"""Framework-neutral incoming Telegram message orchestrator."""
from __future__ import annotations

import re
from typing import Callable, Optional

from .api_client import ApiClient
from .config import Config
from .context_store import ContextStore
from .formatter import format_response
from .parser import is_dangerous_text, normalize_academic_year, parse_control_command, parse_deterministic, parse_with_ai, validate_intent
from .privacy import should_process
from .security import BOOTSTRAP_SETUPID_MESSAGE, READ_ONLY_MESSAGE, WAITING_GROUP_MESSAGE, authorization_error, is_write_request


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
        if isinstance(data, dict) and isinstance(data.get('data'), list):
            data = data['data']
        return data if isinstance(data, list) else []

    def _detail_from_item(self, item: dict, message_data: dict) -> str:
        identifier = item.get('nomor_pendaftaran', item.get('id'))
        intent = {'action': 'get_applicant_detail', 'filters': {'identifier': str(identifier)}}
        payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
        return format_response(intent, payload)

    @staticmethod
    def _is_biodata_list_request(text: str) -> bool:
        return bool(re.search(r'\btampilkan\s+biodata\s+pendaftar\b', text, re.I))

    def _state(self, key):
        return self.context_store.get(*key)

    def handle_message(self, message_data: dict) -> Optional[str]:
        if not should_process(message_data, self.config.bot_username, self.config.allowed_group_id, self.config.allowed_telegram_user_ids):
            return None
        text = (message_data.get('text') or '').strip()
        control = parse_control_command(text)
        if self.config.allowed_group_id == 'WAITING':
            if control == '/setupid': return self._setup_id_response(message_data)
            if control == '/whoami': return self._whoami_response(message_data)
            return WAITING_GROUP_MESSAGE
        error = authorization_error(message_data, self.config)
        if error: return error
        if control == '/setupid': return self._setup_id_response(message_data)
        if control == '/whoami': return self._whoami_response(message_data)
        if is_write_request(text): return READ_ONLY_MESSAGE
        if is_dangerous_text(text): return 'Permintaan tidak dapat diproses.'

        key = self._conversation_key(message_data)
        state = self._state(key)
        ordinal = {'pertama': 1, 'satu': 1, 'kedua': 2, 'dua': 2, 'ketiga': 3, 'tiga': 3, 'keempat': 4, 'empat': 4, 'kelima': 5, 'lima': 5}
        selection = re.search(r'(?:biodata\s+)?(?:nomor|no\.?|yang\s+ke)\s*(\d+|pertama|satu|kedua|dua|ketiga|tiga|keempat|empat|kelima|lima)\b', text, re.I)
        if selection and state.get('last_results'):
            raw = selection.group(1).lower()
            index = int(raw) if raw.isdigit() else ordinal[raw]
            items = state['last_results']
            if 1 <= index <= len(items): return self._detail_from_item(items[index - 1], message_data)
            return 'Nomor pendaftar tidak ditemukan dalam daftar terakhir.'
        year_shift = re.search(r'\b(tahun|periode)\s+(sebelumnya|berikutnya|selanjutnya)\b', text, re.I)
        if year_shift and state.get('filters', {}).get('tahun_ajaran'):
            start, end = map(int, state['filters']['tahun_ajaran'].split('/'))
            delta = -1 if year_shift.group(2).lower() == 'sebelumnya' else 1
            text = re.sub(r'\b(tahun|periode)\s+(sebelumnya|berikutnya|selanjutnya)\b', f'{start + delta}/{end + delta}', text, flags=re.I)
        parsed = parse_deterministic(text)
        if parsed and parsed['action'] == 'reset':
            self.context_store.clear(*key)
            return 'Konteks percakapan telah direset.'
        if parsed and parsed['action'] == 'incompatible_filter':
            return 'Kombinasi siswa baru dan kelas 11 tidak tersedia.'
        if parsed and parsed['action'] in {'next_page', 'previous_page'}:
            if not state.get('intent'):
                return 'Belum ada daftar pendaftar untuk dinavigasi.'
            page = int(state.get('page', 1)) + (1 if parsed['action'] == 'next_page' else -1)
            if page < 1: return 'Ini sudah halaman pertama.'
            intent = dict(state['intent'])
            filters = dict(intent.get('filters', {})); filters['page'] = page; filters.setdefault('limit', 10)
            intent['filters'] = filters
            payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
            items = self._payload_items(payload)
            if not items: return 'Ini sudah halaman terakhir.' if parsed['action'] == 'next_page' else 'Ini sudah halaman pertama.'
            self.context_store.set(*key, {'intent': intent, 'filters': filters, 'page': page, 'last_action': intent['action'], 'last_results': items})
            return format_response(intent, payload)

        if re.fullmatch(r'itu\s+\d+\s+pendaftar\s+siapa\??', text, re.I) and state.get('filters', {}).get('tahun_ajaran'):
            parsed = {'action': 'list_applicants', 'filters': {'tahun_ajaran': state['filters']['tahun_ajaran']}}
        intent = validate_intent(parsed)
        if not intent:
            candidate = self.ai_parser(text, self.config)
            intent = validate_intent(candidate)
        if not intent:
            # Bare follow-ups inherit the previous query and merge only explicit filters.
            if parsed and parsed.get('action') == 'list_applicants' and state.get('intent'):
                old = state['intent']; intent = {'action': old['action'], 'filters': dict(old.get('filters', {}))}
                intent['filters'].update(parsed.get('filters', {}))
            else:
                return 'Maaf, perintah tidak dikenali. Gunakan /kuota, /statistik, /cari, atau /biodata.'
        if intent['action'] not in {'list_applicants', 'search_applicant', 'list_by_city', 'list_by_district', 'list_by_school', 'list_by_document_status', 'list_by_verification_status', 'list_registered_today'}:
            payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
            return format_response(intent, payload)
        old_filters = state.get('filters', {})
        # Conversational list filters merge with prior list context; explicit year wins.
        if parsed and parsed.get('action') == 'list_applicants' and state.get('intent'):
            merged = dict(old_filters); merged.update(intent.get('filters', {})); intent = {'action': 'list_applicants', 'filters': merged}
        filters = dict(intent.get('filters', {})); intent['filters'] = filters
        if filters.get('jenis_pendaftaran') == 'siswa_baru' and filters.get('kelas_tujuan') == 11:
            return 'Kombinasi siswa baru dan kelas 11 tidak tersedia.'
        user_id = str(message_data.get('from', {}).get('id', ''))
        payload = self.api_client.query(intent, user_id)
        items = self._payload_items(payload)
        if payload.get('status') == 'success':
            self.context_store.set(*key, {'intent': intent, 'filters': filters, 'page': int(filters.get('page', 1)), 'last_action': intent['action'], 'last_results': items})
        if intent['action'] == 'list_applicants' and self._is_biodata_list_request(text) and len(items) == 1:
            return self._detail_from_item(items[0], message_data)
        return format_response(intent, payload)

    @staticmethod
    def _setup_id_response(message_data: dict) -> str:
        chat = message_data.get('chat', {})
        if chat.get('type') not in ('group', 'supergroup'): return BOOTSTRAP_SETUPID_MESSAGE
        return f"ID grup Telegram saat ini: {chat.get('id')}"

    @staticmethod
    def _whoami_response(message_data: dict) -> str:
        user_id = message_data.get('from', {}).get('id'); chat = message_data.get('chat', {})
        return f"ID pengguna Telegram: {user_id}\nID chat Telegram: {chat.get('id')}\nTipe chat: {chat.get('type')}"


def handle_message(message_data: dict) -> Optional[str]:
    return MessageHandler(Config.from_env()).handle_message(message_data)
