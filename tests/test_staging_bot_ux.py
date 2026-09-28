import tempfile
import unittest
from pathlib import Path

from bot.callbacks import build_inline_keyboard, parse_callback_data
from bot.config import Config
from bot.handler import MessageHandler
from bot.parser import normalize_tahapan, parse_deterministic


def msg(text, user='7', chat='77', kind='private'):
    return {'text': text, 'from': {'id': user}, 'chat': {'id': chat, 'type': kind}}


class Api:
    def __init__(self):
        self.calls = []

    def query(self, intent, user):
        self.calls.append(intent)
        filters = intent['filters']
        page = int(filters.get('page', 1))
        limit = int(filters.get('limit', 8))
        items = [] if page == 3 else [{'nama': f'N{i}', 'nomor_pendaftaran': f'R{i}'} for i in range(1, limit + 1)]
        return {'status': 'success', 'data': {'data': items, 'meta': {'current_page': page, 'total': 18, 'last_page': 3, 'per_page': limit}}}


class StagingBotUxTests(unittest.TestCase):
    def setUp(self):
        self.db = tempfile.NamedTemporaryFile(suffix='.sqlite3', delete=False)
        self.db.close()
        self.api = Api()
        self.config = Config(allowed_group_id='official', allowed_telegram_user_ids=('7',), context_db_path=self.db.name)
        self.bot = MessageHandler(self.config, self.api, ai_parser=lambda *_: None)

    def tearDown(self):
        Path(self.db.name).unlink(missing_ok=True)

    def test_range_11_18_and_page_three_empty_state_are_contextual(self):
        self.bot.handle_message(msg('pendaftar tahun ajaran 2026/2027'))
        self.bot.handle_message(msg('pendaftar nomor 11-18'))
        self.assertEqual(self.api.calls[-1]['filters'], {'tahun_ajaran': '2026/2027', 'page': 2, 'limit': 8})
        result = self.bot.handle_callback_query({'id': 'c', 'data': 'spmb:v1:next', 'from': {'id': '7'}, 'message': msg('')})
        self.assertIn('Tidak ada pendaftar', result['text'])
        self.assertEqual(self.api.calls[-1]['filters']['page'], 3)

    def test_online_test_aliases_are_backend_integer_stage(self):
        self.assertEqual(normalize_tahapan('sedang tes online'), 4)
        self.assertEqual(parse_deterministic('pendaftar sedang tes online tahun 2026/2027')['filters']['tahapan'], 4)

    def test_callback_payloads_are_strict_and_keyboard_is_complete(self):
        self.assertEqual(parse_callback_data('spmb:v1:stage:4'), ('stage', '4'))
        self.assertIsNone(parse_callback_data('spmb:v1:stage:99'))
        self.assertIsNone(parse_callback_data('spmb:v1:list:extra'))
        markup = build_inline_keyboard(page=2, last_page=3)
        values = [button['callback_data'] for row in markup['inline_keyboard'] for button in row]
        self.assertTrue({'spmb:v1:prev', 'spmb:v1:next', 'spmb:v1:reload', 'spmb:v1:menu', 'spmb:v1:stats', 'spmb:v1:list', 'spmb:v1:stage:4', 'spmb:v1:docs:complete', 'spmb:v1:docs:incomplete'}.issubset(values))

    def test_callback_authorization_and_group_privacy(self):
        callback = {'id': 'c', 'data': 'spmb:v1:stats', 'from': {'id': '999'}, 'message': msg('')}
        self.assertIn('tidak berwenang', self.bot.handle_callback_query(callback)['text'])
        callback['from'] = {'id': '7'}
        callback['message'] = msg('', chat='not-official', kind='group')
        self.assertIn('tidak berwenang', self.bot.handle_callback_query(callback)['text'])


if __name__ == '__main__':
    unittest.main()
