"""Deterministic commands plus a tightly validated, intent-only AI fallback."""
from __future__ import annotations

import json
import re
from urllib import request

from .config import Config


ACTIONS = frozenset({
    'get_quota',
    'get_statistics',
    'search_applicant',
    'get_applicant_detail',
    'list_applicants',
    'list_by_city',
    'list_by_district',
    'list_by_school',
    'list_by_document_status',
    'list_by_verification_status',
    'list_registered_today',
    'gender_summary',
})

FILTERS = frozenset({
    'query',
    'identifier',
    'city',
    'district',
    'school',
    'document_status',
    'verification_status',
    'tahun_ajaran',
})

CONTROL_COMMANDS = frozenset({'/setupid', '/whoami'})

DANGEROUS = re.compile(
    r'(;|--|/\*|\*/|\b(union|select|drop|insert|delete|update|alter|exec)\b)',
    re.I,
)

ACADEMIC_YEAR_PATTERN = re.compile(
    r'(?<!\d)(\d{2,4})(?:\s*[/\-]\s*|\s+)(\d{2,4})(?!\d)'
)


def normalize_academic_year(value: object) -> str | None:
    if not isinstance(value, str):
        return None

    match = re.fullmatch(
        r'\s*(\d{2,4})(?:\s*[/\-]\s*|\s+)(\d{2,4})\s*',
        value,
    )

    if not match:
        return None

    raw_start, raw_end = match.group(1), match.group(2)
    start = int(raw_start)
    end = int(raw_end)
    if len(raw_start) == 2 and len(raw_end) == 2:
        start += 2000
        end += 2000

    if end != start + 1:
        return None

    return f'{start:04d}/{end:04d}'


def _extract_academic_year(text: str) -> tuple[str | None, str]:
    match = ACADEMIC_YEAR_PATTERN.search(text)

    if not match:
        return None, text

    normalized = normalize_academic_year(match.group(0))

    if normalized is None:
        return None, text

    remaining = text[:match.start()] + ' ' + text[match.end():]

    remaining = re.sub(
        r'\b(?:tahun\s+ajaran|tahun)\b',
        ' ',
        remaining,
        flags=re.I,
    )

    return normalized, remaining


def _clean_command(value: str) -> str:
    value = re.sub(r'[?!.,:]+', ' ', value)
    value = re.sub(r'\s+', ' ', value)
    return value.strip().lower()


def parse_control_command(text: str) -> str | None:
    value = text.strip()
    value = re.sub(r'^(/\w+)@\w+', r'\1', value, flags=re.I)
    command = value.lower()
    return command if command in CONTROL_COMMANDS else None


