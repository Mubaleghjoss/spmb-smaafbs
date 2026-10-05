"""Framework-neutral incoming Telegram message orchestrator."""
from __future__ import annotations

import inspect
import logging
import re
from typing import Callable, Optional

from .api_client import ApiClient
from .callbacks import build_inline_keyboard, parse_callback_data
from .config import Config
from .context_store import ContextStore
from .formatter import format_response
from .parser import (
    is_dangerous_text,
    parse_control_command,
    parse_deterministic,
    extract_academic_year,
    parse_number_range,
    parse_number_selection,
    parse_with_ai,
    validate_intent,
    FALLBACK_MESSAGE,
)
from .privacy import should_process
from .routing import (
    SessionRouter,
    is_status_command,
    parse_mode_command,
    production_rejection,
)
from .run_status import RunStatusStore, TelegramRunStatusBridge, message_binding, topic_id
from .security import (
    BOOTSTRAP_SETUPID_MESSAGE,
    READ_ONLY_MESSAGE,
    WAITING_GROUP_MESSAGE,
    admin_authorization_error,
    authorization_error,
    is_admin_command,
    is_write_request,
)


_LIST_ACTIONS = {
    'list_applicants', 'search_applicant', 'list_by_city', 'list_by_district',
    'list_by_school', 'list_by_document_status', 'list_by_verification_status',
    'list_registered_today',
}

_RUN_STATUS_COMMANDS = frozenset({
    '/run-start', '/run-heartbeat', '/run-stage', '/run-testing',
    '/run-pass', '/run-staging-pass', '/run-done', '/run-blocked', '/run-stop',
})

_HELP_TEXT = (
    '*Perintah bridge staging*\n'
    '/mode staging|production — pilih mode sesi topic\n'
    '/status atau /progress — status worker berdasarkan heartbeat\n'
    '/run RUN_ID — daftarkan run pada topic\n'
    '/stop RUN_ID — hentikan run pada topic\n'
    '/help — tampilkan bantuan\n'
    'Milestone internal: /run-heartbeat, /run-testing, /run-pass, /run-done, /run-blocked.'
)


def _command_name(text: str) -> str:
    token = (text or '').split(maxsplit=1)[0] if (text or '').strip() else ''
    return token.split('@', 1)[0].lower()


