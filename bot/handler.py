"""Framework-neutral incoming Telegram message orchestrator."""
from __future__ import annotations
from typing import Callable, Optional
from .api_client import ApiClient
from .config import Config
from .formatter import format_response
from .parser import is_dangerous_text, parse_deterministic, parse_with_ai, validate_intent
from .privacy import should_process
from .security import READ_ONLY_MESSAGE, authorization_error, is_write_request

class MessageHandler:
    def __init__(self, config: Config, api_client: ApiClient | None = None, ai_parser: Callable[[str, Config], dict | None] = parse_with_ai):
        self.config, self.api_client, self.ai_parser = config, api_client or ApiClient(config), ai_parser
    def handle_message(self, message_data: dict) -> Optional[str]:
        if not should_process(message_data, self.config.bot_username): return None
        error = authorization_error(message_data, self.config)
        if error: return error
        text = (message_data.get('text') or '').strip()
        if is_write_request(text): return READ_ONLY_MESSAGE
        # Injection-like input is never sent to either the API or AI router.
        if is_dangerous_text(text): return 'Permintaan tidak dapat diproses.'
        intent = validate_intent(parse_deterministic(text)) or self.ai_parser(text, self.config)
        if not intent: return 'Maaf, perintah tidak dikenali. Gunakan /kuota, /statistik, /cari, atau /biodata.'
        return format_response(intent, self.api_client.query(intent, str(message_data.get('from', {}).get('id', ''))))

def handle_message(message_data: dict) -> Optional[str]:
    return MessageHandler(Config.from_env()).handle_message(message_data)
