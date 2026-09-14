"""Deterministic commands plus a tightly validated, intent-only AI fallback."""
from __future__ import annotations
import json, re
from urllib import request
from .config import Config

ACTIONS = frozenset({'get_quota','get_statistics','search_applicant','get_applicant_detail','list_by_city','list_by_district','list_by_school','list_by_document_status','list_by_verification_status','list_registered_today','gender_summary'})
FILTERS = frozenset({'query','identifier','city','district','school','document_status','verification_status'})
CONTROL_COMMANDS = frozenset({'/setupid', '/whoami'})
DANGEROUS = re.compile(r'(;|--|/\*|\*/|\b(union|select|drop|insert|delete|update|alter|exec)\b)', re.I)

def parse_control_command(text: str) -> str | None:
    value = text.strip()
    value = re.sub(r'^(/\w+)@\w+', r'\1', value, flags=re.I)
    command = value.lower()
    return command if command in CONTROL_COMMANDS else None

def parse_deterministic(text: str) -> dict | None:
    value = text.strip()
    # Strip a bot mention from commands before matching.
    value = re.sub(r'^(/\w+)@\w+', r'\1', value, flags=re.I)
    lower = value.lower()
    fixed = {'/kuota':'get_quota','kuota':'get_quota','/statistik':'get_statistics','statistik':'get_statistics','/hariini':'list_registered_today','hari ini':'list_registered_today','/belumverifikasi':'list_by_verification_status','belum verifikasi':'list_by_verification_status','/belumberkas':'list_by_document_status','belum berkas':'list_by_document_status','/gender':'gender_summary','gender':'gender_summary','/jeniskelamin':'gender_summary'}
    if lower in fixed:
        action = fixed[lower]; filters = {'verification_status':'menunggu'} if action == 'list_by_verification_status' else ({'document_status':'incomplete'} if action == 'list_by_document_status' else {})
        return {'action': action, 'filters': filters}
    for prefix, action, key in (('/cari ','search_applicant','query'),('cari ','search_applicant','query'),('/biodata ','get_applicant_detail','identifier'),('biodata ','get_applicant_detail','identifier'),('/kota ','list_by_city','city'),('kota ','list_by_city','city'),('/kecamatan ','list_by_district','district'),('kecamatan ','list_by_district','district'),('/sekolah ','list_by_school','school'),('sekolah ','list_by_school','school')):
        if lower.startswith(prefix):
            item = value[len(prefix):].strip()
            return {'action': action, 'filters': {key: item}} if item else None
    return None

def is_dangerous_text(text: str) -> bool:
    return bool(DANGEROUS.search(text or ''))

def validate_intent(intent: object) -> dict | None:
    if not isinstance(intent, dict) or set(intent) != {'action','filters'}: return None
    action, filters = intent['action'], intent['filters']
    if action not in ACTIONS or not isinstance(filters, dict) or not set(filters).issubset(FILTERS): return None
    if any(not isinstance(v, (str, int, float, bool)) or DANGEROUS.search(str(v)) for v in filters.values()): return None
    return {'action': action, 'filters': filters}

def parse_with_ai(text: str, config: Config) -> dict | None:
    prompt = ('Return ONLY JSON with exactly action and filters. You are an intent parser for a READ-ONLY admissions bot. '
              'Allowed actions: ' + ', '.join(sorted(ACTIONS)) + '. Allowed filter keys: ' + ', '.join(sorted(FILTERS)) + '. Never propose writes, SQL, or extra keys. User: ' + text)
    payload = json.dumps({'model': config.ai_fallback_model, 'messages': [{'role':'user','content':prompt}], 'temperature': 0}).encode()
    req = request.Request(config.ai_router_url.rstrip('/') + '/chat/completions', data=payload, headers={'Content-Type':'application/json'}, method='POST')
    try:
        with request.urlopen(req, timeout=10) as response:
            body = json.load(response)
        content = body['choices'][0]['message']['content']
        return validate_intent(json.loads(content))
    except (OSError, ValueError, KeyError, TypeError): return None
