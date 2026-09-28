"""Telegram Markdown response rendering for API data."""
from __future__ import annotations

def _esc(value) -> str: return str(value if value is not None else '-').replace('_', '\\_').replace('*', '\\*').replace('`', "'")
def _applicant(item: dict, index: int | None = None) -> str:
    head = ('%d. ' % index) if index else ''
    return head + '*%s* (`%s`)\n%s | %s | %s' % (_esc(item.get('nama')), _esc(item.get('nomor_pendaftaran', item.get('id'))), _esc(item.get('asal_sekolah')), _esc(item.get('city')), _esc(item.get('verification_status')))
def _list(data) -> str:
    meta = data.get('meta', {}) if isinstance(data, dict) else {}
    if isinstance(data, dict) and 'data' in data:
        data, total = data['data'], meta.get('total', len(data['data']))
    else:
        data, total = (data, len(data)) if isinstance(data, list) else ([], 0)
    if not data: return 'Tidak ada pendaftar yang ditemukan.'
    shown = data[:10]
    text = '*Daftar Pendaftar*\n' + '\n\n'.join(_applicant(x, i) for i, x in enumerate(shown, 1))
    page = meta.get('current_page') or meta.get('page')
    pages = meta.get('last_page') or meta.get('total_pages')
    if page and pages: text += '\n\nHalaman %s dari %s' % (_esc(page), _esc(pages))
    return text + ('\n\nMenampilkan %d dari %d pendaftar' % (len(shown), total) if total > len(shown) else '')
def format_response(intent: dict, payload: dict) -> str:
    if payload.get('status') != 'success':
        code = payload.get('code')
        message = str(payload.get('message', ''))
        if code == 404:
            return 'Data pendaftar belum tersedia untuk tahun atau filter tersebut.'
        if message.lower() in {'missing required filter', 'invalid tahapan'}:
            return 'Filter pendaftar belum lengkap atau tidak dikenali. Sebutkan tahun ajaran dan tahap yang dicari.'
        return '*Terjadi Kesalahan*\n' + _esc(payload.get('message', 'API Error'))
    action, data = intent['action'], payload.get('data')
    if action == 'get_quota': return '*Kuota SPMB*\nTahun: %s\nKuota: *%s*\nTerdaftar: *%s*\nDalam kuota: %s\nWaiting list: %s' % (_esc((data.get('tahun_ajaran') or {}).get('nama')), _esc(data.get('quota')), _esc(data.get('registered')), _esc(data.get('within_quota')), _esc(data.get('waiting_list')))
    if action == 'get_statistics': return '*Statistik SPMB*\nTotal: *%s*\nTerverifikasi: %s\nMenunggu: %s\nDokumen lengkap: %s\nDokumen belum lengkap: %s' % tuple(_esc(data.get(k)) for k in ('total','verified','pending','complete_documents','incomplete_documents'))
    if action == 'gender_summary': return '*Ringkasan Gender*\nLaki-laki: *%s*\nPerempuan: *%s*\nBelum diisi: %s' % tuple(_esc(data.get(k)) for k in ('L','P','unknown'))
    if action == 'get_applicant_detail':
        if not data: return 'Pendaftar tidak ditemukan.'
        return '*Biodata Pendaftar*\nNama: *%s*\nNo. Pendaftaran: `%s`\nSekolah: %s\nKota: %s\nKecamatan: %s\nGender: %s\nVerifikasi: %s\nDokumen: %s' % tuple(_esc(data.get(k)) for k in ('nama','nomor_pendaftaran','asal_sekolah','city','district','gender','verification_status','document_status'))
    if action == 'get_extension_status':
        health = data.get('health', {}) if isinstance(data, dict) else {}
        return '*Status Admin Staging*\nStage: %s\nStatus: %s\nTest: %s\nHealth API: %s\nHealth AI fallback: %s\nBlocker: %s' % tuple(_esc(value) for value in (data.get('stage'), data.get('status'), data.get('test'), health.get('api_configured'), health.get('ai_fallback_configured'), data.get('blocker')))
    if action == 'get_applicant_progress':
        if not data: return 'Pendaftar tidak ditemukan.'
        return '*Progress Pendaftar*\nNama: *%s*\nStatus: %s\nTahapan: %s\nStatus kuota: %s\nDokumen: %s' % tuple(_esc(data.get(k)) for k in ('nama','verification_status','stage_label','status_kuota','document_status'))
    if action in {'lookup_payment_proof', 'lookup_document_proof'}:
        if not data: return 'Bukti tidak ditemukan.'
        return '*Lookup Bukti (metadata)*\nJenis: %s\nStatus: %s\nBukti tersedia: %s\nPengiriman file: tidak tersedia pada kontrak read API.' % tuple(_esc(data.get(k)) for k in ('kind','status','available'))
    return _list(data)
