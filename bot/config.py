"""Configuration for the read-only SPMB Telegram data bot."""
from __future__ import annotations

from dataclasses import dataclass
import os


def _ids(value: str) -> tuple[str, ...]:
    return tuple(part.strip() for part in value.split(',') if part.strip())


@dataclass(frozen=True)
class Config:
    telegram_bot_token: str = ''
    spmb_api_base_url: str = 'http://127.0.0.1:8083/api/v1/bot'
    spmb_data_bot_token: str = ''
    allowed_group_id: str = 'WAITING'
    allowed_telegram_user_ids: tuple[str, ...] = ()
    ai_fallback_model: str = 'cx/gpt-5.6-luna'
    ai_router_url: str = 'http://127.0.0.1:20128/v1'
    ai_router_api_key: str = ''
    bot_username: str = 'SPMBAFBSBot'
    context_db_path: str = 'context.sqlite3'
    context_ttl_seconds: int = 86400

    @classmethod
    def from_env(cls) -> 'Config':
        return cls(
            telegram_bot_token=os.getenv('TELEGRAM_BOT_TOKEN', ''),
            spmb_api_base_url=os.getenv('SPMB_API_BASE_URL', cls.spmb_api_base_url),
            spmb_data_bot_token=os.getenv('SPMB_DATA_BOT_TOKEN', ''),
            allowed_group_id=os.getenv('ALLOWED_GROUP_ID', 'WAITING'),
            allowed_telegram_user_ids=_ids(os.getenv('ALLOWED_TELEGRAM_USER_IDS', '')),
            ai_fallback_model=os.getenv('AI_FALLBACK_MODEL', cls.ai_fallback_model),
            ai_router_url=os.getenv('AI_ROUTER_URL', cls.ai_router_url),
            ai_router_api_key=os.getenv('AI_ROUTER_API_KEY', ''),
            bot_username=os.getenv('BOT_USERNAME', cls.bot_username),
            context_db_path=os.getenv('CONTEXT_DB_PATH', cls.context_db_path),
            context_ttl_seconds=int(os.getenv('CONTEXT_TTL_SECONDS', str(cls.context_ttl_seconds))),
        )
