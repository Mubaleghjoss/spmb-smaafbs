"""Small, bounded, restart-persistent conversation store backed by SQLite."""
from __future__ import annotations

import json
import os
import sqlite3
import time
from pathlib import Path
from typing import Any


_ALLOWED_RESULT_KEYS = {"id", "nomor_pendaftaran", "nama"}


class ContextStore:
    def __init__(self, db_path: str, ttl_seconds: int = 86400):
        self.db_path = str(db_path)
        self.ttl_seconds = max(1, int(ttl_seconds))
        self._ensure_schema()

    def _connect(self):
        if self.db_path != ":memory:":
            Path(self.db_path).expanduser().parent.mkdir(parents=True, exist_ok=True)
        return sqlite3.connect(self.db_path)

    def _ensure_schema(self):
        try:
            with self._connect() as db:
                db.execute("CREATE TABLE IF NOT EXISTS conversation_context (chat_id TEXT NOT NULL, user_id TEXT NOT NULL, updated_at REAL NOT NULL, state TEXT NOT NULL, PRIMARY KEY (chat_id, user_id))")
        except (sqlite3.DatabaseError, OSError):
            # A corrupt local cache must never prevent the read-only bot from starting.
            if self.db_path != ":memory:":
                try:
                    os.replace(self.db_path, self.db_path + ".malformed")
                except OSError:
                    pass
                with self._connect() as db:
                    db.execute("CREATE TABLE IF NOT EXISTS conversation_context (chat_id TEXT NOT NULL, user_id TEXT NOT NULL, updated_at REAL NOT NULL, state TEXT NOT NULL, PRIMARY KEY (chat_id, user_id))")

    @staticmethod
    def _safe_state(state: Any) -> dict:
        if not isinstance(state, dict):
            return {}
        result = state.get("last_results", [])
        bounded = []
        if isinstance(result, list):
            for item in result[:100]:
                if isinstance(item, dict):
                    bounded.append({k: item[k] for k in _ALLOWED_RESULT_KEYS if k in item})
        clean = {k: state[k] for k in ("intent", "filters", "page", "last_action") if k in state}
        clean["last_results"] = bounded
        return clean

    def get(self, chat_id: str, user_id: str) -> dict:
        try:
            with self._connect() as db:
                row = db.execute("SELECT updated_at, state FROM conversation_context WHERE chat_id=? AND user_id=?", (str(chat_id), str(user_id))).fetchone()
            if not row:
                return {}
            if time.time() - float(row[0]) > self.ttl_seconds:
                self.clear(chat_id, user_id)
                return {}
            try:
                return self._safe_state(json.loads(row[1]))
            except (ValueError, TypeError, json.JSONDecodeError):
                self.clear(chat_id, user_id)
                return {}
        except sqlite3.DatabaseError:
            return {}

    def set(self, chat_id: str, user_id: str, state: dict) -> None:
        payload = json.dumps(self._safe_state(state), ensure_ascii=False, separators=(",", ":"))
        try:
            with self._connect() as db:
                db.execute("INSERT INTO conversation_context(chat_id,user_id,updated_at,state) VALUES(?,?,?,?) ON CONFLICT(chat_id,user_id) DO UPDATE SET updated_at=excluded.updated_at,state=excluded.state", (str(chat_id), str(user_id), time.time(), payload))
        except sqlite3.DatabaseError:
            return

    def clear(self, chat_id: str, user_id: str) -> None:
        try:
            with self._connect() as db:
                db.execute("DELETE FROM conversation_context WHERE chat_id=? AND user_id=?", (str(chat_id), str(user_id)))
        except sqlite3.DatabaseError:
            return

    def prune(self) -> int:
        try:
            with self._connect() as db:
                cur = db.execute("DELETE FROM conversation_context WHERE updated_at < ?", (time.time() - self.ttl_seconds,))
                return cur.rowcount
        except sqlite3.DatabaseError:
            return 0
