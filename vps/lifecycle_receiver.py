#!/usr/bin/env python3
"""Signed SPMB lifecycle webhook receiver and durable Telegram delivery worker."""
from __future__ import annotations

import hashlib
import hmac
import json
import logging
import os
import sqlite3
import time
from datetime import datetime, timezone
from http.server import BaseHTTPRequestHandler, HTTPServer
from urllib.request import Request, urlopen
from zoneinfo import ZoneInfo

ALLOWED_EVENT_TYPES = frozenset({
    "applicant_registered", "stage_advanced", "quota_status_changed", "graduation_status_changed",
    "biodata_completed", "parent_data_completed", "school_origin_completed", "registration_completed",
    "documents_submitted", "verification_changed", "account_created",
})
RETRY_DELAYS = (30, 60, 120, 300)
MAX_ATTEMPTS = 10
STALE_AFTER = 300
LOGGER = logging.getLogger(__name__)
_PRIVATE_KEYS = {"phone", "email", "address", "parent", "parent_data", "parent_name", "parent_phone", "parent_email", "token", "secret", "password", "authorization", "auth", "headers"}


def sanitize_payload(value):
    if isinstance(value, dict):
        return {k: sanitize_payload(v) for k, v in value.items() if str(k).lower() not in _PRIVATE_KEYS and not any(part in str(k).lower() for part in ("token", "secret", "password"))}
    if isinstance(value, list):
        return [sanitize_payload(item) for item in value]
    return value


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
        self.db = sqlite3.connect(path, timeout=10, check_same_thread=False)
        self.db.execute("PRAGMA busy_timeout=10000")
        self.db.execute("CREATE TABLE IF NOT EXISTS received_events (dedupe_key TEXT PRIMARY KEY, received_at INTEGER NOT NULL, status TEXT NOT NULL DEFAULT 'pending')")
        columns = {row[1] for row in self.db.execute("PRAGMA table_info(received_events)")}
        additions = {
            "payload": "TEXT", "attempts": "INTEGER NOT NULL DEFAULT 0", "next_attempt_at": "INTEGER",
            "last_error_at": "INTEGER", "processing_started_at": "INTEGER", "delivered_at": "INTEGER",
        }
        if "status" not in columns:
            self.db.execute("ALTER TABLE received_events ADD COLUMN status TEXT NOT NULL DEFAULT 'delivered'")
        for name, definition in additions.items():
            if name not in columns:
                self.db.execute(f"ALTER TABLE received_events ADD COLUMN {name} {definition}")
        self.db.commit()

    def reserve(self, key: str, payload: dict | None = None, is_test: bool = False, now: int | None = None) -> bool:
        at = int(time.time()) if now is None else now
        status = "suppressed" if is_test else "pending"
        encoded = json.dumps(payload, ensure_ascii=False, separators=(",", ":")) if payload is not None else None
        try:
            self.db.execute("INSERT INTO received_events (dedupe_key, received_at, status, payload, next_attempt_at) VALUES (?, ?, ?, ?, ?)", (key, at, status, encoded, at))
            self.db.commit()
            return True
        except sqlite3.IntegrityError:
            return False

    claim = reserve

    def claim_next(self, now: int | None = None) -> tuple[str, dict, int] | None:
        now = int(time.time()) if now is None else now
        self.reclaim_stale(now)
        self.db.execute("BEGIN IMMEDIATE")
        row = self.db.execute("SELECT dedupe_key, payload, attempts FROM received_events WHERE status='pending' AND (next_attempt_at IS NULL OR next_attempt_at<=?) ORDER BY received_at, dedupe_key LIMIT 1", (now,)).fetchone()
        if not row:
            self.db.commit()
            return None
        self.db.execute("UPDATE received_events SET status='processing', processing_started_at=?, attempts=attempts+1 WHERE dedupe_key=?", (now, row[0]))
        self.db.commit()
        return row[0], json.loads(row[1]), int(row[2]) + 1

    def mark_delivered(self, key: str, now: int | None = None) -> None:
        self.db.execute("UPDATE received_events SET status='delivered', delivered_at=?, processing_started_at=NULL WHERE dedupe_key=?", (int(time.time()) if now is None else now, key))
        self.db.commit()

    def schedule_retry(self, key: str, error: str, now: int | None = None) -> str:
        now = int(time.time()) if now is None else now
        row = self.db.execute("SELECT attempts FROM received_events WHERE dedupe_key=?", (key,)).fetchone()
        attempts = int(row[0]) if row else MAX_ATTEMPTS
        if attempts >= MAX_ATTEMPTS:
            status = "dead"
            next_at = None
        else:
            status = "pending"
            next_at = now + min(RETRY_DELAYS[min(attempts - 1, len(RETRY_DELAYS) - 1)], 900)
        self.db.execute("UPDATE received_events SET status=?, next_attempt_at=?, last_error_at=?, processing_started_at=NULL WHERE dedupe_key=?", (status, next_at, now, key))
        self.db.commit()
        return status

    def reclaim_stale(self, now: int | None = None) -> int:
        now = int(time.time()) if now is None else now
        cur = self.db.execute("UPDATE received_events SET status='pending', processing_started_at=NULL, next_attempt_at=? WHERE status='processing' AND processing_started_at<?", (now, now - STALE_AFTER))
        self.db.commit()
        return cur.rowcount

    def release(self, key: str) -> None:
        self.schedule_retry(key, "released")


