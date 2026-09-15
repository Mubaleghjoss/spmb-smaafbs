"""Framework-neutral incoming Telegram message orchestrator."""
from __future__ import annotations

import re
from typing import Callable, Optional

from .api_client import ApiClient
from .config import Config
from .formatter import format_response
from .parser import (
    is_dangerous_text,
    normalize_academic_year,
    parse_control_command,
    parse_deterministic,
    parse_with_ai,
    validate_intent,
)
from .privacy import should_process
from .security import (
    BOOTSTRAP_SETUPID_MESSAGE,
    READ_ONLY_MESSAGE,
    WAITING_GROUP_MESSAGE,
    authorization_error,
    is_write_request,
)


class MessageHandler:
    def __init__(
        self,
        config: Config,
        api_client: ApiClient | None = None,
        ai_parser: Callable[[str, Config], dict | None] = parse_with_ai,
    ):
        self.config, self.api_client, self.ai_parser = config, api_client or ApiClient(config), ai_parser
        self._year_context: dict[tuple[str, str], str] = {}
        self._last_results: dict[tuple[str, str], list[dict]] = {}

    @staticmethod
    def _conversation_key(message_data: dict) -> tuple[str, str]:
        return (
            str(message_data.get('chat', {}).get('id', '')),
            str(message_data.get('from', {}).get('id', '')),
        )

    @staticmethod
    def _payload_items(payload: dict) -> list[dict]:
        data = payload.get('data')
        if isinstance(data, dict) and isinstance(data.get('data'), list):
            data = data['data']
        return data if isinstance(data, list) else []

    @staticmethod
    def _is_biodata_list_request(text: str) -> bool:
        return bool(re.search(r'\btampilkan\s+biodata\s+pendaftar\b', text, re.I))

    def _detail_from_item(self, item: dict, message_data: dict) -> str:
        identifier = item.get('nomor_pendaftaran', item.get('id'))
        intent = {'action': 'get_applicant_detail', 'filters': {'identifier': str(identifier)}}
        payload = self.api_client.query(intent, str(message_data.get('from', {}).get('id', '')))
        return format_response(intent, payload)

    def handle_message(self, message_data: dict) -> Optional[str]:
        if not should_process(
            message_data,
            self.config.bot_username,
            self.config.allowed_group_id,
            self.config.allowed_telegram_user_ids,
        ):
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
        # Resolve a numbered follow-up against the last list for this chat/user.
        number_match = re.fullmatch(r'biodata\s+nomor\s+(\d+)', text, re.I)
        if number_match:
            items = self._last_results.get(key, [])
            index = int(number_match.group(1)) - 1
            if 0 <= index < len(items):
                return self._detail_from_item(items[index], message_data)
            return 'Nomor pendaftar tidak ditemukan dalam daftar terakhir.'

        # A quota result establishes the academic-year context for the next
        # natural-language question in the same chat and user scope.
        contextual_list = bool(re.fullmatch(r'itu\s+\d+\s+pendaftar\s+siapa\??', text, re.I))
        intent = validate_intent(parse_deterministic(text))
        if contextual_list and key in self._year_context:
            intent = {
                'action': 'list_applicants',
                'filters': {'tahun_ajaran': self._year_context[key]},
            }

        if not intent:
            intent = validate_intent(self.ai_parser(text, self.config))
        if not intent:
            return 'Maaf, perintah tidak dikenali. Gunakan /kuota, /statistik, /cari, atau /biodata.'

        user_id = str(message_data.get('from', {}).get('id', ''))
        payload = self.api_client.query(intent, user_id)
        filters = intent.get('filters', {})
        year = filters.get('tahun_ajaran')
        if year:
            normalized = normalize_academic_year(year)
            if normalized:
                self._year_context[key] = normalized

        if intent['action'] == 'list_applicants':
            items = self._payload_items(payload)
            self._last_results[key] = items
            if self._is_biodata_list_request(text) and len(items) == 1:
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
        return (f"ID pengguna Telegram: {user_id}\n"
                f"ID chat Telegram: {chat.get('id')}\n"
                f"Tipe chat: {chat.get('type')}")


def handle_message(message_data: dict) -> Optional[str]:
    return MessageHandler(Config.from_env()).handle_message(message_data)
