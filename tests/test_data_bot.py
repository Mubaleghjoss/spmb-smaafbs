import unittest
from bot.config import Config
from bot.handler import MessageHandler
from bot.parser import parse_deterministic, validate_intent
from bot.security import READ_ONLY_MESSAGE

class FakeApi:
    def __init__(self, payload=None): self.calls=[]; self.payload=payload or {'status':'success','data':[]}
    def query(self, intent, user):
        self.calls.append((intent,user))
        if self.payload.get('status') != 'success' or self.payload.get('data') != []: return self.payload
        defaults = {
            'get_quota': {'tahun_ajaran': {'nama':'2025'}, 'quota':100, 'registered':1, 'within_quota':1, 'waiting_list':0},
            'get_statistics': {'total':1, 'verified':0, 'pending':1, 'complete_documents':0, 'incomplete_documents':1},
            'gender_summary': {'L':0, 'P':1, 'unknown':0},
        }
        return {'status':'success','data':defaults.get(intent['action'], [])}

def msg(text, user=1, chat='private', chat_id=1): return {'text':text,'from':{'id':user},'chat':{'type':chat,'id':chat_id}}
def applicant(i=1, name='Ani'): return {'id':i,'nama':name,'nomor_pendaftaran':'REG%03d'%i,'asal_sekolah':'SMP 1','city':'Bandung','district':'Coblong','verification_status':'menunggu'}
class DataBotTests(unittest.TestCase):
    def setUp(self):
        self.config=Config(allowed_telegram_user_ids=('1',),allowed_group_id='99')
        self.api=FakeApi(); self.bot=MessageHandler(self.config,self.api,lambda *_: None)
    def action(self, text): self.bot.handle_message(msg(text)); return self.api.calls[-1][0]
    def test_01_quota(self): self.assertEqual(self.action('/kuota')['action'],'get_quota')
    def test_02_statistics(self): self.assertEqual(self.action('statistik')['action'],'get_statistics')
    def test_03_exact_registration_number(self): self.assertEqual(self.action('/biodata REG001')['filters']['identifier'],'REG001')
    def test_04_partial_name(self): self.assertEqual(self.action('cari Ani')['filters']['query'],'Ani')
    def test_05_duplicate_name(self): self.assertEqual(self.action('/cari Siti')['action'],'search_applicant')
    def test_06_city_filter(self): self.assertEqual(self.action('/kota Bandung')['filters']['city'],'Bandung')
    def test_07_district_filter(self): self.assertEqual(self.action('/kecamatan Coblong')['filters']['district'],'Coblong')
    def test_08_school_filter(self): self.assertEqual(self.action('/sekolah SMP 1')['filters']['school'],'SMP 1')
    def test_09_registered_today(self): self.assertEqual(self.action('/hariini')['action'],'list_registered_today')
    def test_10_pending_verification(self): self.assertEqual(self.action('/belumverifikasi')['filters']['verification_status'],'menunggu')
    def test_11_incomplete_document(self): self.assertEqual(self.action('/belumberkas')['filters']['document_status'],'incomplete')
    def test_12_gender_summary(self): self.assertEqual(self.action('/gender')['action'],'gender_summary')
    def test_13_unauthorized_user(self): self.assertIn('tidak berwenang',self.bot.handle_message(msg('/kuota',2))); self.assertFalse(self.api.calls)
    def test_14_wrong_group(self): self.assertIn('grup resmi',self.bot.handle_message(msg('/kuota',chat='group',chat_id=8))); self.assertFalse(self.api.calls)
    def test_15_invalid_token(self):
        from bot.formatter import format_response
        self.assertIn('Unauthorized',format_response({'action':'get_quota'},{'status':'error','message':'Unauthorized'}))
    def test_16_unsupported_action(self): self.assertIsNone(validate_intent({'action':'write','filters':{}}))
    def test_17_sql_injection_intent(self):
        self.assertIsNone(validate_intent({'action':'search_applicant','filters':{'query':'x; DROP TABLE peserta'}}))
        self.assertIn('tidak dapat diproses', self.bot.handle_message(msg('/cari x; DROP TABLE peserta')))
        self.assertFalse(self.api.calls)
    def test_18_api_timeout(self):
        self.api.payload={'status':'error','message':'API Timeout'}; self.assertIn('API Timeout',self.bot.handle_message(msg('/kuota')))
    def test_19_multiple_result(self):
        self.api.payload={'status':'success','data':[applicant(1),applicant(2)]}; self.assertIn('Ani',self.bot.handle_message(msg('/cari Ani')))
    def test_20_detail_result(self):
        self.api.payload={'status':'success','data':applicant()}; self.assertIn('Biodata',self.bot.handle_message(msg('/biodata REG001')))
    def test_21_pagination_over_10(self):
        self.api.payload={'status':'success','data':[applicant(i) for i in range(11)]}; self.assertIn('Menampilkan 10 dari 11',self.bot.handle_message(msg('/cari Ani')))
    def test_22_write_intent_rejection(self): self.assertEqual(self.bot.handle_message(msg('ubah data Ani')),READ_ONLY_MESSAGE); self.assertFalse(self.api.calls)
if __name__ == '__main__': unittest.main()