class TelegramNotifier:
    def __init__(self, token: str, chat_id: str, timeout: int = 5):
        self.token, self.chat_id, self.timeout = token, chat_id, timeout

    def send(self, payload: dict) -> None:
        data = urlencode({"chat_id": self.chat_id, "text": format_message(payload), "disable_web_page_preview": "true"}).encode()
        request = Request(f"https://api.telegram.org/bot{self.token}/sendMessage", data=data, method="POST")
        with urlopen(request, timeout=self.timeout) as response:
            if response.status >= 300:
                raise RuntimeError(f"telegram status {response.status}")


def _label(value, mapping):
    return mapping.get(str(value).lower(), value if value not in (None, "") else "-")


def _wib(value):
    if not value:
        return "-"
    try:
        text = str(value).replace("Z", "+00:00")
        dt = datetime.fromisoformat(text)
        if dt.tzinfo is None:
            dt = dt.replace(tzinfo=timezone.utc)
        return dt.astimezone(ZoneInfo("Asia/Jakarta")).strftime("%d-%m-%Y %H:%M WIB")
    except (TypeError, ValueError):
        return "-"


def format_message(payload: dict) -> str:
    jalur = {"regular": "Reguler", "reguler": "Reguler", "prestasi": "Prestasi", "afirmasi": "Afirmasi", "zonasi": "Zonasi", "siswa_baru": "Siswa Baru", "pindahan": "Siswa Pindahan"}
    gender = {"male": "Laki-laki", "m": "Laki-laki", "l": "Laki-laki", "female": "Perempuan", "f": "Perempuan", "p": "Perempuan"}
    status = {"pending": "Menunggu", "verified": "Terverifikasi", "approved": "Disetujui", "rejected": "Ditolak", "active": "Aktif", "dalam_kuota": "Dalam Kuota", "waiting_list": "Waiting List", "belum_lengkap": "Belum Lengkap"}
    lines = [f"SPMB: {payload.get('event_type', '-')}", f"Pendaftar: {payload.get('name', '-')}", f"No. pendaftaran: {payload.get('registration_number', '-')}", f"Tahun ajaran: {payload.get('academic_year', '-')}"]
    for key, label, mapping in (("jalur", "Jalur", jalur), ("gender", "Jenis kelamin", gender), ("status", "Status", status), ("status_kuota", "Status kuota", status), ("old_status", "Status lama", status), ("new_status", "Status baru", status)):
        if key in payload: lines.append(f"{label}: {_label(payload[key], mapping)}")
    for key, label in (("kelas_tujuan", "Kelas tujuan"), ("asal_sekolah", "Asal sekolah"), ("city", "Kota"), ("kota", "Kota"), ("stage_name", "Tahap"), ("old_stage", "Tahap lama"), ("new_stage", "Tahap baru"), ("progress", "Progres"), ("cause", "Sebab")):
        if key in payload: lines.append(f"{label}: {payload[key]}")
    if "occurred_at" in payload: lines.append(f"Waktu: {_wib(payload['occurred_at'])}")
    return "\n".join(lines)


