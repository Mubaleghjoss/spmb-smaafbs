"""Telegram group privacy filter; does not inspect ordinary group traffic."""
from __future__ import annotations

def should_process(message: dict, bot_username: str) -> bool:
    chat_type = message.get('chat', {}).get('type')
    if chat_type == 'private': return True
    if chat_type not in ('group', 'supergroup'): return False
    text = (message.get('text') or '').strip()
    username = '@' + bot_username.lower()
    if username in text.lower(): return True
    if text.startswith('/') and ('@' not in text.split(maxsplit=1)[0] or text.split(maxsplit=1)[0].lower().endswith(username)): return True
    reply = message.get('reply_to_message', {})
    return reply.get('from', {}).get('username', '').lower() == bot_username.lower()
