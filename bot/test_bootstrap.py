import unittest

from bot.config import Config
from bot.handler import MessageHandler
from bot.parser import parse_control_command


class RecordingApi:
    def __init__(self):
        self.calls = []

    def query(self, intent, telegram_user_id):
        self.calls.append((intent, telegram_user_id))
        return {'status': 'ok', 'data': {}}


def message(text, chat_id=-100123, chat_type='supergroup', user_id=77):
    return {'text': text, 'chat': {'id': chat_id, 'type': chat_type}, 'from': {'id': user_id}}


class BootstrapRoutingTests(unittest.TestCase):
    def setUp(self):
        self.api = RecordingApi()
        self.config = Config(allowed_group_id='WAITING', bot_username='SPMBAFBSBot')
        self.handler = MessageHandler(self.config, api_client=self.api,
                                      ai_parser=lambda *_: self.fail('AI parser must not run'))

    def test_control_commands_parse_with_bot_mentions(self):
        self.assertEqual(parse_control_command('/setupid@SPMBAFBSBot'), '/setupid')
        self.assertEqual(parse_control_command('/whoami'), '/whoami')

    def test_setupid_reports_group_id_in_bootstrap_mode(self):
        response = self.handler.handle_message(message('/setupid', chat_id=-100999))
        self.assertEqual(response, 'ID grup Telegram saat ini: -100999')
        self.assertEqual(self.api.calls, [])

    def test_whoami_reports_identity_without_sensitive_profile_data(self):
        response = self.handler.handle_message(message('/whoami', chat_id=-100999, user_id=42))
        self.assertEqual(response, 'ID pengguna Telegram: 42\nID chat Telegram: -100999\nTipe chat: supergroup')
        self.assertNotIn('username', response.lower())
        self.assertEqual(self.api.calls, [])

    def test_all_data_commands_are_blocked_without_api_or_ai(self):
        commands = ['/kuota', '/statistik', '/cari ani', '/biodata 123', '/hariini',
                    '/belumverifikasi', '/belumberkas', '/gender', '/jeniskelamin',
                    '/kota bandung', '/kecamatan coblong', '/sekolah SMP 1']
        for command in commands:
            with self.subTest(command=command):
                self.assertEqual(self.handler.handle_message(message(command)),
                                 'Grup resmi Tim SPMB belum dikonfigurasi. Hubungi administrator.')
        self.assertEqual(self.api.calls, [])

    def test_setupid_in_private_chat_explains_group_requirement(self):
        response = self.handler.handle_message(message('/setupid', chat_id=123, chat_type='private'))
        self.assertEqual(response, 'Jalankan /setupid di grup Telegram resmi untuk melihat ID grup.')


class ConfiguredRoutingTests(unittest.TestCase):
    def test_controls_preserve_existing_authorization(self):
        api = RecordingApi()
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',))
        handler = MessageHandler(config, api_client=api)
        self.assertEqual(handler.handle_message(message('/whoami')),
                         'ID pengguna Telegram: 77\nID chat Telegram: -100123\nTipe chat: supergroup')
        self.assertEqual(api.calls, [])


if __name__ == '__main__':
    unittest.main()
