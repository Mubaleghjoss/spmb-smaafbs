"""Telegram group privacy filter; does not inspect ordinary group traffic."""
from __future__ import annotations

def should_process(
    message: dict,
    bot_username: str,
    allowed_group_id: str | None = None,
    allowed_user_ids: tuple[str, ...] = (),
) -> bool:
    chat = message.get('chat', {})
    chat_type = chat.get('type')
    if chat_type == 'private': return True
    if chat_type not in ('group', 'supergroup'): return False

    # Authorized members of the official group may use natural-language
    # conversation without mentioning the bot. Other group traffic remains
    # filtered to explicit mentions, commands, or replies to the bot.
    user_id = str(message.get('from', {}).get('id', ''))
    if (
        allowed_group_id not in (None, '', 'WAITING')
        and str(chat.get('id')) == str(allowed_group_id)
        and user_id in {str(value) for value in allowed_user_ids}
    ):
        return True

    text = (message.get('text') or '').strip()
    username = '@' + bot_username.lower()
    if username in text.lower(): return True
    if text.startswith('/') and ('@' not in text.split(maxsplit=1)[0] or text.split(maxsplit=1)[0].lower().endswith(username)): return True
    reply = message.get('reply_to_message', {})
    return reply.get('from', {}).get('username', '').lower() == bot_username.lower()
