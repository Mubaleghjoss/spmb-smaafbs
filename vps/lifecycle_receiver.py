#!/usr/bin/env python3
"""Minimal VPS receiver for signed SPMB lifecycle webhooks."""
from __future__ import annotations

import hashlib
import hmac
import json
import os
import sqlite3
import time
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.parse import urlencode
from urllib.request import Request, urlopen

ALLOWED_EVENT_TYPES = frozenset({
    "applicant_registered",
    "stage_advanced",
    "quota_status_changed",
    "graduation_status_changed",
    # Existing application lifecycle events remain supported for compatibility.
    "account_created",
    "biodata_completed",
    "parent_data_completed",
    "school_origin_completed",
    "registration_completed",
    "documents_submitted",
    "verification_changed",
})


def verify_signature(body: bytes, timestamp: str, signature: str, secret: str, now: int | None = None, max_age: int = 300) -> bool:
    if not secret or not signature:
        return False
    try:
        ts = int(timestamp)
    except (TypeError, ValueError):
        return False
    current = int(time.time()) if now is None else now
    if ts > current or current - ts > max_age:
        return False
    expected = "sha256=" + hmac.new(secret.encode(), timestamp.encode() + b"." + body, hashlib.sha256).hexdigest()
    return hmac.compare_digest(expected, signature)


class DedupeStore:
    def __init__(self, path: str):
        self.db = sqlite3.connect(path)
        self.db.execute("CREATE TABLE IF NOT EXISTS received_events (dedupe_key TEXT PRIMARY KEY, received_at INTEGER NOT NULL)")
        self.db.commit()

    def claim(self, key: str) -> bool:
        try:
            self.db.execute("INSERT INTO received_events VALUES (?, ?)", (key, int(time.time())))
            self.db.commit()
            return True
        except sqlite3.IntegrityError:
            return False


class TelegramNotifier:
    def __init__(self, token: str, chat_id: str, timeout: int = 5):
        self.token, self.chat_id, self.timeout = token, chat_id, timeout

    def send(self, payload: dict) -> None:
        text = format_message(payload)
        data = urlencode({"chat_id": self.chat_id, "text": text, "disable_web_page_preview": "true"}).encode()
        request = Request(f"https://api.telegram.org/bot{self.token}/sendMessage", data=data, method="POST")
        with urlopen(request, timeout=self.timeout) as response:
            if response.status >= 300:
                raise RuntimeError(f"telegram status {response.status}")


def format_message(payload: dict) -> str:
    event = payload.get("event_type", "unknown")
    lines = [f"SPMB: {event}", f"Pendaftar: {payload.get('name', '-')}", f"No. pendaftaran: {payload.get('registration_number', '-')}", f"Tahun ajaran: {payload.get('academic_year', '-')}"]
    for key in ("stage_name", "old_stage", "new_stage", "progress", "cause", "old_status", "new_status"):
        if key in payload:
            lines.append(f"{key}: {payload[key]}")
    return "\n".join(lines)


def process(body: bytes, headers: dict[str, str], secret: str, store: DedupeStore, notifier: TelegramNotifier, now: int | None = None, max_age: int = 300) -> tuple[int, str]:
    normalized_headers = {key.lower(): value for key, value in headers.items()}
    if not verify_signature(body, normalized_headers.get("x-spmb-webhook-timestamp", ""), normalized_headers.get("x-spmb-webhook-signature", ""), secret, now, max_age):
        return 401, "invalid signature"
    try:
        payload = json.loads(body)
        if not isinstance(payload, dict) or payload.get("event_type") not in ALLOWED_EVENT_TYPES:
            return 400, "invalid event type"
        key = payload["dedupe_key"]
        if not isinstance(key, str) or not key:
            raise ValueError("missing dedupe_key")
    except (ValueError, KeyError, json.JSONDecodeError, TypeError):
        return 400, "invalid payload"
    if not store.claim(key):
        return 200, "duplicate"
    try:
        notifier.send(payload)
    except Exception:
        # Delivery failure is isolated from the producer; retain the claim to avoid storms.
        return 202, "accepted; notification failed"
    return 202, "accepted"


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):  # noqa: N802
        if self.path != "/webhook/lifecycle":
            self.send_error(404)
            return
        body = self.rfile.read(int(self.headers.get("Content-Length", "0")))
        status, message = process(body, dict(self.headers.items()), os.environ["SPMB_WEBHOOK_SECRET"], DedupeStore(os.environ.get("SPMB_DEDUPE_DB", "lifecycle-events.sqlite3")), TelegramNotifier(os.environ["SPMB_TELEGRAM_BOT_TOKEN"], os.environ["SPMB_TELEGRAM_CHAT_ID"]))
        self.send_response(status)
        self.send_header("Content-Type", "text/plain; charset=utf-8")
        self.end_headers()
        self.wfile.write(message.encode())


if __name__ == "__main__":
    HTTPServer((os.environ.get("SPMB_BIND_HOST", "127.0.0.1"), int(os.environ.get("SPMB_BIND_PORT", "8090"))), Handler).serve_forever()
