@extends('layouts.admin')

@section('title', 'Penanda Grup WhatsApp Tahap 4')

@section('content')
<div class="container-fluid py-4">
    @include('partials.periode-banner')

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="mb-1"><i class="bi bi-whatsapp text-success me-2"></i>Penanda Grup WhatsApp — Tahap 4</h4>
            <p class="text-muted mb-0">Daftar peserta yang sudah menyelesaikan Tahap 4. Status hanya berubah melalui konfirmasi manual admin.</p>
        </div>
        <a href="{{ route('admin.verifikasi.index', ['tahap' => 4, 'antrean' => 0]) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Kembali ke Verifikasi
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="status">Status grup WhatsApp</label>
                    <select class="form-select" id="status" name="status">
                        <option value="semua" @selected(($filter['status'] ?? 'semua') === 'semua')>Semua status</option>
                        <option value="belum" @selected(($filter['status'] ?? '') === 'belum')>Belum masuk</option>
                        <option value="sudah" @selected(($filter['status'] ?? '') === 'sudah')>Sudah masuk</option>
                        <option value="dikecualikan" @selected(($filter['status'] ?? '') === 'dikecualikan')>Dikecualikan</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="cari">Cari peserta</label>
                    <input type="text" class="form-control" id="cari" name="cari" value="{{ $filter['cari'] ?? '' }}" placeholder="Nama atau nomor pendaftaran">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                    <a href="{{ route('admin.verifikasi.grup-wa') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Peserta Tahap 4</h5>
            <span class="badge bg-secondary">{{ $peserta->total() }} peserta</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Pendaftaran</th>
                        <th>Nama</th>
                        <th>Status Grup WA</th>
                        <th>Penanda</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($peserta as $p)
                        @php($tahapan = $p->tahapanSpmb)
                        @php($statusGrup = $tahapan?->grup_wa_status ?: 'belum')
                        <tr>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>{{ $p->nama }}</td>
                            <td>
                                @if($statusGrup === 'sudah')
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sudah Masuk Grup WA</span>
                                @elseif($statusGrup === 'dikecualikan')
                                    <span class="badge bg-secondary"><i class="bi bi-dash-circle me-1"></i>Dikecualikan</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Belum Masuk Grup WA</span>
                                @endif
                                @if($tahapan?->grup_wa_catatan)
                                    <div class="small text-muted mt-1">Catatan: {{ $tahapan->grup_wa_catatan }}</div>
                                @endif
                            </td>
                            <td>
                                @if($tahapan?->grup_wa_ditandai_pada)
                                    <div>{{ $tahapan->grupWaPenanda?->nama ?? 'Admin' }}</div>
                                    <small class="text-muted">{{ $tahapan->grup_wa_ditandai_pada->format('d/m/Y H:i') }}</small>
                                @else
                                    <span class="text-muted">Belum ditandai</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.verifikasi.grup-wa.tandai', $p) }}" class="d-inline-flex flex-wrap justify-content-end gap-1">
                                    @csrf
                                    <input type="hidden" name="status" value="sudah">
                                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Catatan (opsional)" value="{{ $tahapan?->grup_wa_catatan }}" style="max-width: 190px">
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Tandai peserta ini sudah masuk grup WhatsApp?')">
                                        <i class="bi bi-check2 me-1"></i>Tandai Sudah Masuk
                                    </button>
                                </form>
                                @if($statusGrup !== 'dikecualikan')
                                    <form method="POST" action="{{ route('admin.verifikasi.grup-wa.tandai', $p) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="dikecualikan">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary mt-1" onclick="return confirm('Tandai peserta ini sebagai dikecualikan?')">Dikecualikan</button>
                                    </form>
                                @endif
                                @if($statusGrup !== 'belum')
                                    <form method="POST" action="{{ route('admin.verifikasi.grup-wa.tandai', $p) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="status" value="belum">
                                        <button type="submit" class="btn btn-sm btn-outline-warning mt-1">Kembalikan Belum</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada peserta sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($peserta->hasPages())
            <div class="card-footer bg-white">{{ $peserta->links() }}</div>
        @endif
    </div>
</div>
@endsection
