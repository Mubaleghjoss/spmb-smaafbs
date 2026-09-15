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

    def test_idempotency(self):
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now)[0], 202)
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now)[1], "duplicate")
        self.assertEqual(len(self.notifier.calls), 1)

    def test_notifier_failure_isolated(self):
        self.notifier.fail = True
        self.assertEqual(process(self.body, self.headers, self.secret, self.store, self.notifier, self.now)[0], 202)


if __name__ == "__main__": unittest.main()
