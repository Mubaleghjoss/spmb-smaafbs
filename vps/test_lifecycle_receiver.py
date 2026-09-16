import hashlib
import hmac
import json
import logging
import sqlite3
import tempfile
import unittest
from unittest.mock import patch


from lifecycle_receiver import (
    ALLOWED_EVENT_TYPES, DedupeStore, DeliveryWorker, ReadApiEnricher,
    format_message, process, verify_signature,
)


class FakeNotifier:
    def __init__(self, fail=False): self.calls, self.fail = [], fail
    def send(self, payload):
        self.calls.append(payload)
        if self.fail: raise RuntimeError("offline")


class LifecycleReceiverTest(unittest.TestCase):
    def setUp(self):
        self.secret, self.now = "secret", 1700000000
        self.payload = {"event_type": "stage_advanced", "dedupe_key": "stable-1", "name": "S*****", "registration_number": "REG-1"}
        self.tmp = tempfile.NamedTemporaryFile()
        self.store = DedupeStore(self.tmp.name)
        self.notifier = FakeNotifier()

    def signed(self, payload=None, timestamp=None):
        body = json.dumps(payload or self.payload, separators=(",", ":")).encode()
        ts = str(self.now if timestamp is None else timestamp)
        return body, {"X-SPMB-Webhook-Timestamp": ts, "X-SPMB-Webhook-Signature": "sha256=" + hmac.new(self.secret.encode(), ts.encode() + b"." + body, hashlib.sha256).hexdigest()}

    def test_hmac_timestamp_and_exact_raw_body(self):
        body, headers = self.signed()
        self.assertTrue(verify_signature(body, headers["X-SPMB-Webhook-Timestamp"], headers["X-SPMB-Webhook-Signature"], self.secret, self.now))
        self.assertFalse(verify_signature(body, str(self.now + 1), headers["X-SPMB-Webhook-Signature"], self.secret, self.now))
        self.assertFalse(verify_signature(body, str(self.now - 301), headers["X-SPMB-Webhook-Signature"], self.secret, self.now))
        self.assertFalse(verify_signature(body, headers["X-SPMB-Webhook-Timestamp"], "sha256=bad", self.secret, self.now))
        self.assertFalse(verify_signature(body, headers["X-SPMB-Webhook-Timestamp"], headers["X-SPMB-Webhook-Signature"], "", self.now))

    def test_validation_and_allowlist(self):
        body, headers = self.signed({**self.payload, "event_type": "admin_secret_dump"})
        self.assertEqual(process(body, headers, self.secret, self.store, now=self.now), (400, "invalid event type"))
        for event in ALLOWED_EVENT_TYPES:
            body, headers = self.signed({**self.payload, "event_type": event, "dedupe_key": event})
            self.assertEqual(process(body, headers, self.secret, self.store, now=self.now)[0], 202)
        body, headers = self.signed({"event_type": "stage_advanced"})
        self.assertEqual(process(body, headers, self.secret, self.store, now=self.now), (400, "invalid payload"))
        self.assertEqual(process(b"{", headers, self.secret, self.store, now=self.now), (401, "invalid signature"))

    def test_enqueue_202_no_notifier_or_api_call_and_dedupe_once(self):
        with patch("lifecycle_receiver.urlopen") as api:
            body, headers = self.signed()
            self.assertEqual(process(body, headers, self.secret, self.store, self.notifier, self.now), (202, "accepted"))
            self.assertEqual(process(body, headers, self.secret, self.store, self.notifier, self.now), (200, "duplicate"))
            api.assert_not_called(); self.assertEqual(self.notifier.calls, [])
        row = self.store.db.execute("SELECT status, payload, attempts FROM received_events WHERE dedupe_key='stable-1'").fetchone()
        self.assertEqual(row[0], "pending"); self.assertIn("stable-1", row[1]); self.assertEqual(row[2], 0)
        self.assertNotIn("TOPSECRET", row[1])
        sensitive_body, sensitive_headers = self.signed({**self.payload, "dedupe_key": "safe", "token": "TOPSECRET", "authorization": "Bearer TOPSECRET"})
        self.assertEqual(process(sensitive_body, sensitive_headers, self.secret, self.store, now=self.now)[0], 202)
        stored = self.store.db.execute("SELECT payload FROM received_events WHERE dedupe_key='safe'").fetchone()[0]
        self.assertNotIn("TOPSECRET", stored); self.assertNotIn("authorization", stored)

    def test_test_suppression_and_duplicate(self):
        body, headers = self.signed({**self.payload, "is_test": True})
        self.assertEqual(process(body, headers, self.secret, self.store, self.notifier, self.now), (202, "accepted"))
        self.assertEqual(process(body, headers, self.secret, self.store, self.notifier, self.now), (200, "duplicate"))
        self.assertEqual(self.store.db.execute("SELECT status FROM received_events WHERE dedupe_key='stable-1'").fetchone()[0], "suppressed")
        self.assertFalse(self.store.claim_next(self.now))

    def test_persistence_and_backward_compatible_schema(self):
        path = self.tmp.name
        body, headers = self.signed()
        self.assertEqual(process(body, headers, self.secret, self.store, now=self.now)[0], 202)
        second = DedupeStore(path)
        self.assertFalse(process(body, headers, self.secret, second, now=self.now)[0] == 202)
        cols = {r[1] for r in second.db.execute("PRAGMA table_info(received_events)")}
        self.assertTrue({"payload", "attempts", "next_attempt_at", "last_error_at", "processing_started_at"} <= cols)

    def test_worker_delivery_and_no_redelivery(self):
        body, headers = self.signed()
        process(body, headers, self.secret, self.store, now=self.now)
        worker = DeliveryWorker(self.store, self.notifier)
        self.assertTrue(worker.run_once(self.now)); self.assertEqual(len(self.notifier.calls), 1)
        self.assertFalse(worker.run_once(self.now + 1)); self.assertEqual(self.store.db.execute("SELECT status FROM received_events WHERE dedupe_key='stable-1'").fetchone()[0], "delivered")

    def test_retry_schedule_bounded_and_dead(self):
        body, headers = self.signed(); process(body, headers, self.secret, self.store, now=self.now)
        failing = FakeNotifier(True); worker = DeliveryWorker(self.store, failing)
        for i in range(10):
            self.assertTrue(worker.run_once(self.now + i * 1000))
            if i < 9:
                self.store.db.execute("UPDATE received_events SET next_attempt_at=? WHERE dedupe_key='stable-1'", (self.now + i * 1000,)); self.store.db.commit()
        row = self.store.db.execute("SELECT status, attempts, next_attempt_at FROM received_events WHERE dedupe_key='stable-1'").fetchone()
        self.assertEqual((row[0], row[1]), ("dead", 10)); self.assertIsNone(row[2])

    def test_stale_processing_reclaimed(self):
        body, headers = self.signed(); process(body, headers, self.secret, self.store, now=self.now)
        self.assertEqual(self.store.claim_next(self.now)[0], "stable-1")
        self.assertEqual(self.store.reclaim_stale(self.now + 301), 1)
        self.assertEqual(self.store.db.execute("SELECT status FROM received_events WHERE dedupe_key='stable-1'").fetchone()[0], "pending")

    def test_enrichment_success_failure_auth_and_no_token_logs(self):
        payload = {"event_type": "applicant_registered", "dedupe_key": "r", "registration_number": "REG-9"}
        class Response:
            def __enter__(self): return self
            def __exit__(self, *a): pass
            def read(self): return json.dumps({"data": {"name": "Ani", "gender": "female", "email": "bad", "address": "bad"}}).encode()
        with patch("lifecycle_receiver.urlopen", return_value=Response()) as opened:
            result = ReadApiEnricher("http://read/api", "TOPSECRET").enrich(payload)
        request = opened.call_args.args[0]
        self.assertEqual(request.method, "POST")
        self.assertEqual(json.loads(request.data), {"action": "get_applicant_detail", "filters": {"identifier": "REG-9"}})
        self.assertEqual(request.get_header("Content-type"), "application/json")
        self.assertEqual(request.get_header("Authorization"), "Bearer TOPSECRET")
        self.assertEqual(result["name"], "Ani"); self.assertNotIn("email", result); self.assertNotIn("address", result)
        with self.assertLogs("lifecycle_receiver", level=logging.INFO) as logs:
            with patch("lifecycle_receiver.urlopen", side_effect=OSError("TOPSECRET")):
                self.assertEqual(ReadApiEnricher("http://read/api", "TOPSECRET").enrich(payload), payload)
        self.assertNotIn("TOPSECRET", "".join(logs.output))
        with patch("lifecycle_receiver.urlopen", return_value=Response()) as opened:
            ReadApiEnricher("http://read/api", "").enrich(payload)
        self.assertIsNone(opened.call_args.args[0].get_header("Authorization"))

    def test_formatter_labels_wib_and_privacy(self):
        text = format_message({"event_type": "applicant_registered", "name": "Ani", "registration_number": "R", "academic_year": "2026", "jalur": "siswa_baru", "gender": "L", "status_kuota": "dalam_kuota", "occurred_at": "2026-01-01T00:00:00Z", "kelas_tujuan": 10, "asal_sekolah": "SMP 1", "kota": "Bandung", "phone": "081", "email": "x@y", "address": "secret", "parent": "secret"})
        self.assertIn("Jalur: Siswa Baru", text); self.assertIn("Jenis kelamin: Laki-laki", text); self.assertIn("Status kuota: Dalam Kuota", text); self.assertIn("Kelas tujuan: 10", text); self.assertIn("Asal sekolah: SMP 1", text); self.assertIn("Kota: Bandung", text); self.assertIn("07:00 WIB", text)
        for value in ("081", "x@y", "secret"): self.assertNotIn(value, text)
        self.assertEqual(format_message({"event_type": "x", "occurred_at": "bad"}).splitlines()[-1], "Waktu: -")

    def test_formatter_canonical_alias_mappings(self):
        text = format_message({
            "event_type": "quota_status_changed", "jalur": "pindahan", "gender": "P",
            "status_kuota": "waiting_list", "old_status": "belum_lengkap",
        })
        self.assertIn("Jalur: Siswa Pindahan", text)
        self.assertIn("Jenis kelamin: Perempuan", text)
        self.assertIn("Status kuota: Waiting List", text)
        self.assertIn("Status lama: Belum Lengkap", text)

    def test_malformed_sqlite_fails_safe(self):
        with tempfile.TemporaryDirectory() as directory:
            with self.assertRaises(sqlite3.Error): DedupeStore(directory)


if __name__ == "__main__": unittest.main()
