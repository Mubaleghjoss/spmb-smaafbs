import unittest

from bot.config import Config
from bot.handler import MessageHandler
from bot.parser import normalize_academic_year, parse_deterministic


def msg(text, user=1, chat='supergroup', chat_id=99):
    return {
        'text': text,
        'from': {'id': user},
        'chat': {'type': chat, 'id': chat_id},
    }


class ConversationApi:
    def __init__(self, items=None):
        self.items = items or [
            {
                'id': 1,
                'nama': 'Ani',
                'nomor_pendaftaran': 'REG001',
                'asal_sekolah': 'SMP 1',
                'city': 'Bandung',
                'district': 'Coblong',
                'verification_status': 'menunggu',
            },
            {
                'id': 2,
                'nama': 'Budi',
                'nomor_pendaftaran': 'REG002',
                'asal_sekolah': 'SMP 2',
                'city': 'Bandung',
                'district': 'Coblong',
                'verification_status': 'menunggu',
            },
        ]
        self.calls = []

    def query(self, intent, user):
        self.calls.append((intent, user))
        if intent['action'] == 'get_quota':
            return {
                'status': 'success',
                'data': {
                    'tahun_ajaran': {'nama': '2027/2028'},
                    'quota': 10,
                    'registered': 2,
                    'within_quota': 2,
                    'waiting_list': 0,
                },
            }
        if intent['action'] == 'get_applicant_detail':
            identifier = intent['filters']['identifier']
            return {'status': 'success', 'data': next(item for item in self.items if str(item['id']) == identifier or item['nomor_pendaftaran'] == identifier)}
        return {'status': 'success', 'data': self.items}


class ConversationContextTests(unittest.TestCase):
    def setUp(self):
        self.api = ConversationApi()
        self.config = Config(
            allowed_group_id='99',
            allowed_telegram_user_ids=('1',),
        )
        self.bot = MessageHandler(self.config, self.api, ai_parser=lambda *_: None)

    def test_short_year_and_list_phrases_are_deterministic(self):
        self.assertEqual(normalize_academic_year('27/28'), '2027/2028')
        self.assertEqual(
            parse_deterministic('siapa pendaftar 27/28'),
            {'action': 'list_applicants', 'filters': {'tahun_ajaran': '2027/2028'}},
        )
        self.assertEqual(
            parse_deterministic('tampilkan biodata pendaftar 27/28'),
            {'action': 'list_applicants', 'filters': {'tahun_ajaran': '2027/2028'}},
        )

    def test_authorized_official_group_can_omit_bot_mention(self):
        response = self.bot.handle_message(msg('siapa pendaftar 27/28'))
        self.assertIn('Daftar Pendaftar', response)
        self.assertEqual(self.api.calls[0][0]['filters']['tahun_ajaran'], '2027/2028')

    def test_quota_year_is_used_by_contextual_follow_up(self):
        self.bot.handle_message(msg('kuota 27/28'))
        self.bot.handle_message(msg('itu 1 pendaftar siapa?'))
        self.assertEqual(self.api.calls[-1][0], {
            'action': 'list_applicants',
            'filters': {'tahun_ajaran': '2027/2028'},
        })

    def test_single_biodata_request_opens_detail_automatically(self):
        self.api.items = [self.api.items[0]]
        response = self.bot.handle_message(msg('tampilkan biodata pendaftar 27/28'))
        self.assertIn('Biodata Pendaftar', response)
        self.assertEqual([call[0]['action'] for call in self.api.calls], ['list_applicants', 'get_applicant_detail'])

    def test_multiple_results_can_be_opened_by_number(self):
        self.bot.handle_message(msg('siapa pendaftar 27/28'))
        response = self.bot.handle_message(msg('biodata nomor 2'))
        self.assertIn('Biodata Pendaftar', response)
        self.assertEqual(self.api.calls[-1][0], {
            'action': 'get_applicant_detail',
            'filters': {'identifier': 'REG002'},
        })

    def test_unauthorized_user_still_cannot_use_unmentioned_group_text(self):
        self.assertIsNone(self.bot.handle_message(msg('siapa pendaftar 27/28', user=2)))
        self.assertEqual(self.api.calls, [])


if __name__ == '__main__':
    unittest.main()
