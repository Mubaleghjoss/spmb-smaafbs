"""Explicit Telegram session mode routing and production safety gates."""
from __future__ import annotations

import re
from dataclasses import dataclass

from .run_status import RunStatusStore


MODES = frozenset({'staging', 'production'})
_MODE_COMMAND = re.compile(r'^/mode(?:@[^\s]+)?(?:\s+([^\s]+))?\s*$', re.I)
_DESTRUCTIVE = re.compile(
    r'(^|\s)/(?:deploy|restart|migrate|ssh|shell|exec|db|database|rollback|credential|secrets?)\b'
    r'|\b(?:deploy|restart|migrate|ssh|shell|execute|drop|truncate|delete|update|insert)\b',
    re.I,
)


@dataclass(frozen=True)
class ModeCommand:
    requested: str | None
    is_command: bool


def parse_mode_command(text: str) -> ModeCommand:
    match = _MODE_COMMAND.fullmatch((text or '').strip())
    if not match:
        return ModeCommand(None, False)
    value = match.group(1)
    return ModeCommand(value.lower() if value else None, True)


def is_status_command(text: str) -> bool:
    """Recognize local status/heartbeat probes without API or AI fallback."""
    return bool(re.fullmatch(r'/(?:status|progress|heartbeat|health|run-status)(?:@[^\s]+)?', (text or '').strip(), re.I))


def is_production_destructive_request(text: str) -> bool:
    return bool(_DESTRUCTIVE.search(text or ''))


class SessionRouter:
    """Keeps a per-chat explicit mode; no mode is inferred from free text."""

    def __init__(self, default_mode: str = 'staging', session_store: RunStatusStore | None = None):
        default = (default_mode or 'staging').strip().lower()
        self.default_mode = default if default in MODES else 'staging'
        self._modes: dict[tuple[str, str], str] = {}
        self.session_store = session_store

    def mode_for(self, chat_id: object, message_thread_id: object = '') -> str:
        key = (str(chat_id), str(message_thread_id or ''))
        if key in self._modes:
            return self._modes[key]
        if self.session_store:
            return self.session_store.get_mode(*key, default=self.default_mode)
        return self.default_mode

    def set_mode(self, chat_id: object, mode: str, message_thread_id: object = '') -> bool:
        normalized = (mode or '').strip().lower()
        if normalized not in MODES:
            return False
        key = (str(chat_id), str(message_thread_id or ''))
        self._modes[key] = normalized
        if self.session_store:
            self.session_store.set_mode(*key, normalized)
        return True

    def status_text(self, chat_id: object, message_thread_id: object = '', run_store: RunStatusStore | None = None) -> str:
        mode = self.mode_for(chat_id, message_thread_id).upper()
        if mode == 'PRODUCTION':
            return (
                '*Agent Session Status*\n'
                'Mode: *PRODUCTION*\n'
                'Stage: production-read-only\n'
                'Heartbeat: handler-alive\n'
                'Bot status: connected\n'
                'Execution policy: *READ-ONLY*\n'
                'Worker status: no active worker (production state is not inferred)\n'
                'Production actions: dispatcher-only and explicit confirmation required.'
            )
        worker = run_store.format_status(run_store.describe(chat_id, message_thread_id)) if run_store else 'Worker status: no active worker'
        return (
            '*Agent Session Status*\n'
            'Mode: *STAGING*\n'
            'Stage: staging-only\n'
            'Heartbeat: handler-alive\n'
            'Bot status: connected\n'
            'Execution policy: staging-only\n'
            'Blocker: no active worker is claimed without a fresh heartbeat\n'
            + worker
        )


def production_rejection(text: str) -> str:
    if is_production_destructive_request(text):
        return ('Mode PRODUCTION is read-only. Destructive actions are rejected; '
                'no shell, SSH, database, migration, restart, deploy, or credential command was run.')
    return ('Mode PRODUCTION accepts read-only routing only. Select /mode staging for staging data '
            'operations; production actions require the approved dispatcher and a separate confirmation step.')
