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
    'jenis_pendaftaran',
    'kelas_tujuan',
    'gender',
    'tahapan',
    'status_kuota',
    'page',
    'limit',
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

    if command in {'reset', '/reset', 'mulai lagi', 'hapus konteks'}:
        return {'action': 'reset', 'filters': {}}
    if command in {'lanjut', 'berikutnya', 'selanjutnya', 'next', 'next page', 'halaman berikutnya'}:
        return {'action': 'next_page', 'filters': {}}
    if command in {'kembali', 'sebelumnya', 'previous', 'prev', 'previous page', 'halaman sebelumnya'}:
        return {'action': 'previous_page', 'filters': {}}
    if command in {'siapa aja', 'siapa saja', 'tampilkan', 'lihat datanya', 'daftarnya', 'lihat daftar'}:
        return {'action': 'list_applicants', 'filters': year_filters}

    # Deterministic conversational filters. Values intentionally match the API contract.
    filter_patterns = (
        (r'(?:jalur\s+)?(?:siswa\s+baru|baru)', 'jenis_pendaftaran', 'siswa_baru'),
        (r'(?:jalur\s+)?(?:siswa\s+pindahan|pindahan|transfer)', 'jenis_pendaftaran', 'pindahan'),
        (r'(?:jenis\s+kelamin\s+)?(?:laki[ -]?laki|laki|pria|\bL\b)', 'gender', 'L'),
        (r'(?:jenis\s+kelamin\s+)?(?:perempuan|wanita|\bP\b)', 'gender', 'P'),
        (r'(?:sudah|telah)\s+verifikasi|terverifikasi', 'verification_status', 'terverifikasi'),
        (r'(?:belum|menunggu)\s+verifikasi', 'verification_status', 'menunggu'),
        (r'(?:berkas|dokumen)\s+lengkap', 'document_status', 'complete'),
        (r'(?:berkas|dokumen)\s+belum\s+lengkap', 'document_status', 'incomplete'),
        (r'dalam\s+kuota', 'status_kuota', 'dalam_kuota'),
        (r'waiting\s*list', 'status_kuota', 'waiting_list'),
    )
    extracted = dict(year_filters)
    remaining = command
    for pattern, key, val in filter_patterns:
        if re.search(pattern, remaining, re.I):
            extracted[key] = val
            remaining = re.sub(pattern, ' ', remaining, flags=re.I)
    class_match = re.search(r'kelas\s*(10|11|12)', remaining, re.I)
    if class_match:
        extracted['kelas_tujuan'] = int(class_match.group(1))
        remaining = remaining[:class_match.start()] + ' ' + remaining[class_match.end():]
    if extracted and (remaining.strip() in {'', 'pendaftar', 'siswa', 'data pendaftar', 'data siswa', 'jalur'}):
        if extracted.get('jenis_pendaftaran') == 'siswa_baru' and extracted.get('kelas_tujuan') == 11:
            return {'action': 'incompatible_filter', 'filters': extracted}
        return {'action': 'list_applicants', 'filters': extracted}

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
    if re.fullmatch(r'(?:siapa\s+(?:aja|saja)\s+)?pendaftar|siapa\s+pendaftar', command):
        return {'action': 'list_applicants', 'filters': year_filters}

    if re.fullmatch(r'(?:lihat|tampilkan)\s+(?:data\s+)?pendaftar', command):
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


def parse_number_selection(text: str) -> int | None:
    """Return a 1-based list position for supported conversational forms."""
    value = _clean_command(text)
    match = re.fullmatch(
        r'(?:cek\s+)?(?:biodata\s+)?(?:nomor|no|yang\s+(?:nomor|ke)|yg\s+(?:nomor|ke)|yang)\s*'
        r'(\d+|pertama|satu|kedua|dua|ketiga|tiga|keempat|empat|kelima|lima)(?:\s+itu)?',
        value,
        re.I,
    )
    if not match:
        match = re.fullmatch(
            r'(?:cek\s+)?(?:yang|yg)\s+'
            r'(\d+|pertama|satu|kedua|dua|ketiga|tiga|keempat|empat|kelima|lima)(?:\s+itu)?',
            value,
            re.I,
        )
    if not match:
        return None
    raw = match.group(1).lower()
    return int(raw) if raw.isdigit() else {
        'pertama': 1, 'satu': 1, 'kedua': 2, 'dua': 2,
        'ketiga': 3, 'tiga': 3, 'keempat': 4, 'empat': 4,
        'kelima': 5, 'lima': 5,
    }[raw]


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


FALLBACK_MESSAGE = 'Maaf, saya belum memahami pertanyaan itu. Coba tulis lebih spesifik.'
RUNTIME_AI_MODEL = 'cx/gpt-5.6-luna'


def _trusted_router_url(value: object) -> str | None:
    if not isinstance(value, str):
        return None
    from urllib.parse import urlparse
    candidate = value.strip().rstrip('/')
    parsed = urlparse(candidate)
    if parsed.scheme not in {'http', 'https'} or not parsed.netloc:
        return None
    if parsed.username or parsed.password or parsed.query or parsed.fragment:
        return None
    return candidate


def parse_with_ai(text: str, config: Config, context: dict | None = None) -> dict | None:
    router_url = _trusted_router_url(config.ai_router_url)
    if not router_url or not isinstance(text, str) or not text.strip():
        return None
    safe_context = {}
    if isinstance(context, dict):
        safe_context = {key: value for key, value in context.items()
                        if key in {'filters', 'page', 'last_page', 'last_action'}}
    prompt = (
        'You classify one user request for a READ-ONLY SPMB admissions bot. '
        'Return ONLY one JSON object with exactly the keys action and filters; '
        'no markdown, explanation, SQL, or other keys. Allowed actions: '
        + ', '.join(sorted(ACTIONS)) + '. Allowed filter keys: '
        + ', '.join(sorted(FILTERS)) + '. Normalize academic years (26/27, '
        '2026-2027, 2026 2027) to YYYY/YYYY. Interpret typos kupta/statitisk, '
        'jalur siswa baru/pindahan, and gender laki-laki/perempuan. Use only '
        'requested or context-compatible filters; never invent PII. Sanitized '
        'context: ' + json.dumps(safe_context, ensure_ascii=False, separators=(',', ':'))
        + '. User request: ' + text
    )

    payload = json.dumps({
        'model': RUNTIME_AI_MODEL,
        'messages': [{'role': 'system', 'content': prompt}],
        'temperature': 0,
        'max_tokens': 256,
    }).encode()
    headers = {'Content-Type': 'application/json'}
    if config.ai_router_api_key:
        headers['Authorization'] = 'Bearer ' + config.ai_router_api_key

    req = request.Request(
        router_url + '/chat/completions',
        data=payload,
        headers=headers,
        method='POST',
    )

    try:
        with request.urlopen(req, timeout=10) as response:
            body = json.load(response)

        content = body['choices'][0]['message']['content']
        if not isinstance(content, str) or not content.strip().startswith('{') or not content.strip().endswith('}'):
            return None
        return validate_intent(json.loads(content))
    except (OSError, ValueError, KeyError, TypeError, json.JSONDecodeError):
        return None
