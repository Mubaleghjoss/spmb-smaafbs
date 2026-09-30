"""Explicit Telegram session mode routing and production safety gates."""
from __future__ import annotations

import re
from dataclasses import dataclass
import hmac
import subprocess
from pathlib import Path

from .config import Config


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
    return bool(re.fullmatch(r'/(?:status|heartbeat|health)(?:@[^\s]+)?', (text or '').strip(), re.I))


def is_production_destructive_request(text: str) -> bool:
    return bool(_DESTRUCTIVE.search(text or ''))


class SessionRouter:
    """Keeps a per-chat explicit mode; no mode is inferred from free text."""

    def __init__(self, default_mode: str = 'staging'):
        default = (default_mode or 'staging').strip().lower()
        self.default_mode = default if default in MODES else 'staging'
        self._modes: dict[str, str] = {}

    def mode_for(self, chat_id: object) -> str:
        return self._modes.get(str(chat_id), self.default_mode)

    def set_mode(self, chat_id: object, mode: str) -> bool:
        normalized = (mode or '').strip().lower()
        if normalized not in MODES:
            return False
        self._modes[str(chat_id)] = normalized
        return True

    def status_text(self, chat_id: object) -> str:
        mode = self.mode_for(chat_id).upper()
        if mode == 'PRODUCTION':
            return (
                '*Agent Session Status*\n'
                'Mode: *PRODUCTION*\n'
                'Stage: production-read-only\n'
                'Heartbeat: handler-alive\n'
                'Bot status: connected\n'
                'Execution policy: *READ-ONLY*\n'
                'Worker status: not linked (not inferred)\n'
                'Production actions: dispatcher-only and explicit confirmation required.'
            )
        return (
            '*Agent Session Status*\n'
            'Mode: *STAGING*\n'
            'Stage: staging-only\n'
            'Heartbeat: handler-alive\n'
            'Bot status: connected\n'
            'Execution policy: staging-only\n'
            'Worker status: not linked (not inferred)'
        )


def production_rejection(text: str) -> str:
    if is_production_destructive_request(text):
        return ('Mode PRODUCTION is read-only. Destructive actions are rejected; '
                'no shell, SSH, database, migration, restart, deploy, or credential command was run.')
    return ('Mode PRODUCTION accepts read-only routing only. Select /mode staging for staging data '
            'operations; production actions require the approved dispatcher and a separate confirmation step.')


class ProductionDispatcher:
    """Narrow wrapper for the approved production dispatcher.

    This class is intentionally not used by ordinary Telegram text. Callers must
    choose a fixed action, pass a separate confirmation token, and never provide
    a shell command, SSH target, SQL, or credential argument.
    """

    ALLOWED_ACTIONS = frozenset({'status', 'audit', 'deploy', 'restart', 'rollback'})

    def __init__(self, config: Config):
        self.path = Path(config.production_dispatcher_path)
        self.confirmation_token = config.production_confirmation_token

    def dispatch(self, action: str, *, confirmed: bool = False, token: str | None = None) -> str:
        action = (action or '').strip().lower()
        if action not in self.ALLOWED_ACTIONS:
            raise ValueError('unsupported production action')
        if action != 'status' and (not confirmed or not token or not hmac.compare_digest(token, self.confirmation_token)):
            raise PermissionError('explicit production confirmation required')
        if not self.path.is_absolute() or str(self.path) != '/home/hermesadmin/bin/smaafbs-prod-dispatch':
            raise PermissionError('production dispatcher path is not approved')
        # argv-only invocation: no shell, no user-supplied command or target.
        completed = subprocess.run([str(self.path), action], check=False, capture_output=True, text=True, timeout=30)
        return completed.stdout.strip() if completed.returncode == 0 else 'production dispatcher rejected the request'
