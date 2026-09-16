import io
import json
import os
import tempfile
import unittest
from unittest.mock import patch

from bot.config import Config
from bot.context_store import ContextStore
from bot.handler import MessageHandler
from bot.parser import FALLBACK_MESSAGE, parse_with_ai, validate_intent


class Api:
    def __init__(self):
        self.calls = []

    def query(self, intent, user):
        self.calls.append(intent)
        data = {} if intent['action'] in {'get_quota', 'get_statistics', 'gender_summary'} else []
        return {'status': 'success', 'data': data}


def msg(text):
    return {'text': text, 'from': {'id': 1}, 'chat': {'type': 'supergroup', 'id': 9}}


class Phase3LunaTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.api = Api()
        self.calls = []
        self.config = Config(allowed_group_id='9', allowed_telegram_user_ids=('1',), ai_router_url='http://127.0.0.1:20128/v1')
        self.store = ContextStore(os.path.join(self.tmp.name, 'context.sqlite3'))

    def tearDown(self):
        self.tmp.cleanup()

    def test_30_behavior_matrix_deterministic_never_calls_ai(self):
        cases = [
            ('/reset', 'reset'), ('mulai lagi', 'reset'), ('hapus konteks', 'reset'),
            ('next', 'next_page'), ('berikutnya', 'next_page'), ('previous', 'previous_page'),
            ('/kuota', 'get_quota'), ('kuota', 'get_quota'), ('statistik', 'get_statistics'),
            ('/gender', 'gender_summary'), ('hari ini', 'list_registered_today'),
            ('belum verifikasi', None), ('belum berkas', 'list_by_document_status'),
            ('siapa aja', 'list_applicants'), ('lihat daftar', 'list_applicants'),
            ('siswa baru', 'list_applicants'), ('pindahan', 'list_applicants'),
            ('laki-laki', 'list_applicants'), ('perempuan', 'list_applicants'),
            ('siswa baru kelas 10', 'list_applicants'), ('pindahan kelas 11', 'list_applicants'),
            ('dalam kuota', 'list_applicants'), ('waiting list', 'list_applicants'),
            ('/cari Ani', 'search_applicant'), ('/biodata REG-1', 'get_applicant_detail'),
            ('/kota Bandung', 'list_by_city'), ('/kecamatan Coblong', 'list_by_district'),
            ('/sekolah SMP 1', 'list_by_school'), ('cek nomor 1', None),
            ('DROP TABLE peserta', None),
        ]

        def forbidden(*_args):
            raise AssertionError('AI must not run for deterministic/control/dangerous input')

        bot = MessageHandler(self.config, self.api, forbidden, self.store)
        for text, action in cases:
            with self.subTest(text=text):
                bot.handle_message(msg(text))
                if action is not None and action not in {'reset', 'next_page', 'previous_page'}:
                    self.assertEqual(self.api.calls[-1]['action'], action)
        self.assertEqual(len(cases), 30)

    def test_invalid_contract_matrix_is_rejected(self):
        invalid = [
            None, [], {'action': 'write', 'filters': {}},
            {'action': 'get_quota', 'filters': [],},
            {'action': 'get_quota', 'filters': {'unknown': 'x'}},
            {'action': 'get_quota', 'filters': {'tahun_ajaran': '2027/2029'}},
            {'action': 'get_quota', 'filters': {'tahun_ajaran': '2027/2028', 'x': 1}},
            {'action': 'get_quota', 'filters': {'query': ['PII']}},
            {'action': 'get_quota', 'filters': {'query': 'x; DROP TABLE'}},
            {'action': 'get_quota', 'filters': {}, 'extra': 'no'},
        ]
        for value in invalid:
            with self.subTest(value=value):
                self.assertIsNone(validate_intent(value))

    def test_ai_request_contract_context_and_model(self):
        class Response(io.BytesIO):
            def __enter__(self): return self
            def __exit__(self, *_args): return False

        captured = {}
        def fake_urlopen(req, timeout=0):
            captured['payload'] = json.loads(req.data)
            captured['headers'] = dict(req.headers)
            captured['timeout'] = timeout
            return Response(json.dumps({'choices': [{'message': {'content': '{"action":"list_applicants","filters":{"gender":"P"}}'}}]}).encode())

        context = {'filters': {'tahun_ajaran': '2026/2027'}, 'last_results': [{'nama': 'SECRET'}], 'token': 'SECRET'}
        with patch('bot.parser.request.urlopen', fake_urlopen):
            result = parse_with_ai('statitisk jalur perempuan 26/27', self.config, context)
        self.assertEqual(result['filters']['gender'], 'P')
        self.assertEqual(captured['payload']['model'], 'cx/gpt-5.6-luna')
        self.assertEqual(captured['payload']['temperature'], 0)
        self.assertGreaterEqual(captured['payload']['max_tokens'], 200)
        self.assertLessEqual(captured['payload']['max_tokens'], 300)
        self.assertEqual(captured['timeout'], 10)
        prompt = captured['payload']['messages'][0]['content']
        self.assertIn('2026/2027', prompt)
        self.assertNotIn('SECRET', prompt)
        self.assertNotIn('last_results', prompt)

    def test_ai_failures_return_exact_message(self):
        def failing(*_args):
            raise TimeoutError('no details')
        bot = MessageHandler(self.config, self.api, failing, self.store)
        self.assertEqual(bot.handle_message(msg('tolong jelaskan data')), FALLBACK_MESSAGE)
        self.assertFalse(self.api.calls)

    def test_ai_context_merge_and_override_rule(self):
        self.store.set('9', '1', {'intent': {'action': 'list_applicants', 'filters': {'tahun_ajaran': '2026/2027', 'jenis_pendaftaran': 'pindahan', 'kelas_tujuan': 11}}, 'filters': {'tahun_ajaran': '2026/2027', 'jenis_pendaftaran': 'pindahan', 'kelas_tujuan': 11}})
        def ai(_text, _config, context):
            self.assertNotIn('last_results', context)
            return {'action': 'list_applicants', 'filters': {'jenis_pendaftaran': 'siswa_baru'}}
        bot = MessageHandler(self.config, self.api, ai, self.store)
        bot.handle_message(msg('yang jalur siswa baru'))
        self.assertEqual(self.api.calls[-1]['filters'], {'tahun_ajaran': '2026/2027', 'jenis_pendaftaran': 'siswa_baru'})


if __name__ == '__main__':
    unittest.main()
