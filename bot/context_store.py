"""Small, bounded, restart-persistent conversation store backed by SQLite."""
from __future__ import annotations

import json
import os
import sqlite3
import time
from pathlib import Path
from typing import Any


_ALLOWED_RESULT_KEYS = {"id", "nomor_pendaftaran", "nama"}
_ALLOWED_FILTER_KEYS = {
    "query", "identifier", "city", "district", "school", "document_status",
    "verification_status", "tahun_ajaran", "jenis_pendaftaran", "kelas_tujuan",
    "gender", "tahapan", "status_kuota", "page", "limit",
}
_STATE_KEYS = ("intent", "filters", "page", "last_page", "last_action", "last_results")


class ContextStore:
    def __init__(self, db_path: str, ttl_seconds: int = 86400):
        self.db_path = str(db_path)
        self.ttl_seconds = max(1, int(ttl_seconds))
        self._ensure_schema()

    def _connect(self):
        if self.db_path != ":memory:":
            Path(self.db_path).expanduser().parent.mkdir(parents=True, exist_ok=True)
        return sqlite3.connect(self.db_path)

    @staticmethod
    def _schema(db) -> None:
        db.execute(
            "CREATE TABLE IF NOT EXISTS conversation_context ("
            "chat_id TEXT NOT NULL, user_id TEXT NOT NULL, updated_at REAL NOT NULL, "
            "state TEXT NOT NULL, PRIMARY KEY (chat_id, user_id))"
        )

    def _ensure_schema(self):
        try:
            with self._connect() as db:
                self._schema(db)
        except (sqlite3.DatabaseError, OSError):
            if self.db_path == ":memory:":
                return
            try:
                os.replace(self.db_path, self.db_path + ".malformed")
                with self._connect() as db:
                    self._schema(db)
            except (sqlite3.DatabaseError, OSError):
                # A corrupt/unwritable local cache must never stop the bot.
                return

    @staticmethod
    def _safe_state(state: Any) -> dict:
        if not isinstance(state, dict):
            return {}
        clean: dict[str, Any] = {}
        intent = state.get("intent")
        def safe_filters(value: Any) -> dict:
            if not isinstance(value, dict):
                return {}
            return {
                str(k): v for k, v in value.items()
                if isinstance(k, str) and k in _ALLOWED_FILTER_KEYS
                and isinstance(v, (str, int, float, bool))
            }

        if isinstance(intent, dict) and isinstance(intent.get("action"), str):
            clean["intent"] = {"action": intent["action"], "filters": safe_filters(intent.get("filters"))}
        filters = state.get("filters")
        clean["filters"] = safe_filters(filters)
        page = state.get("page", 1)
        last_page = state.get("last_page")
        clean["page"] = max(1, int(page)) if isinstance(page, (int, float)) and not isinstance(page, bool) else 1
        clean["last_page"] = max(1, int(last_page)) if isinstance(last_page, (int, float)) and not isinstance(last_page, bool) else None
        clean["last_action"] = state.get("last_action") if isinstance(state.get("last_action"), str) else None
        result = state.get("last_results", [])
        bounded = []
        if isinstance(result, list):
            for item in result[:100]:
                if isinstance(item, dict):
                    safe = {k: item[k] for k in _ALLOWED_RESULT_KEYS if k in item and isinstance(item[k], (str, int, float))}
                    if safe:
                        bounded.append(safe)
        clean["last_results"] = bounded
        return clean

    def get(self, chat_id: str, user_id: str) -> dict:
        try:
            with self._connect() as db:
                row = db.execute(
                    "SELECT updated_at, state FROM conversation_context WHERE chat_id=? AND user_id=?",
                    (str(chat_id), str(user_id)),
                ).fetchone()
            if not row:
                return {}
            if time.time() - float(row[0]) > self.ttl_seconds:
                self.clear(chat_id, user_id)
                return {}
            try:
                state = json.loads(row[1])
            except (ValueError, TypeError, json.JSONDecodeError):
                self.clear(chat_id, user_id)
                return {}
            return self._safe_state(state)
        except (sqlite3.DatabaseError, OSError, TypeError, ValueError):
            return {}

    def set(self, chat_id: str, user_id: str, state: dict) -> None:
        try:
            payload = json.dumps(self._safe_state(state), ensure_ascii=False, separators=(",", ":"))
            with self._connect() as db:
                db.execute(
                    "INSERT INTO conversation_context(chat_id,user_id,updated_at,state) VALUES(?,?,?,?) "
                    "ON CONFLICT(chat_id,user_id) DO UPDATE SET updated_at=excluded.updated_at,state=excluded.state",
                    (str(chat_id), str(user_id), time.time(), payload),
                )
        except (sqlite3.DatabaseError, OSError, TypeError, ValueError):
            return

    def clear(self, chat_id: str, user_id: str) -> None:
        try:
            with self._connect() as db:
                db.execute("DELETE FROM conversation_context WHERE chat_id=? AND user_id=?", (str(chat_id), str(user_id)))
        except (sqlite3.DatabaseError, OSError):
            return

    def prune_expired(self) -> int:
        try:
            with self._connect() as db:
                cur = db.execute("DELETE FROM conversation_context WHERE updated_at < ?", (time.time() - self.ttl_seconds,))
                return max(0, cur.rowcount)
        except (sqlite3.DatabaseError, OSError):
            return 0

    def prune(self) -> int:
        return self.prune_expired()
