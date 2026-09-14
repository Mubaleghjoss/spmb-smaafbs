"""Authorization and strict read-only policy checks."""
from __future__ import annotations
import re
from .config import Config

READ_ONLY_MESSAGE = 'Bot Data SPMB hanya memiliki akses baca. Silakan lakukan perubahan melalui aplikasi SPMB.'
WAITING_GROUP_MESSAGE = 'Grup resmi Tim SPMB belum dikonfigurasi. Hubungi administrator.'
UNAUTHORIZED_MESSAGE = 'Anda tidak berwenang menggunakan Bot Data SPMB.'
WRONG_GROUP_MESSAGE = 'Bot Data SPMB hanya dapat digunakan di grup resmi Tim SPMB.'
_WRITE = re.compile(r'\b(ubah(\s+data)?|hapus(\s+data)?|verifikasi|terima\s+siswa|edit\s+biodata|tolak|update|delete|insert|create)\b', re.I)

def authorization_error(message: dict, config: Config) -> str | None:
    chat = message.get('chat', {})
    chat_type, chat_id = chat.get('type'), chat.get('id')
    if chat_type in ('group', 'supergroup'):
        if config.allowed_group_id == 'WAITING': return WAITING_GROUP_MESSAGE
        if str(chat_id) != str(config.allowed_group_id): return WRONG_GROUP_MESSAGE
    user_id = message.get('from', {}).get('id')
    if str(user_id) not in config.allowed_telegram_user_ids: return UNAUTHORIZED_MESSAGE
    return None

def is_write_request(text: str) -> bool:
    return bool(_WRITE.search(text or ''))