def parse_deterministic(text: str) -> dict | None:
    value = text.strip()

    # Strip mention, e.g. /kuota@spmb_sma_afbs_bot
    value = re.sub(r'^(/\w+)@\w+', r'\1', value, flags=re.I)

    year, without_year = _extract_academic_year(value)
    command = _clean_command(without_year)

    year_filters = {'tahun_ajaran': year} if year else {}

    # Read-only summary commands with optional academic year.
    if re.fullmatch(
        r'(?:/kuota|kuota|berapa\s+kuota)(?:\s+spmb)?',
        command,
    ):
        return {
            'action': 'get_quota',
            'filters': year_filters,
        }

    if re.fullmatch(
        r'(?:/statistik|statistik)'
        r'(?:\s+(?:pendaftar|spmb|pendaftar\s+spmb))?',
        command,
    ):
        return {
            'action': 'get_statistics',
            'filters': year_filters,
        }

    if re.fullmatch(
        r'(?:/gender|gender|/jeniskelamin|jeniskelamin|'
        r'jenis\s+kelamin|ringkasan\s+gender)'
        r'(?:\s+(?:pendaftar|spmb))?',
        command,
    ):
        return {
            'action': 'gender_summary',
            'filters': year_filters,
        }

    # Conversation-friendly applicant list requests with optional year.
    if re.fullmatch(r'(?:siapa\s+)?pendaftar|siapa\s+pendaftar', command):
        return {'action': 'list_applicants', 'filters': year_filters}

    if re.fullmatch(r'tampilkan\s+biodata\s+pendaftar', command):
        return {'action': 'list_applicants', 'filters': year_filters}

    # Existing exact commands.
    fixed = {
        '/hariini': 'list_registered_today',
        'hari ini': 'list_registered_today',
        '/belumverifikasi': 'list_by_verification_status',
        'belum verifikasi': 'list_by_verification_status',
        '/belumberkas': 'list_by_document_status',
        'belum berkas': 'list_by_document_status',
    }

    if command in fixed:
        action = fixed[command]

        filters = (
            {'verification_status': 'menunggu'}
            if action == 'list_by_verification_status'
            else (
                {'document_status': 'incomplete'}
                if action == 'list_by_document_status'
                else {}
            )
        )

        return {'action': action, 'filters': filters}

    # Existing commands with a value.
    for prefix, action, key in (
        ('/cari ', 'search_applicant', 'query'),
        ('cari ', 'search_applicant', 'query'),
        ('/biodata ', 'get_applicant_detail', 'identifier'),
        ('biodata ', 'get_applicant_detail', 'identifier'),
        ('/kota ', 'list_by_city', 'city'),
        ('kota ', 'list_by_city', 'city'),
        ('/kecamatan ', 'list_by_district', 'district'),
        ('kecamatan ', 'list_by_district', 'district'),
        ('/sekolah ', 'list_by_school', 'school'),
        ('sekolah ', 'list_by_school', 'school'),
    ):
        if command.startswith(prefix):
            item = without_year.strip()[len(prefix):].strip()

            if not item:
                return None

            return {
                'action': action,
                'filters': {key: item},
            }

    return None


def is_dangerous_text(text: str) -> bool:
    return bool(DANGEROUS.search(text or ''))


def validate_intent(intent: object) -> dict | None:
    if not isinstance(intent, dict):
        return None

    if set(intent) != {'action', 'filters'}:
        return None

    action = intent['action']
    filters = intent['filters']

    if action not in ACTIONS:
        return None

    if not isinstance(filters, dict):
        return None

    if not set(filters).issubset(FILTERS):
        return None

    validated_filters: dict = {}

    for key, value in filters.items():
        if key == 'tahun_ajaran':
            normalized = normalize_academic_year(value)

            if normalized is None:
                return None

            validated_filters[key] = normalized
            continue

        if not isinstance(value, (str, int, float, bool)):
            return None

        if DANGEROUS.search(str(value)):
            return None

        validated_filters[key] = value

    return {
        'action': action,
        'filters': validated_filters,
    }


def parse_with_ai(text: str, config: Config) -> dict | None:
    prompt = (
        'Return ONLY JSON with exactly action and filters. '
        'You are an intent parser for a READ-ONLY admissions bot. '
        'Allowed actions: '
        + ', '.join(sorted(ACTIONS))
        + '. Allowed filter keys: '
        + ', '.join(sorted(FILTERS))
        + '. '
        'If the user specifies an academic year, include '
        '"tahun_ajaran":"YYYY/YYYY" and normalize variants such as '
        '2027-2028 or 2027 2028 to 2027/2028. '
        'If no academic year is specified, omit tahun_ajaran. '
        'Never propose writes, SQL, or extra keys. '
        'User: '
        + text
    )

    payload = json.dumps({
        'model': config.ai_fallback_model,
        'messages': [
            {
                'role': 'user',
                'content': prompt,
            }
        ],
        'temperature': 0,
    }).encode()

    headers = {
        'Content-Type': 'application/json',
    }

    if config.ai_router_api_key:
        headers['Authorization'] = 'Bearer ' + config.ai_router_api_key

    req = request.Request(
        config.ai_router_url.rstrip('/') + '/chat/completions',
        data=payload,
        headers=headers,
        method='POST',
    )

    try:
        with request.urlopen(req, timeout=10) as response:
            body = json.load(response)

        content = body['choices'][0]['message']['content']
        return validate_intent(json.loads(content))

    except (OSError, ValueError, KeyError, TypeError):
        return None
