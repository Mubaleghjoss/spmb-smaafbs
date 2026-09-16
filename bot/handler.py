"""Framework-neutral incoming Telegram message orchestrator."""
from __future__ import annotations

import inspect
import re
from typing import Callable, Optional

from .api_client import ApiClient
from .config import Config
from .context_store import ContextStore
from .formatter import format_response
from .parser import (
    is_dangerous_text,
    parse_control_command,
    parse_deterministic,
    parse_number_selection,
    parse_with_ai,
    validate_intent,
    FALLBACK_MESSAGE,
)
from .privacy import should_process
from .security import (
    BOOTSTRAP_SETUPID_MESSAGE,
    READ_ONLY_MESSAGE,
    WAITING_GROUP_MESSAGE,
    authorization_error,
    is_write_request,
)


_LIST_ACTIONS = {
    'list_applicants', 'search_applicant', 'list_by_city', 'list_by_district',
    'list_by_school', 'list_by_document_status', 'list_by_verification_status',
    'list_registered_today',
}


class MessageHandler:
    def __init__(self, config: Config, api_client: ApiClient | None = None, ai_parser: Callable[[str, Config], dict | None] = parse_with_ai, context_store: ContextStore | None = None):
        self.config = config
        self.api_client = api_client or ApiClient(config)
        self.ai_parser = ai_parser
        self.context_store = context_store or ContextStore(config.context_db_path, config.context_ttl_seconds)

    @staticmethod
    def _conversation_key(message_data: dict) -> tuple[str, str]:
        return str(message_data.get('chat', {}).get('id', '')), str(message_data.get('from', {}).get('id', ''))

    @staticmethod
    def _payload_items(payload: dict) -> list[dict]:
        data = payload.get('data')
        if isinstance(data, dict):
            data = data.get('data', data.get('items', []))
        return data if isinstance(data, list) else []

    @staticmethod
    def _payload_meta(payload: dict) -> dict:
        meta = payload.get('meta', {}) if isinstance(payload, dict) else {}
        data = payload.get('data') if isinstance(payload, dict) else None
        if (not isinstance(meta, dict) or not meta) and isinstance(data, dict):
            meta = data.get('meta', {})
        return meta if isinstance(meta, dict) else {}

    def _detail_from_item(self, item: dict, message_data: dict) -> str:
        identifier = item.get('nomor_pendaftaran', item.get('id'))
        if identifier is None:
            return 'Pendaftar tidak ditemukan.'
        intent = {'action': 'get_applicant_detail', 'filters': {'identifier': str(identifier)}}
        payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
        return format_response(intent, payload)

    @staticmethod
    def _is_biodata_list_request(text: str) -> bool:
        return bool(re.search(r'\btampilkan\s+biodata\s+pendaftar\b', text, re.I))

    def _state(self, key):
        return self.context_store.get(*key)

    def _save_list_state(self, key, intent, payload, items):
        filters = dict(intent.get('filters', {}))
        meta = self._payload_meta(payload)
        page = int(filters.get('page', meta.get('current_page') or meta.get('page') or 1))
        last_page = meta.get('last_page') or meta.get('total_pages')
        self.context_store.set(*key, {
            'intent': intent,
            'filters': filters,
            'page': page,
            'last_page': int(last_page) if isinstance(last_page, (int, float)) else None,
            'last_action': intent['action'],
            'last_results': items,
        })

    def _call_ai(self, text: str, state: dict) -> dict | None:
        # Keep compatibility with phase-1/2 test callbacks while allowing the
        # production parser to receive only sanitized current context.
        context = {
            key: state[key] for key in ('filters', 'page', 'last_page', 'last_action')
            if key in state
        }
        try:
            signature = inspect.signature(self.ai_parser)
            try:
                signature.bind(text, self.config, context)
            except TypeError:
                return self.ai_parser(text, self.config)
            return self.ai_parser(text, self.config, context)
        except Exception:
            return None

    def handle_message(self, message_data: dict) -> Optional[str]:
        if not should_process(message_data, self.config.bot_username, self.config.allowed_group_id, self.config.allowed_telegram_user_ids):
            return None
        text = (message_data.get('text') or '').strip()
        control = parse_control_command(text)
        if self.config.allowed_group_id == 'WAITING':
            if control == '/setupid':
                return self._setup_id_response(message_data)
            if control == '/whoami':
                return self._whoami_response(message_data)
            return WAITING_GROUP_MESSAGE
        error = authorization_error(message_data, self.config)
        if error:
            return error
        if control == '/setupid':
            return self._setup_id_response(message_data)
        if control == '/whoami':
            return self._whoami_response(message_data)
        if is_write_request(text):
            return READ_ONLY_MESSAGE
        if is_dangerous_text(text):
            return 'Permintaan tidak dapat diproses.'

        key = self._conversation_key(message_data)
        state = self._state(key)
        selection = parse_number_selection(text)
        if selection is not None:
            if state.get('last_results'):
                items = state['last_results']
                if 1 <= selection <= len(items):
                    return self._detail_from_item(items[selection - 1], message_data)
                return 'Nomor itu tidak ada pada daftar terakhir.'
            return 'Belum ada daftar sebelumnya. Coba minta daftar peserta terlebih dahulu.'

        parsed = parse_deterministic(text)
        if parsed and parsed['action'] == 'reset':
            self.context_store.clear(*key)
            return 'Konteks pencarian sudah direset.'
        if parsed and parsed['action'] == 'incompatible_filter':
            return 'Kombinasi siswa baru dan kelas 11 tidak tersedia.'

        year_shift = re.search(r'\b(tahun|periode)\s+(sebelumnya|berikutnya|selanjutnya)\b', text, re.I)
        if year_shift and state.get('filters', {}).get('tahun_ajaran'):
            start, end = map(int, state['filters']['tahun_ajaran'].split('/'))
            delta = -1 if year_shift.group(2).lower() == 'sebelumnya' else 1
            text = re.sub(r'\b(tahun|periode)\s+(sebelumnya|berikutnya|selanjutnya)\b', f'{start + delta}/{end + delta}', text, flags=re.I)
            parsed = parse_deterministic(text)

        if re.fullmatch(r'itu\s+\d+\s+pendaftar\s+siapa\??', text, re.I) and state.get('filters', {}).get('tahun_ajaran'):
            parsed = {'action': 'list_applicants', 'filters': {'tahun_ajaran': state['filters']['tahun_ajaran']}}

        if parsed and parsed['action'] in {'next_page', 'previous_page'}:
            if not state.get('intent') or state.get('last_action') not in _LIST_ACTIONS:
                return 'Belum ada daftar sebelumnya. Coba minta daftar peserta terlebih dahulu.'
            current = int(state.get('page', 1))
            last_page = state.get('last_page')
            if parsed['action'] == 'previous_page' and current <= 1:
                return 'Sudah di halaman pertama.'
            if parsed['action'] == 'next_page' and last_page and current >= int(last_page):
                return 'Sudah di halaman terakhir.'
            page = current + (1 if parsed['action'] == 'next_page' else -1)
            intent = {'action': state['intent']['action'], 'filters': dict(state['intent'].get('filters', {}))}
            intent['filters']['page'] = page
            intent['filters'].setdefault('limit', 10)
            payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
            items = self._payload_items(payload)
            if payload.get('status') == 'success' and not items and parsed['action'] == 'next_page':
                return 'Sudah di halaman terakhir.'
            if payload.get('status') == 'success' and not items and parsed['action'] == 'previous_page':
                return 'Sudah di halaman pertama.'
            if payload.get('status') == 'success':
                self._save_list_state(key, intent, payload, items)
            return format_response(intent, payload)

        intent = validate_intent(parsed)
        if not intent:
            candidate = self._call_ai(text, state)
            intent = validate_intent(candidate)
        if not intent:
            return FALLBACK_MESSAGE

        if intent['action'] not in _LIST_ACTIONS:
            payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
            return format_response(intent, payload)

        explicit_filters = dict(intent.get('filters', {}))
        if intent['action'] == 'list_applicants' and state.get('intent'):
            merged = dict(state.get('filters', {}))
            previous_type = merged.get('jenis_pendaftaran')
            merged.update(explicit_filters)
            if explicit_filters.get('jenis_pendaftaran') == 'siswa_baru' and previous_type == 'pindahan':
                merged.pop('kelas_tujuan', None)
            if any(k not in {'page', 'limit'} for k in explicit_filters):
                merged.pop('page', None)
            intent = {'action': 'list_applicants', 'filters': merged}
        filters = dict(intent.get('filters', {}))
        if filters.get('jenis_pendaftaran') == 'siswa_baru' and filters.get('kelas_tujuan') == 11:
            return 'Kombinasi siswa baru dan kelas 11 tidak tersedia.'
        user_id = str(message_data.get('from', {}).get('id', ''))
        payload = self.api_client.query(intent, user_id)
        items = self._payload_items(payload)
        if payload.get('status') == 'success':
            self._save_list_state(key, intent, payload, items)
        if intent['action'] == 'list_applicants' and self._is_biodata_list_request(text) and len(items) == 1:
            return self._detail_from_item(items[0], message_data)
        return format_response(intent, payload)

    @staticmethod
    def _setup_id_response(message_data: dict) -> str:
        chat = message_data.get('chat', {})
        if chat.get('type') not in ('group', 'supergroup'):
            return BOOTSTRAP_SETUPID_MESSAGE
        return f"ID grup Telegram saat ini: {chat.get('id')}"

    @staticmethod
    def _whoami_response(message_data: dict) -> str:
        user_id = message_data.get('from', {}).get('id')
        chat = message_data.get('chat', {})
        return f"ID pengguna Telegram: {user_id}\nID chat Telegram: {chat.get('id')}\nTipe chat: {chat.get('type')}"


def handle_message(message_data: dict) -> Optional[str]:
    return MessageHandler(Config.from_env()).handle_message(message_data)
