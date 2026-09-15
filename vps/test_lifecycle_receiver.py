import hashlib
import hmac
import json
import tempfile
import unittest
from lifecycle_receiver import DedupeStore, process, verify_signature


class FakeNotifier:
    def __init__(self, fail=False): self.calls, self.fail = [], fail
    def send(self, payload):
        self.calls.append(payload)
        if self.fail: raise RuntimeError("offline")


class LifecycleReceiverTest(unittest.TestCase):
    def setUp(self):
        self.secret, self.now = "secret", 1700000000
        self.payload = {"event_type": "stage_advanced", "dedupe_key": "stable-1", "name": "S*****", "registration_number": "REG-1"}
        self.body = json.dumps(self.payload, separators=(",", ":")).encode()
        self.timestamp = str(self.now)
        self.headers = {"X-SPMB-Webhook-Timestamp": self.timestamp, "X-SPMB-Webhook-Signature": "sha256=" + hmac.new(self.secret.encode(), self.timestamp.encode() + b"." + self.body, hashlib.sha256).hexdigest()}
        self.tmp = tempfile.NamedTemporaryFile()
        self.store = DedupeStore(self.tmp.name)
        self.notifier = FakeNotifier()

    def test_hmac_and_stale_rejection(self):
        self.assertTrue(verify_signature(self.body, self.timestamp, self.headers["X-SPMB-Webhook-Signature"], self.secret, self.now))
        self.assertFalse(verify_signature(self.body, self.timestamp, self.headers["X-SPMB-Webhook-Signature"], self.secret, self.now + 301))
        self.assertEqual(process(self.body, {**self.headers, "X-SPMB-Webhook-Signature": "sha256=bad"}, self.secret, self.store, self.notifier, self.now)[0], 401)
        self.assertFalse(verify_signature(self.body, self.timestamp, self.headers["X-SPMB-Webhook-Signature"], "", self.now))
        self.assertFalse(verify_signature(self.body, str(self.now + 1), self.headers["X-SPMB-Webhook-Signature"], self.secret, self.now))

    def test_unknown_event_is_rejected_before_notification(self):
        payload = {**self.payload, "event_type": "admin_secret_dump"}
        body = json.dumps(payload, separators=(",", ":")).encode()
        timestamp = str(self.now)
        headers = {"x-spmb-webhook-timestamp": timestamp, "x-spmb-webhook-signature": "sha256=" + hmac.new(self.secret.encode(), timestamp.encode() + b"." + body, hashlib.sha256).hexdigest()}
        self.assertEqual(process(body, headers, self.secret, self.store, self.notifier, self.now), (400, "invalid event type"))
        self.assertEqual(self.notifier.calls, [])

    def test_idempotency(self):
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now)[0], 202)
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now)[1], "duplicate")
        self.assertEqual(len(self.notifier.calls), 1)

    def test_retry_after_failure_then_delivered_duplicate(self):
        self.notifier.fail = True
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now), (202, "accepted; notification failed"))

        self.notifier.fail = False
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now), (202, "accepted"))
        self.assertEqual(len(self.notifier.calls), 2)

        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now), (200, "duplicate"))
        self.assertEqual(len(self.notifier.calls), 2)
        self.assertEqual(self.store.db.execute("SELECT status FROM received_events WHERE dedupe_key = ?", ("stable-1",)).fetchone()[0], "delivered")

    def test_notifier_failure_isolated(self):
        self.notifier.fail = True
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now)[0], 202)
        self.assertIsNone(self.store.db.execute("SELECT 1 FROM received_events WHERE dedupe_key = ?", ("stable-1",)).fetchone())


if __name__ == "__main__": unittest.main()
