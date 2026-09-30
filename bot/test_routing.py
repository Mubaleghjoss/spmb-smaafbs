import unittest
from unittest.mock import patch

from bot.config import Config
from bot.handler import MessageHandler
from bot.routing import ProductionDispatcher


class RecordingApi:
    def __init__(self):
        self.calls = []

    def query(self, intent, telegram_user_id):
        self.calls.append((intent, telegram_user_id))
        return {'status': 'success', 'data': []}


def message(text, chat_id=-100123, user_id=77, chat_type='supergroup'):
    return {
        'text': text,
        'chat': {'id': chat_id, 'type': chat_type},
        'from': {'id': user_id},
    }


class SessionRoutingTests(unittest.TestCase):
    def setUp(self):
        self.api = RecordingApi()
        self.config = Config(
            allowed_group_id='-100123',
            allowed_telegram_user_ids=('77',),
            allowed_admin_chat_ids=('999',),
            admin_extension_enabled=True,
            context_db_path=':memory:',
        )
        self.handler = MessageHandler(self.config, api_client=self.api,
                                      ai_parser=lambda *_: self.fail('AI parser must not run'))

    def test_mode_isolated_per_chat(self):
        self.assertIn('STAGING', self.handler.handle_message(message('/mode')))
        self.assertIn('PRODUCTION', self.handler.handle_message(message('/mode production')))
        self.assertIn('PRODUCTION', self.handler.handle_message(message('/mode')))
        other = message('/status', chat_id=999, chat_type='private')
        self.assertIn('STAGING', self.handler.handle_message(other))

    def test_unknown_mode_is_rejected(self):
        self.assertEqual(
            self.handler.handle_message(message('/mode qa')),
            'Mode tidak dikenal. Pilih hanya: staging atau production.',
        )

    def test_mode_change_requires_existing_authorization(self):
        response = self.handler.handle_message(message('/mode production', user_id=999))
        self.assertEqual(response, 'Anda tidak berwenang menggunakan Bot Data SPMB.')

    def test_status_and_heartbeat_are_local_probes_without_api(self):
        for command in ('/status', '/heartbeat@SPMBAFBSBot', '/health'):
            with self.subTest(command=command):
                response = self.handler.handle_message(message(command, chat_id=999, chat_type='private'))
                self.assertIn('Mode: *STAGING*', response)
                self.assertIn('Stage: staging-only', response)
                self.assertIn('Heartbeat: handler-alive', response)
                self.assertIn('Worker status: not linked', response)
        self.assertEqual(self.api.calls, [])

    def test_status_identifies_mode_without_claiming_worker(self):
        response = self.handler.handle_message(message('/status', chat_id=999, chat_type='private'))
        self.assertIn('Mode: *STAGING*', response)
        self.assertIn('Heartbeat: handler-alive', response)
        self.assertIn('Worker status: not linked', response)
        self.assertEqual(self.api.calls, [])

    def test_production_isolation_rejects_data_and_destructive_commands(self):
        self.handler.handle_message(message('/mode production'))
        for text in ('/kuota', '/deploy', 'restart production', 'run ssh command'):
            with self.subTest(text=text):
                response = self.handler.handle_message(message(text))
                self.assertIn('Mode PRODUCTION', response)
        self.assertEqual(self.api.calls, [])

    def test_production_status_is_read_only_and_has_heartbeat_labels(self):
        self.handler.handle_message(message('/mode production'))
        response = self.handler.handle_message(message('/status'))
        self.assertIn('Mode: *PRODUCTION*', response)
        self.assertIn('Stage: production-read-only', response)
        self.assertIn('Heartbeat: handler-alive', response)
        self.assertIn('Execution policy: *READ-ONLY*', response)
        self.assertEqual(self.api.calls, [])

    def test_dispatcher_requires_confirmation_and_fixed_path(self):
        dispatcher = ProductionDispatcher(Config(production_confirmation_token='confirm-token'))
        with self.assertRaises(PermissionError):
            dispatcher.dispatch('deploy', confirmed=False, token='confirm-token')
        with self.assertRaises(PermissionError):
            dispatcher.dispatch('deploy', confirmed=True, token='wrong')
        with patch('bot.routing.subprocess.run') as run:
            run.return_value.returncode = 0
            run.return_value.stdout = 'ok\n'
            self.assertEqual(dispatcher.dispatch('deploy', confirmed=True, token='confirm-token'), 'ok')
            run.assert_called_once_with(
                ['/home/hermesadmin/bin/smaafbs-prod-dispatch', 'deploy'],
                check=False, capture_output=True, text=True, timeout=30,
            )


if __name__ == '__main__':
    unittest.main()
