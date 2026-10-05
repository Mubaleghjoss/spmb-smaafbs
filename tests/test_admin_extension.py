import logging
import os
import tempfile
import unittest
from pathlib import Path
from unittest.mock import patch

from bot.config import Config
from bot.formatter import format_response
from bot.handler import MessageHandler
from bot.parser import normalize_tahapan, parse_deterministic, parse_number_range, validate_intent


class FakeApi:
    def __init__(self):
        self.calls = []

    def query(self, intent, user):
        self.calls.append((intent, user))
        return {'status': 'success', 'data': {
            'nama': 'Applicant', 'verification_status': 'menunggu',
            'stage_label': 'Tes Online', 'status_kuota': 'dalam_kuota',
            'document_status': 'incomplete', 'kind': 'payment',
            'status': 'found', 'available': True,
        }}


def message(text, user='7', chat_type='private', chat_id='77'):
    return {'text': text, 'from': {'id': user}, 'chat': {'type': chat_type, 'id': chat_id}}


class AdminExtensionTests(unittest.TestCase):
    def setUp(self):
        self.context_db = tempfile.NamedTemporaryFile(suffix='.sqlite3', delete=False)
        self.context_db.close()
        self.config = Config(
            allowed_group_id='official',
            allowed_telegram_user_ids=('7',),
            allowed_admin_chat_ids=('77',),
            admin_extension_enabled=True,
            context_db_path=self.context_db.name,
        )
        self.api = FakeApi()
        self.bot = MessageHandler(self.config, self.api, ai_parser=lambda *_: None)

    def tearDown(self):
        Path(self.context_db.name).unlink(missing_ok=True)

    def test_from_env_keeps_admin_extension_disabled_when_unconfigured(self):
        base = {
            'APP_ENV': 'staging',
            'SPMB_ADMIN_EXTENSION_ENABLED': 'true',
            'ALLOWED_TELEGRAM_USER_IDS': '7',
        }
        with patch.dict(os.environ, base, clear=True):
            config = Config.from_env()
        self.assertFalse(config.admin_extension_enabled)

    def test_from_env_enables_only_staging_with_both_allowlists(self):
        enabled = {
            'APP_ENV': 'staging',
            'SPMB_ADMIN_EXTENSION_ENABLED': 'true',
            'ALLOWED_TELEGRAM_USER_IDS': '7',
            'ALLOWED_ADMIN_CHAT_IDS': '77',
        }
        with patch.dict(os.environ, enabled, clear=True):
            self.assertTrue(Config.from_env().admin_extension_enabled)
        production = dict(enabled, APP_ENV='production')
        with patch.dict(os.environ, production, clear=True):
            self.assertFalse(Config.from_env().admin_extension_enabled)

    def test_disabled_admin_returns_safe_message(self):
        config = Config(
            allowed_group_id='official',
            allowed_telegram_user_ids=('7',),
            allowed_admin_chat_ids=('77',),
            admin_extension_enabled=False,
            context_db_path=self.context_db.name,
        )
        bot = MessageHandler(config, FakeApi(), ai_parser=lambda *_: None)
        self.assertEqual(bot.handle_message(message('/status')), 'Fitur admin staging belum diaktifkan.')

    def test_admin_commands_are_deterministic_and_private_authorized(self):
        status = self.bot.handle_message(message('/status'))
        self.assertIn('Stage: staging-only', status)
        self.assertIn('Blocker:', status)
        self.assertEqual(self.api.calls, [])
        self.assertEqual(parse_deterministic('/progress REG-1')['action'], 'get_applicant_progress')
        self.assertEqual(parse_deterministic('/bukti-bayar REG-1 formulir')['filters']['proof_type'], 'formulir')
        self.assertEqual(parse_deterministic('/bukti-dokumen REG-1 kk')['action'], 'lookup_document_proof')
        response = self.bot.handle_message(message('/progress REG-1'))
        self.assertIn('Progress', response)
        self.assertEqual(len(self.api.calls), 1)

    def test_group_admin_command_denied_before_api_call(self):
        response = self.bot.handle_message(message('/progress REG-1', chat_type='group', chat_id='official'))
        self.assertIn('chat pribadi', response)
        self.assertEqual(self.api.calls, [])

    def test_wrong_private_admin_chat_denied_before_api_call(self):
        response = self.bot.handle_message(message('/progress REG-1', chat_id='not-allowlisted'))
        self.assertIn('tidak berwenang', response)
        self.assertEqual(self.api.calls, [])

    def test_query_pagination_and_stage_alias_are_local_contracts(self):
        intent = validate_intent({'action': 'list_applicants', 'filters': {'query': 'REG', 'page': 2, 'limit': 8, 'tahapan': 'online-test'}})
        self.assertEqual(intent['filters'], {'query': 'REG', 'page': 2, 'limit': 8, 'tahapan': 4})
        self.assertEqual(normalize_tahapan('Tes Online'), 4)

    def test_screenshot_range_follow_up_keeps_academic_year(self):
        first = parse_deterministic('siapa pendaftar tahun ajaran 2026/2027')
        self.assertEqual(first['filters']['tahun_ajaran'], '2026/2027')
        self.assertEqual(parse_number_range('tampilkan pendaftar nomor 11-18'), (11, 18))
        response = self.bot.handle_message(message('siapa pendaftar tahun ajaran 2026/2027'))
        self.assertIn('pendaftar', response.lower())
        self.bot.handle_message(message('tampilkan pendaftar nomor 11-18'))
        intent = self.api.calls[-1][0]
        self.assertEqual(intent['filters'], {'tahun_ajaran': '2026/2027', 'page': 2, 'limit': 8})

    def test_screenshot_online_test_is_normalized_and_empty_is_clear(self):
        intent = parse_deterministic('pendaftar tahap online test tahun ajaran 2026/2027')
        self.assertEqual(intent, {'action': 'list_applicants', 'filters': {'tahun_ajaran': '2026/2027', 'tahapan': 4}})
        self.api.query = lambda intent, user: {'status': 'success', 'data': {'data': [], 'meta': {'total': 0}}}
        response = self.bot.handle_message(message('pendaftar tahap online test tahun ajaran 2026/2027'))
        self.assertIn('Tidak ada pendaftar yang ditemukan.', response)
        unavailable = format_response(
            {'action': 'list_applicants'},
            {'status': 'error', 'code': 404, 'message': 'Tahun ajaran belum tersedia'},
        )
        self.assertIn('belum tersedia', unavailable)
        self.assertNotIn('API Error', unavailable)

    def test_metadata_commands_use_supported_contract_values_only(self):
        self.assertEqual(parse_deterministic('/pembayaran REG-1 pertama')['filters']['proof_type'], 'pertama')
        self.assertIsNone(parse_deterministic('/pembayaran REG-1 pelunasan'))
        self.assertEqual(parse_deterministic('/dokumen REG-1 file_mutasi_sekolah')['filters']['document_type'], 'file_mutasi_sekolah')

    def test_invalid_ai_write_or_unknown_intent_never_reaches_api(self):
        self.assertIsNone(validate_intent({'action': 'delete_applicant', 'filters': {}}))
        self.assertIsNone(validate_intent({'action': 'lookup_payment_proof', 'filters': {'identifier': ['PII']}}))
        bot = MessageHandler(self.config, self.api, ai_parser=lambda *_: {'action': 'delete_applicant', 'filters': {}})
        response = bot.handle_message(message('cek sesuatu yang tidak dikenal'))
        self.assertIn('belum memahami', response)
        self.assertEqual(self.api.calls, [])

    def test_audit_log_has_no_user_or_identifier(self):
        logger = logging.getLogger('spmb.bot.audit')
        with self.assertLogs(logger, level='INFO') as captured:
            self.bot.handle_message(message('/progress REG-SECRET'))
        joined = '\n'.join(captured.output)
        self.assertIn('authorized=True', joined)
        self.assertNotIn('REG-SECRET', joined)
        self.assertNotIn('7', joined)


if __name__ == '__main__':
    unittest.main()
