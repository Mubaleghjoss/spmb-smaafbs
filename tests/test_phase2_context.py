import json
import os
import sqlite3
import tempfile
import time
import unittest

from bot.config import Config
from bot.context_store import ContextStore
from bot.handler import MessageHandler
from bot.parser import parse_number_selection


def message(text, user=1, chat_id=99):
    return {'text': text, 'from': {'id': user}, 'chat': {'type': 'supergroup', 'id': chat_id}}


class FakeApi:
    def __init__(self):
        self.calls = []

    def query(self, intent, user):
        self.calls.append((intent, user))
        if intent['action'] == 'get_applicant_detail':
            return {'status': 'success', 'data': {'nama': 'N', 'nomor_pendaftaran': intent['filters']['identifier']}}
        page = int(intent['filters'].get('page', 1))
        items = [{'id': page * 10 + 1, 'nama': f'N{page}', 'nomor_pendaftaran': f'R{page}'}]
        return {'status': 'success', 'data': {'data': items, 'meta': {'current_page': page, 'last_page': 2, 'total': 2}}}


class Phase2ContextTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.store = ContextStore(os.path.join(self.tmp.name, 'context.sqlite3'))
        self.api = FakeApi()
        self.bot = MessageHandler(Config(allowed_group_id='99', allowed_telegram_user_ids=('1',)), self.api, lambda *_: None, self.store)

    def tearDown(self):
        self.tmp.cleanup()

    def test_restart_ttl_clear_and_key_isolation(self):
        self.store.set('c', 'u', {'intent': {'action': 'list_applicants', 'filters': {'city': 'X'}}, 'page': 2, 'last_page': 3, 'last_results': [{'id': 7, 'nama': 'A', 'secret': 'no'}], 'secret': 'no'})
        restarted = ContextStore(self.store.db_path, ttl_seconds=60)
        state = restarted.get('c', 'u')
        self.assertEqual(state['page'], 2)
        self.assertEqual(state['last_results'], [{'id': 7, 'nama': 'A'}])
        self.assertNotIn('secret', json.dumps(state))
        self.assertEqual(restarted.get('c', 'other'), {})
        restarted.clear('c', 'u')
        self.assertEqual(restarted.get('c', 'u'), {})

    def test_ttl_and_prune_expired(self):
        self.store.set('c', 'u', {'last_results': [{'id': 1}]})
        with sqlite3.connect(self.store.db_path) as db:
            db.execute('UPDATE conversation_context SET updated_at=?', (time.time() - self.store.ttl_seconds - 1,))
        self.assertEqual(self.store.get('c', 'u'), {})
        self.store.set('c', 'u', {'last_results': [{'id': 1}]})
        with sqlite3.connect(self.store.db_path) as db:
            db.execute('UPDATE conversation_context SET updated_at=?', (time.time() - self.store.ttl_seconds - 1,))
        self.assertEqual(self.store.prune_expired(), 1)

    def test_malformed_sqlite_is_fail_safe(self):
        bad = os.path.join(self.tmp.name, 'bad.sqlite3')
        with open(bad, 'wb') as fh:
            fh.write(b'not sqlite')
        store = ContextStore(bad)
        self.assertEqual(store.get('c', 'u'), {})

    def test_numbered_forms_and_identifier_selection(self):
        self.assertEqual([parse_number_selection(x) for x in ('cek nomor 3', 'cek no 3', 'cek yang 3', 'cek yg 3', 'yang nomor 3', 'yang ke 3', 'cek yang pertama', 'yang pertama', 'cek yg 1 itu')], [3, 3, 3, 3, 3, 3, 1, 1, 1])
        self.bot.handle_message(message('siapa aja'))
        self.bot.handle_message(message('cek nomor 1'))
        self.assertEqual(self.api.calls[-1][0]['filters']['identifier'], 'R1')

    def test_exact_responses_navigation_and_metadata_bound(self):
        self.assertEqual(self.bot.handle_message(message('next')), 'Belum ada daftar sebelumnya. Coba minta daftar peserta terlebih dahulu.')
        self.bot.handle_message(message('daftarnya'))
        response = self.bot.handle_message(message('next'))
        self.assertIn('*Daftar Pendaftar*', response)
        self.assertIn('Halaman 2 dari 2', response)
        self.assertEqual(self.bot.handle_message(message('next')), 'Sudah di halaman terakhir.')
        response = self.bot.handle_message(message('previous'))
        self.assertIn('*Daftar Pendaftar*', response)
        self.assertIn('Halaman 1 dari 2', response)
        self.assertEqual(self.bot.handle_message(message('previous')), 'Sudah di halaman pertama.')
        self.assertEqual(self.bot.handle_message(message('cek nomor 9')), 'Nomor itu tidak ada pada daftar terakhir.')
        self.assertEqual(self.bot.handle_message(message('reset')), 'Konteks pencarian sudah direset.')

    def test_filter_merge_page_reset_and_cross_filter_rules(self):
        self.bot.handle_message(message('pindahan kelas 11'))
        self.bot.handle_message(message('siswa baru'))
        filters = self.api.calls[-1][0]['filters']
        self.assertEqual(filters['jenis_pendaftaran'], 'siswa_baru')
        self.assertNotIn('kelas_tujuan', filters)
        self.bot.handle_message(message('pindahan kelas 11'))
        self.assertIn('Kombinasi', self.bot.handle_message(message('siswa baru kelas 11')))
        self.assertEqual(self.api.calls[-1][0]['filters']['kelas_tujuan'], 11)


if __name__ == '__main__':
    unittest.main()
