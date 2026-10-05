"""Staging-only, topic-bound agent run status storage.

This module deliberately has no process, shell, SSH, database, or deployment control.
A worker is considered active only while its heartbeat is fresh.
"""
from __future__ import annotations

from dataclasses import dataclass
from datetime import datetime, timezone
import sqlite3
import time
import json
from urllib import request
import uuid


TERMINAL_STATES = frozenset({"DONE", "BLOCKED", "STOPPED"})
STATES = frozenset({"STARTED", "RUNNING", "TESTING", "STAGING_PASS", *TERMINAL_STATES})
_TRANSITIONS = {
    "STARTED": frozenset({"STARTED", "RUNNING", "TESTING", "BLOCKED", "STOPPED"}),
    "RUNNING": frozenset({"RUNNING", "TESTING", "STAGING_PASS", "DONE", "BLOCKED", "STOPPED"}),
    "TESTING": frozenset({"TESTING", "STAGING_PASS", "DONE", "BLOCKED", "STOPPED"}),
    "STAGING_PASS": frozenset({"STAGING_PASS", "DONE", "BLOCKED", "STOPPED"}),
    "DONE": frozenset({"DONE"}),
    "BLOCKED": frozenset({"BLOCKED"}),
    "STOPPED": frozenset({"STOPPED"}),
}


def _now() -> float:
    return time.time()


def topic_id(message: dict) -> str:
    """Return a stable, non-PII topic key; empty means the chat's main thread."""
    value = message.get("message_thread_id")
    if value is None:
        value = message.get("chat", {}).get("message_thread_id")
    return str(value) if value is not None else ""


def message_binding(message: dict) -> tuple[str, str]:
    """Return the exact Telegram chat/topic binding, rejecting incomplete input."""
    chat_id = message.get("chat", {}).get("id")
    if chat_id is None:
        raise ValueError("chat_id is required")
    return str(chat_id), topic_id(message)


@dataclass(frozen=True)
class RunState:
    chat_id: str
    message_thread_id: str
    run_id: str
    state: str
    stage: str
    heartbeat_at: float
    last_seen: float

    def is_active(self, now: float, heartbeat_timeout: int) -> bool:
        return self.state not in TERMINAL_STATES and self.heartbeat_at >= 0 and now - self.last_seen <= heartbeat_timeout


