import tempfile
import unittest

from bot.config import Config
from bot.handler import MessageHandler
from bot.run_status import RunStatusStore, TelegramRunStatusBridge, message_binding


def msg(text, thread=7, chat=-100123, user=77):
    return {'text': text, 'chat': {'id': chat, 'type': 'supergroup'}, 'from': {'id': user}, 'message_thread_id': thread}


class RunStatusTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.NamedTemporaryFile()
        self.now = [1000.0]
        self.store = RunStatusStore(self.tmp.name, heartbeat_timeout=30, clock=lambda: self.now[0])

    def tearDown(self):
        self.tmp.close()

    def test_topic_binding_and_stale_heartbeat(self):
        started = self.store.start('-100123', '7', 'run-1')
        self.assertEqual(started.message_thread_id, '7')
        self.store.heartbeat('-100123', '7', 'run-1')
        self.assertTrue(self.store.describe('-100123', '7')['active'])
        self.assertFalse(self.store.describe('-100123', '8')['active'])
        self.now[0] += 31
        self.assertFalse(self.store.describe('-100123', '7')['active'])

    def test_status_transitions_and_terminal_state(self):
        self.store.start('c', 't', 'r')
        self.store.transition('c', 't', 'r', 'RUNNING')
        self.store.transition('c', 't', 'r', 'TESTING')
        self.store.transition('c', 't', 'r', 'STAGING_PASS')
        final = self.store.transition('c', 't', 'r', 'DONE')
        self.assertEqual(final.state, 'DONE')
        self.assertFalse(self.store.describe('c', 't')['active'])
        with self.assertRaises(ValueError):
            self.store.heartbeat('c', 't', 'r')

    def test_wrong_run_id_cannot_update_topic(self):
        self.store.start('c', 't', 'real')
        with self.assertRaises(ValueError):
            self.store.heartbeat('c', 't', 'other')

    def test_handler_has_no_false_positive_and_binds_topic(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        bot = MessageHandler(config, ai_parser=lambda *_: self.fail('AI must not run'))
        self.assertIn('no active worker', bot.handle_message(msg('/status')).lower())
        response = bot.handle_message(msg('/run-start run-topic'))
        self.assertIn('no active worker', response.lower())
        response = bot.handle_message(msg('/run-heartbeat run-topic coding'))
        self.assertIn('ACTIVE', response)
        other = bot.handle_message(msg('/status', thread=8))
        self.assertIn('no active worker', other.lower())
        self.assertIn('run-topic', response)

    def test_handler_status_reports_transition_and_stale_heartbeat_truthfully(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name,
                        run_heartbeat_timeout_seconds=30)
        bot = MessageHandler(config, ai_parser=lambda *_: self.fail('AI must not run'))
        bot.run_status.clock = lambda: self.now[0]
        self.assertIn('no active worker', bot.handle_message(msg('/status')).lower())
        bot.handle_message(msg('/run-start run-topic'))
        bot.handle_message(msg('/run-heartbeat run-topic ingest'))
        response = bot.handle_message(msg('/run-testing run-topic'))
        self.assertIn('Worker status: ACTIVE', response)
        self.assertIn('Run state: TESTING', response)
        self.assertIn('Stage: ingest', response)
        self.assertNotIn('\\nStatus:', response)
        self.now[0] += 31
        response = bot.handle_message(msg('/status'))
        self.assertIn('heartbeat stale', response.lower())
        self.assertNotIn('Worker status: ACTIVE', response)

    def test_lifecycle_commands_accept_bot_mentions_and_remain_topic_bound(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        bot = MessageHandler(config)
        bot.handle_message(msg('/run-start@SPMBAFBSBot mentioned-run'))
        response = bot.handle_message(msg('/run-heartbeat@SPMBAFBSBot mentioned-run'))
        self.assertIn('ACTIVE', response)
        other_topic = bot.handle_message(msg('/status@SPMBAFBSBot', thread=8))
        self.assertIn('not linked', other_topic.lower())

    def test_unauthorized_user_cannot_create_or_read_run(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        bot = MessageHandler(config)
        response = bot.handle_message(msg('/run-start secret', user=999))
        self.assertIn('tidak berwenang', response.lower())
        self.assertIsNone(self.store.get('-100123', '7'))

    def test_production_status_never_reads_worker_store(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        bot = MessageHandler(config)
        bot.handle_message(msg('/mode production'))
        bot.run_status.start('-100123', '7', 'staging-run')
        response = bot.handle_message(msg('/status'))
        self.assertIn('PRODUCTION', response)
        self.assertNotIn('staging-run', response)

    def test_message_binding_requires_chat_and_preserves_topic(self):
        self.assertEqual(message_binding(msg('/status')), ('-100123', '7'))
        with self.assertRaises(ValueError):
            message_binding({'text': '/status', 'chat': {}})

    def test_bridge_posts_only_to_explicit_configured_topic(self):
        calls = []
        bridge = TelegramRunStatusBridge('token-not-used', '-100123', '7', sender=calls.append)
        self.assertTrue(bridge.post('RUNNING run-1'))
        self.assertEqual(calls[0]['chat_id'], '-100123')
        self.assertEqual(calls[0]['message_thread_id'], '7')
        unbound = TelegramRunStatusBridge('token-not-used', '-100123', '', sender=calls.append)
        self.assertFalse(unbound.post('must not send'))
        self.assertEqual(len(calls), 1)

    def test_bridge_transport_failure_does_not_break_in_topic_status(self):
        def fail(_payload):
            raise OSError('synthetic transport failure')

        bridge = TelegramRunStatusBridge('token-not-used', '-100123', '7', sender=fail)
        self.assertFalse(bridge.post('RUNNING run-1'))

    def test_handler_update_api_uses_configured_staging_topic(self):
        calls = []
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name,
                        telegram_bot_token='token-not-used', run_status_update_chat_id='-100123',
                        run_status_update_thread_id='7')
        bot = MessageHandler(config)
        bot.run_status_bridge.sender = calls.append
        self.assertTrue(bot.post_run_status_update('TESTING run-1'))
        self.assertEqual(calls[0]['chat_id'], '-100123')
        self.assertEqual(calls[0]['message_thread_id'], '7')
        production = MessageHandler(Config(session_mode='production', telegram_bot_token='token-not-used',
                                           run_status_update_chat_id='-100123', run_status_update_thread_id='7',
                                           run_status_db_path=self.tmp.name, context_db_path=':memory:'))
        self.assertFalse(production.post_run_status_update('must not post'))

    def test_staging_pass_cannot_claim_active_without_fresh_heartbeat(self):
        self.store.start('c', 't', 'r')
        self.store.transition('c', 't', 'r', 'RUNNING')
        self.store.transition('c', 't', 'r', 'TESTING')
        self.store.transition('c', 't', 'r', 'STAGING_PASS')
        self.assertFalse(self.store.describe('c', 't')['active'])
        self.assertIn('heartbeat stale', self.store.format_status(self.store.describe('c', 't')).lower())

    def test_progress_command_is_truthful_and_milestone_refreshes_same_topic(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name,
                        run_heartbeat_timeout_seconds=30, telegram_bot_token='token')
        bot = MessageHandler(config)
        calls = []
        bot.run_status_bridge.sender = calls.append
        bot.run_status.clock = lambda: self.now[0]
        bot.handle_message(msg('/run-start run-topic'))
        self.now[0] += 29
        response = bot.handle_message(msg('/run-testing run-topic'))
        self.assertIn('Run state: TESTING', response)
        self.assertIn('Worker status: ACTIVE', response)
        self.assertEqual(calls[0]['chat_id'], '-100123')
        self.assertEqual(calls[0]['message_thread_id'], '7')
        self.assertEqual(bot.handle_message(msg('/progress')), response)
        self.now[0] += 31
        self.assertIn('heartbeat stale', bot.handle_message(msg('/progress')).lower())

    def test_public_run_stop_help_commands_are_topic_bound(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        bot = MessageHandler(config, ai_parser=lambda *_: self.fail('AI must not run'))
        self.assertIn('Perintah bridge staging', bot.handle_message(msg('/help')))
        self.assertIn('Run state: STARTED', bot.handle_message(msg('/run r-public')))
        stopped = bot.handle_message(msg('/stop r-public'))
        self.assertIn('Run state: STOPPED', stopped)
        self.assertFalse(bot.run_status.describe('-100123', '8')['active'])

    def test_mode_persists_per_topic_across_handler_restart(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        first = MessageHandler(config)
        first.handle_message(msg('/mode production'))
        second = MessageHandler(config)
        self.assertIn('PRODUCTION', second.handle_message(msg('/mode')))
        self.assertIn('STAGING', second.handle_message(msg('/mode', thread=8)))

    def test_terminal_milestone_is_not_running(self):
        self.store.start('c', 't', 'r')
        self.store.heartbeat('c', 't', 'r')
        self.store.transition('c', 't', 'r', 'TESTING')
        final = self.store.milestone('c', 't', 'r', 'DONE', 'complete')
        self.assertEqual(final.state, 'DONE')
        self.assertFalse(self.store.describe('c', 't')['active'])
        self.assertNotIn('ACTIVE', self.store.format_status(self.store.describe('c', 't')))

    def test_handler_exposes_each_terminal_state_as_terminal(self):
        config = Config(allowed_group_id='-100123', allowed_telegram_user_ids=('77',),
                        context_db_path=':memory:', run_status_db_path=self.tmp.name)
        for command, expected in (('/run-done', 'DONE'), ('/run-blocked', 'BLOCKED'), ('/run-stop', 'STOPPED')):
            bot = MessageHandler(config, ai_parser=lambda *_: self.fail('AI must not run'))
            run_id = f'terminal-{expected.lower()}'
            bot.handle_message(msg(f'/run-start {run_id}'))
            if expected == 'DONE':
                bot.handle_message(msg(f'/run-heartbeat {run_id}'))
                bot.handle_message(msg(f'/run-testing {run_id}'))
                bot.handle_message(msg(f'/run-pass {run_id}'))
            response = bot.handle_message(msg(f'{command} {run_id}'))
            self.assertIn(f'Run state: {expected}', response)
            self.assertIn(f'{expected} (terminal)', response)


if __name__ == '__main__':
    unittest.main()