class MessageHandler:
    def __init__(self, config: Config, api_client: ApiClient | None = None, ai_parser: Callable[[str, Config], dict | None] = parse_with_ai, context_store: ContextStore | None = None):
        self.config = config
        self.api_client = api_client or ApiClient(config)
        self.ai_parser = ai_parser
        self.context_store = context_store or ContextStore(config.context_db_path, config.context_ttl_seconds)
        # Local SQLite state is staging-only and contains no Telegram profile data.
        self.run_status = RunStatusStore(config.run_status_db_path, config.run_heartbeat_timeout_seconds)
        self.session_router = SessionRouter(config.session_mode, self.run_status)
        self.run_status_bridge = TelegramRunStatusBridge(
            config.telegram_bot_token,
            config.run_status_update_chat_id,
            config.run_status_update_thread_id,
        )
        self.last_reply_markup = None

    def post_run_status_update(self, text: str, chat_id: str | None = None,
                               message_thread_id: str | None = None) -> bool:
        """Send an explicit staging update to the bound Telegram topic only."""
        if self.config.session_mode != 'staging':
            return False
        if chat_id is not None and message_thread_id is not None:
            bridge = TelegramRunStatusBridge(
                self.config.telegram_bot_token, chat_id, message_thread_id,
                sender=self.run_status_bridge.sender,
            )
            return bridge.post(text)
        return self.run_status_bridge.post(text)

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
        if not last_page:
            total = meta.get('total')
            limit = int(filters.get('limit', 10))
            if isinstance(total, (int, float)) and limit > 0:
                last_page = max(1, (int(total) + limit - 1) // limit)
        self.context_store.set(*key, {
            'intent': intent,
            'filters': filters,
            'page': page,
            'last_page': int(last_page) if isinstance(last_page, (int, float)) else None,
            'last_action': intent['action'],
            'last_results': items,
        })

    def _set_list_markup(self, key):
        state = self._state(key) or {}
        self.last_reply_markup = build_inline_keyboard(
            page=int(state.get('page', 1)), last_page=state.get('last_page')
        )

    def handle_callback_query(self, callback_data: dict) -> dict | None:
        """Handle only signed-by-context, allowlisted callback operations."""
        query = callback_data if isinstance(callback_data, dict) else {}
        parsed = parse_callback_data(query.get('data'))
        callback_message = query.get('message') or {}
        if not parsed:
            return {'text': 'Tombol sudah tidak berlaku.'}
        message = {
            'chat': callback_message.get('chat', {}),
            'from': query.get('from', {}),
            'text': '',
        }
        if not should_process(message, self.config.bot_username, self.config.allowed_group_id, self.config.allowed_telegram_user_ids):
            return {'text': 'Tombol tidak berwenang digunakan.'}
        error = authorization_error(message, self.config)
        if error:
            return {'text': error}
        key = self._conversation_key(message)
        state = self._state(key) or {}
        action, value = parsed
        if action in {'prev', 'next', 'reload'}:
            if not state.get('intent') or state.get('last_action') not in _LIST_ACTIONS:
                return {'text': 'Belum ada daftar sebelumnya.'}
            current = int(state.get('page', 1))
            page = current + (-1 if action == 'prev' else 1) if action != 'reload' else current
            last_page = state.get('last_page')
            if action == 'prev' and page < 1:
                return {'text': 'Sudah di halaman pertama.'}
            if action == 'next' and last_page and page > int(last_page):
                return {'text': 'Sudah di halaman terakhir.'}
            intent = {'action': state['intent']['action'], 'filters': dict(state.get('filters', {}))}
            intent['filters']['page'] = page
            intent['filters'].setdefault('limit', 10)
        elif action == 'list':
            intent = {'action': 'list_applicants', 'filters': dict(state.get('filters', {}))}
            intent['filters'].pop('page', None)
        elif action == 'stats':
            intent = {'action': 'get_statistics', 'filters': dict(state.get('filters', {}))}
        elif action == 'stage':
            intent = {'action': 'list_applicants', 'filters': dict(state.get('filters', {}), tahapan=int(value))}
            intent['filters'].pop('page', None)
        elif action == 'docs':
            intent = {'action': 'list_by_document_status', 'filters': dict(state.get('filters', {}), document_status=value)}
            intent['filters'].pop('page', None)
        else:
            self.last_reply_markup = build_inline_keyboard()
            return {'text': 'Menu SPMB: pilih salah satu pilihan di bawah.', 'reply_markup': self.last_reply_markup}
        intent = validate_intent(intent)
        if not intent:
            return {'text': 'Filter tombol tidak valid.'}
        payload = self.api_client.query(intent, str(message.get('from', {}).get('id', '')))
        items = self._payload_items(payload)
        if payload.get('status') == 'success' and intent['action'] in _LIST_ACTIONS:
            self._save_list_state(key, intent, payload, items)
            self._set_list_markup(key)
        else:
            self.last_reply_markup = build_inline_keyboard()
        return {'text': format_response(intent, payload), 'reply_markup': self.last_reply_markup}

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

    def _run_status_command(self, text: str, message_data: dict) -> str | None:
        """Handle worker lifecycle commands; never invokes the data API or shell."""
        parts = text.split()
        command = _command_name(text)
        chat_id, thread = message_binding(message_data)
        if command in {'/status', '/progress', '/heartbeat', '/health', '/run-status'}:
            return self.session_router.status_text(chat_id, thread, self.run_status)
        if command == '/run':
            if len(parts) < 2:
                return 'Usage: /run RUN_ID'
            try:
                state = self.run_status.start(chat_id, thread, parts[1])
                self.post_run_status_update(f'STARTED run_id={state.run_id} stage={state.stage}', chat_id, thread)
                return self.session_router.status_text(chat_id, thread, self.run_status)
            except ValueError as exc:
                return f'Run status rejected: {exc}'
        if command == '/stop':
            if len(parts) < 2:
                return 'Usage: /stop RUN_ID'
            try:
                state = self.run_status.milestone(chat_id, thread, parts[1], 'STOPPED', 'stopped by operator')
                self.post_run_status_update(f'STOPPED run_id={state.run_id} stage={state.stage}', chat_id, thread)
                return self.session_router.status_text(chat_id, thread, self.run_status)
            except ValueError as exc:
                return f'Run status rejected: {exc}'
        if command not in _RUN_STATUS_COMMANDS:
            return None
        try:
            if command == '/run-start':
                run_id = parts[1] if len(parts) > 1 else None
                state = self.run_status.start(chat_id, thread, run_id)
                self.post_run_status_update(f'STARTED run_id={state.run_id} stage={state.stage}', chat_id, thread)
            elif command == '/run-heartbeat':
                if len(parts) < 2: return 'Usage: /run-heartbeat RUN_ID [stage]'
                state = self.run_status.heartbeat(chat_id, thread, parts[1], ' '.join(parts[2:]) or None)
            else:
                if len(parts) < 2: return f'Usage: {command} RUN_ID'
                target = {'/run-stage': 'TESTING', '/run-testing': 'TESTING', '/run-pass': 'STAGING_PASS', '/run-staging-pass': 'STAGING_PASS', '/run-done': 'DONE', '/run-blocked': 'BLOCKED', '/run-stop': 'STOPPED'}[command]
                stage = 'staging pass' if target == 'STAGING_PASS' else None
                state = self.run_status.milestone(chat_id, thread, parts[1], target, stage)
                self.post_run_status_update(
                    f"{state.state} run_id={state.run_id} stage={state.stage}",
                    chat_id, thread,
                )
            return self.session_router.status_text(chat_id, thread, self.run_status)
        except ValueError as exc:
            return f'Run status rejected: {exc}'

    def handle_message(self, message_data: dict) -> Optional[str]:
        self.last_reply_markup = None
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

        mode_command = parse_mode_command(text)
        if mode_command.is_command:
            if mode_command.requested is None:
                return 'Mode saat ini: *%s*. Gunakan /mode staging atau /mode production.' % self.session_router.mode_for(message_data.get('chat', {}).get('id'), topic_id(message_data)).upper()
            if mode_command.requested not in {'staging', 'production'}:
                return 'Mode tidak dikenal. Pilih hanya: staging atau production.'
            self.session_router.set_mode(message_data.get('chat', {}).get('id'), mode_command.requested, topic_id(message_data))
            return 'Mode sesi diubah ke *%s*.' % mode_command.requested.upper()

        if _command_name(text) == '/help':
            return _HELP_TEXT

        chat_id = message_data.get('chat', {}).get('id')
        current_mode = self.session_router.mode_for(chat_id, topic_id(message_data))
        if current_mode == 'production':
            if is_status_command(text):
                return self.session_router.status_text(chat_id, topic_id(message_data), self.run_status)
            if _command_name(text) in _RUN_STATUS_COMMANDS or _command_name(text) in {'/run', '/stop'}:
                return production_rejection(text)
            if control not in {'/setupid', '/whoami'}:
                return production_rejection(text)

        if is_status_command(text):
            if message_data.get('chat', {}).get('type') not in {'group', 'supergroup'}:
                admin_error = admin_authorization_error(message_data, self.config)
                if admin_error:
                    return admin_error
            return self._run_status_command(text, message_data)

        if _command_name(text) in _RUN_STATUS_COMMANDS or _command_name(text) in {'/run', '/stop'}:
            return self._run_status_command(text, message_data)

        if is_admin_command(text):
            admin_error = admin_authorization_error(message_data, self.config)
            logging.getLogger('spmb.bot.audit').info(
                'admin_command action=%s chat_type=%s authorized=%s',
                text.split(maxsplit=1)[0].lower() if text else 'unknown',
                message_data.get('chat', {}).get('type'),
                not bool(admin_error),
            )
            if admin_error:
                return admin_error
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

        number_range = parse_number_range(text)
        if number_range is not None:
            start, end = number_range
            requested = end - start + 1
            # The read API exposes page/limit rather than an offset.  The
            # screenshot flow (11-18) therefore maps to page 2 of 8; keep
            # any previously selected academic year and other list filters.
            parsed = {
                'action': 'list_applicants',
                'filters': {
                    'page': ((start - 1) // requested) + 1,
                    'limit': requested,
                },
            }
            year = extract_academic_year(text)
            if year:
                parsed['filters']['tahun_ajaran'] = year
        else:
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
                self._set_list_markup(key)
            return format_response(intent, payload)

        intent = validate_intent(parsed)
        if not intent:
            candidate = self._call_ai(text, state)
            intent = validate_intent(candidate)
        if not intent:
            return FALLBACK_MESSAGE

        if intent['action'] in {'get_extension_status', 'get_applicant_progress', 'lookup_payment_proof', 'lookup_document_proof'}:
            admin_error = admin_authorization_error(message_data, self.config)
            if admin_error:
                logging.getLogger('spmb.bot.audit').info(
                    'admin_intent action=%s chat_type=%s authorized=False',
                    intent['action'], message_data.get('chat', {}).get('type'),
                )
                return admin_error

        if intent['action'] == 'get_extension_status':
            payload = {'status': 'success', 'data': {
                'stage': 'staging-only',
                'status': 'read-only',
                'test': 'verified by local test command',
                'health': {
                    'api_configured': bool(self.config.spmb_api_base_url and self.config.spmb_data_bot_token),
                    'ai_fallback_configured': bool(self.config.ai_router_url),
                },
                'blocker': 'signed private document/payment delivery unavailable in current API contract',
            }}
            return format_response(intent, payload)

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
            self._set_list_markup(key)
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