class RunStatusStore:
    def __init__(self, path: str = ":memory:", heartbeat_timeout: int = 90, clock=_now):
        self.path = path
        self.heartbeat_timeout = int(heartbeat_timeout)
        self.clock = clock
        self._memory_connection = sqlite3.connect(':memory:') if path == ':memory:' else None
        self._init_db()

    def _connect(self):
        connection = self._memory_connection or sqlite3.connect(self.path)
        connection.row_factory = sqlite3.Row
        return connection

    def _init_db(self):
        with self._connect() as db:
            db.execute("""CREATE TABLE IF NOT EXISTS agent_runs (
                chat_id TEXT NOT NULL, message_thread_id TEXT NOT NULL,
                run_id TEXT NOT NULL, state TEXT NOT NULL, stage TEXT NOT NULL,
                heartbeat_at REAL NOT NULL, last_seen REAL NOT NULL,
                PRIMARY KEY (chat_id, message_thread_id)
            )""")
            db.execute("""CREATE TABLE IF NOT EXISTS topic_sessions (
                chat_id TEXT NOT NULL, message_thread_id TEXT NOT NULL,
                mode TEXT NOT NULL, updated_at REAL NOT NULL,
                PRIMARY KEY (chat_id, message_thread_id)
            )""")

    @staticmethod
    def _key(chat_id, message_thread_id):
        return str(chat_id), str(message_thread_id) if message_thread_id is not None else ""

    def get(self, chat_id, message_thread_id="") -> RunState | None:
        chat_id, message_thread_id = self._key(chat_id, message_thread_id)
        with self._connect() as db:
            row = db.execute("SELECT * FROM agent_runs WHERE chat_id=? AND message_thread_id=?", (chat_id, message_thread_id)).fetchone()
        return RunState(**dict(row)) if row else None

    def get_mode(self, chat_id, message_thread_id="", default="staging") -> str:
        chat_id, message_thread_id = self._key(chat_id, message_thread_id)
        with self._connect() as db:
            row = db.execute(
                "SELECT mode FROM topic_sessions WHERE chat_id=? AND message_thread_id=?",
                (chat_id, message_thread_id),
            ).fetchone()
        return str(row["mode"]) if row else default

    def set_mode(self, chat_id, message_thread_id, mode: str) -> None:
        chat_id, message_thread_id = self._key(chat_id, message_thread_id)
        with self._connect() as db:
            db.execute(
                "INSERT OR REPLACE INTO topic_sessions VALUES (?,?,?,?)",
                (chat_id, message_thread_id, mode, self.clock()),
            )

    def start(self, chat_id, message_thread_id="", run_id: str | None = None, stage: str = "starting") -> RunState:
        chat_id, message_thread_id = self._key(chat_id, message_thread_id)
        if not chat_id or chat_id == "None":
            raise ValueError("chat_id is required")
        now = self.clock()
        run_id = run_id or uuid.uuid4().hex
        if len(run_id) > 96 or any(ch.isspace() for ch in run_id):
            raise ValueError("invalid run_id")
        with self._connect() as db:
            db.execute("INSERT OR REPLACE INTO agent_runs VALUES (?,?,?,?,?,?,?)",
                       (chat_id, message_thread_id, run_id, "STARTED", str(stage)[:80], -1, now))
        return self.get(chat_id, message_thread_id)

    def heartbeat(self, chat_id, message_thread_id, run_id: str, stage: str | None = None) -> RunState:
        current = self.get(chat_id, message_thread_id)
        if not current or current.run_id != run_id:
            raise ValueError("run not found for this topic")
        if current.state in TERMINAL_STATES:
            raise ValueError("run is terminal")
        now = self.clock()
        next_state = "RUNNING" if current.state == "STARTED" else current.state
        self._update(chat_id, message_thread_id, run_id, next_state, stage or current.stage, now, heartbeat=True)
        return self.get(chat_id, message_thread_id)

    def transition(self, chat_id, message_thread_id, run_id: str, state: str, stage: str | None = None) -> RunState:
        state = state.upper()
        if state not in STATES:
            raise ValueError("invalid run state")
        current = self.get(chat_id, message_thread_id)
        if not current or current.run_id != run_id:
            raise ValueError("run not found for this topic")
        if state not in _TRANSITIONS[current.state]:
            raise ValueError(f"invalid transition {current.state}->{state}")
        now = self.clock()
        self._update(chat_id, message_thread_id, run_id, state, stage or current.stage, now, heartbeat=False)
        return self.get(chat_id, message_thread_id)

    def milestone(self, chat_id, message_thread_id, run_id: str, state: str | None = None,
                  stage: str | None = None) -> RunState:
        """Record progress for this exact topic and refresh its heartbeat."""
        current = self.get(chat_id, message_thread_id)
        if not current or current.run_id != run_id:
            raise ValueError("run not found for this topic")
        if state is not None:
            current = self.transition(chat_id, message_thread_id, run_id, state, stage)
        elif stage is not None:
            current = self.heartbeat(chat_id, message_thread_id, run_id, stage)
        if current.state not in TERMINAL_STATES:
            current = self.heartbeat(chat_id, message_thread_id, run_id, stage)
        return current

    def _update(self, chat_id, message_thread_id, run_id, state, stage, now, *, heartbeat: bool):
        chat_id, message_thread_id = self._key(chat_id, message_thread_id)
        with self._connect() as db:
            if heartbeat:
                db.execute("UPDATE agent_runs SET state=?, stage=?, heartbeat_at=?, last_seen=? WHERE chat_id=? AND message_thread_id=? AND run_id=?",
                           (state, str(stage)[:80], now, now, chat_id, message_thread_id, run_id))
            else:
                db.execute("UPDATE agent_runs SET state=?, stage=?, last_seen=? WHERE chat_id=? AND message_thread_id=? AND run_id=?",
                           (state, str(stage)[:80], now, chat_id, message_thread_id, run_id))

    def describe(self, chat_id, message_thread_id="", now=None) -> dict:
        current = self.get(chat_id, message_thread_id)
        if not current:
            return {"active": False, "state": None, "run_id": None, "stage": None, "last_seen": None}
        active = current.is_active(self.clock() if now is None else now, self.heartbeat_timeout)
        return {"active": active, "state": current.state, "run_id": current.run_id,
                "stage": current.stage, "last_seen": current.last_seen}

    @staticmethod
    def format_status(details: dict) -> str:
        if not details["active"]:
            if details["state"] and details["state"] not in TERMINAL_STATES:
                return f"Worker status: no active worker (heartbeat stale)\nRun state: {details['state']}"
            if details["state"]:
                return f"Worker status: {details['state']} (terminal)\nRun state: {details['state']}"
            return "Worker status: not linked; no active worker"
        return f"Worker status: ACTIVE\nRun state: {details['state']}\nStage: {details['stage']}\nRun ID: {details['run_id']}"


class TelegramRunStatusBridge:
    """Post a run update only to an explicitly configured Telegram topic.

    This is a transport helper, not an executor. It never discovers a chat or
    falls back to a global chat when the topic binding is absent.
    """

    def __init__(self, token: str, chat_id: str, message_thread_id: str = "", timeout: int = 5, sender=None):
        self.token = token or ""
        self.chat_id = str(chat_id or "")
        self.message_thread_id = str(message_thread_id or "")
        self.timeout = int(timeout)
        self.sender = sender or self._send

    @property
    def configured(self) -> bool:
        return bool(self.token and self.chat_id and self.message_thread_id)

    def post(self, text: str) -> bool:
        if not self.configured:
            return False
        payload = {
            "chat_id": self.chat_id,
            "message_thread_id": self.message_thread_id,
            "text": str(text),
            "disable_web_page_preview": True,
        }
        try:
            self.sender(payload)
        except Exception:
            # Telegram transport is advisory: a transient staging delivery
            # failure must not suppress the authoritative in-topic reply.
            return False
        return True

    def _send(self, payload: dict) -> None:
        req = request.Request(
            f"https://api.telegram.org/bot{self.token}/sendMessage",
            data=json.dumps(payload).encode(),
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        with request.urlopen(req, timeout=self.timeout) as response:
            if response.status >= 300:
                raise RuntimeError(f"telegram status {response.status}")