class ReadApiEnricher:
    def __init__(self, base_url: str | None = None, token: str | None = None, timeout: int = 5):
        self.base_url = (base_url or os.environ.get("SPMB_READ_API_URL", "http://127.0.0.1:18084/api/v1")).rstrip("/")
        self.token, self.timeout = token if token is not None else os.environ.get("SPMB_DATA_BOT_TOKEN"), timeout

    def enrich(self, payload: dict) -> dict:
        if payload.get("event_type") != "applicant_registered" or not payload.get("registration_number"):
            return payload
        body = json.dumps({"action": "get_applicant_detail", "filters": {"identifier": payload["registration_number"]}}, ensure_ascii=False, separators=(",", ":")).encode()
        headers = {"Accept": "application/json", "Content-Type": "application/json"}
        if self.token: headers["Authorization"] = f"Bearer {self.token}"
        request = Request(self.base_url, data=body, headers=headers, method="POST")
        try:
            with urlopen(request, timeout=self.timeout) as response:
                data = json.loads(response.read())
            if isinstance(data, dict):
                extra = data.get("data", data)
                if isinstance(extra, dict):
                    return {**payload, **sanitize_payload(extra)}
        except Exception:
            LOGGER.info("applicant enrichment unavailable")
        return payload


class DeliveryWorker:
    def __init__(self, store: DedupeStore, notifier, enricher: ReadApiEnricher | None = None, sleep_fn=time.sleep):
        self.store, self.notifier, self.enricher, self.sleep_fn = store, notifier, enricher or ReadApiEnricher(), sleep_fn

    def run_once(self, now: int | None = None) -> bool:
        item = self.store.claim_next(now)
        if not item: return False
        key, payload, _attempt = item
        try:
            self.notifier.send(self.enricher.enrich(payload))
        except Exception as exc:
            self.store.schedule_retry(key, type(exc).__name__, now)
        else:
            self.store.mark_delivered(key, now)
        return True

    def run_forever(self):
        while True:
            if not self.run_once(): self.sleep_fn(1)


def process(body: bytes, headers: dict[str, str], secret: str, store: DedupeStore, notifier=None, now: int | None = None, max_age: int = 300) -> tuple[int, str]:
    normalized = {k.lower(): v for k, v in headers.items()}
    if not verify_signature(body, normalized.get("x-spmb-webhook-timestamp", ""), normalized.get("x-spmb-webhook-signature", ""), secret, now, max_age): return 401, "invalid signature"
    try:
        payload = json.loads(body)
        if not isinstance(payload, dict) or payload.get("event_type") not in ALLOWED_EVENT_TYPES: return 400, "invalid event type"
        key = payload.get("dedupe_key")
        if not isinstance(key, str) or not key: raise ValueError
        if not isinstance(payload.get("is_test", False), bool): raise ValueError
    except (ValueError, TypeError, json.JSONDecodeError):
        return 400, "invalid payload"
    try:
        if not store.reserve(key, sanitize_payload(payload), payload.get("is_test") is True, now): return 200, "duplicate"
    except sqlite3.Error:
        return 503, "queue unavailable"
    return 202, "accepted"


class Handler(BaseHTTPRequestHandler):
    def do_POST(self):
        if self.path != "/webhook/lifecycle": self.send_error(404); return
        try:
            body = self.rfile.read(int(self.headers.get("Content-Length", "0")))
            status, message = process(body, dict(self.headers.items()), os.environ.get("SPMB_WEBHOOK_SECRET", ""), DedupeStore(os.environ.get("SPMB_DEDUPE_DB", "lifecycle-events.sqlite3")))
        except (OSError, sqlite3.Error, KeyError):
            status, message = 503, "queue unavailable"
        self.send_response(status); self.send_header("Content-Type", "text/plain; charset=utf-8"); self.end_headers(); self.wfile.write(message.encode())


if __name__ == "__main__":
    store = DedupeStore(os.environ.get("SPMB_DEDUPE_DB", "lifecycle-events.sqlite3"))
    if os.environ.get("SPMB_RUN_WORKER") == "1":
        DeliveryWorker(store, TelegramNotifier(os.environ["SPMB_TELEGRAM_BOT_TOKEN"], os.environ["SPMB_TELEGRAM_CHAT_ID"])).run_forever()
    else:
        HTTPServer((os.environ.get("SPMB_BIND_HOST", "127.0.0.1"), int(os.environ.get("SPMB_BIND_PORT", "8090"))), Handler).serve_forever()
