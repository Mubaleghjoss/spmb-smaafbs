"""Telegram Markdown response rendering for API data."""
from __future__ import annotations

def _esc(value) -> str: return str(value if value is not None else '-').replace('_', '\\_').replace('*', '\\*').replace('`', "'")
def _applicant(item: dict, index: int | None = None) -> str:
    head = ('%d. ' % index) if index else ''
    return head + '*%s* (`%s`)\n%s | %s | %s' % (_esc(item.get('nama')), _esc(item.get('nomor_pendaftaran', item.get('id'))), _esc(item.get('asal_sekolah')), _esc(item.get('city')), _esc(item.get('verification_status')))
def _list(data) -> str:
    if isinstance(data, dict) and 'data' in data: data, total = data['data'], data.get('meta', {}).get('total', len(data['data']))
    else: total = len(data) if isinstance(data, list) else 0
    if not data: return 'Tidak ada pendaftar yang ditemukan.'
    shown = data[:10]
    text = '*Daftar Pendaftar*\n' + '\n\n'.join(_applicant(x, i) for i, x in enumerate(shown, 1))
    return text + ('\n\nMenampilkan 10 dari %d pendaftar' % total if total > 10 else '')
def format_response(intent: dict, payload: dict) -> str:
    if payload.get('status') != 'success':
        return '*Terjadi Kesalahan*\n' + _esc(payload.get('message', 'API Error'))
    action, data = intent['action'], payload.get('data')
    if action == 'get_quota': return '*Kuota SPMB*\nTahun: %s\nKuota: *%s*\nTerdaftar: *%s*\nDalam kuota: %s\nWaiting list: %s' % (_esc((data.get('tahun_ajaran') or {}).get('nama')), _esc(data.get('quota')), _esc(data.get('registered')), _esc(data.get('within_quota')), _esc(data.get('waiting_list')))
    if action == 'get_statistics': return '*Statistik SPMB*\nTotal: *%s*\nTerverifikasi: %s\nMenunggu: %s\nDokumen lengkap: %s\nDokumen belum lengkap: %s' % tuple(_esc(data.get(k)) for k in ('total','verified','pending','complete_documents','incomplete_documents'))
    if action == 'gender_summary': return '*Ringkasan Gender*\nLaki-laki: *%s*\nPerempuan: *%s*\nBelum diisi: %s' % tuple(_esc(data.get(k)) for k in ('L','P','unknown'))
    if action == 'get_applicant_detail':
        if not data: return 'Pendaftar tidak ditemukan.'
        return '*Biodata Pendaftar*\nNama: *%s*\nNo. Pendaftaran: `%s`\nSekolah: %s\nKota: %s\nKecamatan: %s\nGender: %s\nVerifikasi: %s\nDokumen: %s' % tuple(_esc(data.get(k)) for k in ('nama','nomor_pendaftaran','asal_sekolah','city','district','gender','verification_status','document_status'))
    return _list(data)
