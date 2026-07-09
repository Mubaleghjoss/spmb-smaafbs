@extends('layouts.admin')

@section('title', 'Verifikasi Kelulusan')

@section('content')
<style>
    .kelulusan-page {
        --kelulusan-border: #e2e8f0;
        --kelulusan-muted: #64748b;
        --kelulusan-surface: #ffffff;
    }

    .kelulusan-hero {
        background: linear-gradient(135deg, #064e3b 0%, #0f766e 55%, #0ea5e9 100%);
        color: #fff;
        border-radius: 8px;
        padding: 22px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
    }

    .kelulusan-stat,
    .kelulusan-toolbar,
    .kelulusan-table-card {
        border: 1px solid var(--kelulusan-border);
        border-radius: 8px;
        background: var(--kelulusan-surface);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .kelulusan-stat {
        padding: 16px;
        height: 100%;
    }

    .kelulusan-stat .icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 1.25rem;
    }

    .kelulusan-table thead th {
        color: #475569;
        font-size: 0.72rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .kelulusan-table tbody td {
        vertical-align: middle;
    }

    .kelulusan-row-title {
        min-width: 210px;
    }

    .kelulusan-empty {
        border: 1px dashed var(--kelulusan-border);
        border-radius: 8px;
        padding: 36px 16px;
    }

    .sk-select {
        min-width: 160px;
        max-width: 190px;
    }

    @media (max-width: 768px) {
        .kelulusan-hero {
            padding: 18px;
        }

        .kelulusan-actions {
            width: 100%;
        }

        .kelulusan-actions .btn,
        .kelulusan-actions .form-select {
            width: 100%;
            max-width: none;
        }
    }
</style>

<div class="container-fluid py-4 kelulusan-page">
    <div class="kelulusan-hero mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <div class="small text-white-50 mb-1">Tahap 7 SPMB</div>
                <h4 class="mb-1"><i class="bi bi-mortarboard me-2"></i>Verifikasi Kelulusan</h4>
                <p class="mb-0 text-white-50">Kelola keputusan akhir peserta dan SK gelombang dalam satu daftar lengkap.</p>
            </div>
            <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap7" class="btn btn-light">
                <i class="bi bi-gear me-1"></i>Pengaturan Kelulusan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(empty($skGelombang))
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle mt-1"></i>
            <div>
                Belum ada SK gelombang. Tambahkan file SK di
                <a href="{{ route('admin.pengaturan.spmb') }}?tab=tahap7" class="alert-link">Pengaturan SPMB - Kelulusan</a>
                sebelum meluluskan peserta.
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="kelulusan-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-people"></i></span>
                    <div>
                        <div class="h3 mb-0">{{ $statistik['total'] ?? $peserta->count() }}</div>
                        <div class="small text-muted">Total Tahap 7</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kelulusan-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-hourglass-split"></i></span>
                    <div>
                        <div class="h3 mb-0">{{ $statistik['menunggu'] }}</div>
                        <div class="small text-muted">Menunggu</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kelulusan-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="icon bg-success bg-opacity-10 text-success"><i class="bi bi-check-circle"></i></span>
                    <div>
                        <div class="h3 mb-0">{{ $statistik['lulus'] }}</div>
                        <div class="small text-muted">Lulus</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="kelulusan-stat">
                <div class="d-flex align-items-center gap-3">
                    <span class="icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></span>
                    <div>
                        <div class="h3 mb-0">{{ $statistik['tidak_lulus'] }}</div>
                        <div class="small text-muted">Tidak Lulus</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="kelulusan-toolbar p-3 mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4">
                <label for="searchKelulusan" class="form-label small text-muted mb-1">Cari peserta</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="search" id="searchKelulusan" class="form-control" placeholder="Nama, nomor, asal sekolah">
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <label for="filterStatusKelulusan" class="form-label small text-muted mb-1">Status</label>
                <select id="filterStatusKelulusan" class="form-select">
                    <option value="semua">Semua status</option>
                    <option value="menunggu">Menunggu</option>
                    <option value="lulus">Lulus</option>
                    <option value="tidak_lulus">Tidak Lulus</option>
                </select>
            </div>
            <div class="col-lg-6">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2 kelulusan-actions">
                    <div class="form-check d-flex align-items-center gap-2 px-0">
                        <input type="checkbox" class="form-check-input m-0" id="selectAll" onchange="toggleSelectAll(this)">
                        <label class="form-check-label" for="selectAll">Pilih semua tampil</label>
                    </div>
                    <span class="badge bg-secondary align-self-center" id="selectedCount">0 dipilih</span>
                    <select class="form-select form-select-sm sk-select" id="batchSkGelombang" {{ empty($skGelombang) ? 'disabled' : '' }}>
                        <option value="">Pilih SK Gelombang</option>
                        @foreach($skGelombang as $sk)
                            <option value="{{ $sk['id'] }}">{{ $sk['nama'] }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-success btn-sm" onclick="luluskanBatch()" id="btnLuluskanBatch" disabled>
                        <i class="bi bi-check-circle me-1"></i>Luluskan
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="tidakLulusBatch()" id="btnTidakLulusBatch" disabled>
                        <i class="bi bi-x-circle me-1"></i>Tidak Lulus
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="kelulusan-table-card">
        <div class="p-3 border-bottom">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="mb-1"><i class="bi bi-list-check me-2"></i>Daftar Peserta Tahap 7</h5>
                    <div class="small text-muted">
                        Menampilkan semua <span id="visibleRowsCount">{{ $peserta->count() }}</span> dari {{ $peserta->count() }} data.
                    </div>
                </div>
                <form action="{{ route('admin.verifikasi.kelulusan.luluskan-semua') }}" method="POST" class="d-flex flex-wrap gap-2"
                      onsubmit="return confirm('Yakin ingin meluluskan semua peserta yang menunggu?')">
                    @csrf
                    <select name="sk_gelombang_kelulusan" class="form-select form-select-sm sk-select" required {{ empty($skGelombang) ? 'disabled' : '' }}>
                        <option value="">Pilih SK</option>
                        @foreach($skGelombang as $sk)
                            <option value="{{ $sk['id'] }}">{{ $sk['nama'] }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm" {{ empty($skGelombang) ? 'disabled' : '' }}>
                        <i class="bi bi-check-all me-1"></i>Luluskan Semua Menunggu ({{ $statistik['menunggu'] }})
                    </button>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 kelulusan-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 44px"></th>
                        <th>Peserta</th>
                        <th>Asal Sekolah</th>
                        <th>Tahap</th>
                        <th>Status</th>
                        <th>SK Gelombang</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="kelulusanTableBody">
                    @forelse($peserta as $p)
                        @php
                            $tahap = $p->tahapanSpmb?->tahap_saat_ini ?? 1;
                            $statusKelulusan = $p->tahapanSpmb?->status_kelulusan ?? 'menunggu';
                            $statusKelulusan = $statusKelulusan ?: 'menunggu';
                            $tahap7Selesai = $p->tahapanSpmb?->tahap_7_selesai ?? false;
                            $selectedSk = $p->tahapanSpmb?->sk_gelombang_kelulusan;
                            $selectedSkLabel = $selectedSk && isset($skGelombangById[$selectedSk]) ? $skGelombangById[$selectedSk]['nama'] : null;
                            $asalSekolah = $p->formulirSpmb?->asal_sekolah ?: ($p->asal_sekolah ?: '-');
                            $isMenunggu = $statusKelulusan === 'menunggu';
                            $rowSearch = strtolower(trim($p->nomor_pendaftaran . ' ' . $p->nama . ' ' . $asalSekolah));
                        @endphp
                        <tr class="kelulusan-row" data-status="{{ $statusKelulusan }}" data-search="{{ $rowSearch }}">
                            <td>
                                @if($isMenunggu)
                                    <input type="checkbox" class="form-check-input peserta-checkbox" value="{{ $p->id }}" onchange="updateSelectedCount()">
                                @endif
                            </td>
                            <td class="kelulusan-row-title">
                                <div class="fw-semibold">{{ $p->nama }}</div>
                                <div class="small text-muted"><code>{{ $p->nomor_pendaftaran }}</code></div>
                            </td>
                            <td>{{ $asalSekolah }}</td>
                            <td>
                                <span class="badge rounded-pill bg-{{ $tahap == 7 ? 'success' : 'primary' }}">Tahap {{ $tahap }}</span>
                            </td>
                            <td>
                                @if($tahap7Selesai && $statusKelulusan === 'lulus')
                                    <span class="badge rounded-pill bg-success"><i class="bi bi-check-circle me-1"></i>Lulus</span>
                                @elseif($statusKelulusan === 'tidak_lulus')
                                    <span class="badge rounded-pill bg-danger"><i class="bi bi-x-circle me-1"></i>Tidak Lulus</span>
                                @else
                                    <span class="badge rounded-pill bg-warning text-dark"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                @endif
                            </td>
                            <td>
                                @if($selectedSkLabel)
                                    <span class="badge rounded-pill bg-info text-dark"><i class="bi bi-file-earmark-pdf me-1"></i>{{ $selectedSkLabel }}</span>
                                @else
                                    <span class="text-muted small">Belum dipilih</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    @if($statusKelulusan !== 'lulus' || !$tahap7Selesai)
                                        <form action="{{ route('admin.verifikasi.kelulusan.luluskan', $p) }}" method="POST" class="d-inline-flex gap-1">
                                            @csrf
                                            <select name="sk_gelombang_kelulusan" class="form-select form-select-sm sk-select" required {{ empty($skGelombang) ? 'disabled' : '' }}>
                                                <option value="">Pilih SK</option>
                                                @foreach($skGelombang as $sk)
                                                    <option value="{{ $sk['id'] }}" {{ (string) $selectedSk === (string) $sk['id'] ? 'selected' : '' }}>{{ $sk['nama'] }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-success btn-sm" title="Luluskan" {{ empty($skGelombang) ? 'disabled' : '' }}>
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        @if($statusKelulusan !== 'tidak_lulus')
                                            <form action="{{ route('admin.verifikasi.kelulusan.tidak-lulus', $p) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Tidak Lulus">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <form action="{{ route('admin.verifikasi.kelulusan.luluskan', $p) }}" method="POST" class="d-inline-flex gap-1">
                                            @csrf
                                            <select name="sk_gelombang_kelulusan" class="form-select form-select-sm sk-select" required {{ empty($skGelombang) ? 'disabled' : '' }}>
                                                <option value="">Pilih SK</option>
                                                @foreach($skGelombang as $sk)
                                                    <option value="{{ $sk['id'] }}" {{ (string) $selectedSk === (string) $sk['id'] ? 'selected' : '' }}>{{ $sk['nama'] }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-outline-success btn-sm" title="Update SK Gelombang" {{ empty($skGelombang) ? 'disabled' : '' }}>
                                                <i class="bi bi-save"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="kelulusan-empty">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-muted"></i>
                                    <div class="fw-semibold">Belum ada peserta tahap 7</div>
                                    <div class="small text-muted">Peserta akan muncul setelah mencapai tahap kelulusan.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(!empty($pengaturanKelulusan['keterangan_lulus']) || !empty($pengaturanKelulusan['keterangan_tidak_lulus']))
        <div class="kelulusan-table-card mt-4">
            <div class="p-3 border-bottom">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Keterangan yang Ditampilkan ke Peserta</h6>
            </div>
            <div class="p-3">
                <div class="row g-3">
                    @if(!empty($pengaturanKelulusan['keterangan_lulus']))
                        <div class="col-md-6">
                            <div class="alert alert-success mb-0">
                                <h6 class="alert-heading"><i class="bi bi-check-circle me-1"></i>Jika Lulus</h6>
                                <p class="mb-0 small">{!! nl2br(e($pengaturanKelulusan['keterangan_lulus'])) !!}</p>
                            </div>
                        </div>
                    @endif
                    @if(!empty($pengaturanKelulusan['keterangan_tidak_lulus']))
                        <div class="col-md-6">
                            <div class="alert alert-danger mb-0">
                                <h6 class="alert-heading"><i class="bi bi-x-circle me-1"></i>Jika Tidak Lulus</h6>
                                <p class="mb-0 small">{!! nl2br(e($pengaturanKelulusan['keterangan_tidak_lulus'])) !!}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<form id="formLuluskanBatch" action="{{ route('admin.verifikasi.kelulusan.luluskan-batch') }}" method="POST">
    @csrf
    <input type="hidden" name="peserta_ids" id="pesertaIdsLulus">
    <input type="hidden" name="sk_gelombang_kelulusan" id="skGelombangLulus">
</form>

<form id="formTidakLulusBatch" action="{{ route('admin.verifikasi.kelulusan.tidak-lulus-batch') }}" method="POST">
    @csrf
    <input type="hidden" name="peserta_ids" id="pesertaIdsTidakLulus">
</form>
@endsection

@push('scripts')
<script>
const searchInput = document.getElementById('searchKelulusan');
const statusFilter = document.getElementById('filterStatusKelulusan');

function visibleRows() {
    return Array.from(document.querySelectorAll('.kelulusan-row')).filter(row => row.style.display !== 'none');
}

function eligibleVisibleCheckboxes() {
    return visibleRows().map(row => row.querySelector('.peserta-checkbox')).filter(Boolean);
}

function applyKelulusanFilter() {
    const keyword = (searchInput.value || '').trim().toLowerCase();
    const status = statusFilter.value || 'semua';
    let visibleCount = 0;

    document.querySelectorAll('.kelulusan-row').forEach(row => {
        const matchesSearch = !keyword || (row.dataset.search || '').includes(keyword);
        const matchesStatus = status === 'semua' || row.dataset.status === status;
        const isVisible = matchesSearch && matchesStatus;

        row.style.display = isVisible ? '' : 'none';

        if (!isVisible) {
            const checkbox = row.querySelector('.peserta-checkbox');
            if (checkbox) checkbox.checked = false;
        } else {
            visibleCount++;
        }
    });

    document.getElementById('visibleRowsCount').textContent = visibleCount;
    updateSelectedCount();
}

function toggleSelectAll(checkbox) {
    eligibleVisibleCheckboxes().forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const eligible = eligibleVisibleCheckboxes();
    const checked = eligible.filter(cb => cb.checked);
    const count = checked.length;

    document.getElementById('selectedCount').textContent = count + ' dipilih';
    document.getElementById('btnLuluskanBatch').disabled = count === 0;
    document.getElementById('btnTidakLulusBatch').disabled = count === 0;

    const selectAll = document.getElementById('selectAll');
    selectAll.checked = eligible.length > 0 && count === eligible.length;
    selectAll.indeterminate = count > 0 && count < eligible.length;
}

function selectedParticipantIds() {
    return eligibleVisibleCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
}

function luluskanBatch() {
    const ids = selectedParticipantIds();
    if (ids.length === 0) {
        alert('Pilih peserta terlebih dahulu');
        return;
    }

    const skGelombang = document.getElementById('batchSkGelombang').value;
    if (!skGelombang) {
        alert('Pilih SK gelombang terlebih dahulu');
        return;
    }

    if (!confirm('Yakin ingin meluluskan ' + ids.length + ' peserta terpilih?')) return;

    document.getElementById('pesertaIdsLulus').value = ids.join(',');
    document.getElementById('skGelombangLulus').value = skGelombang;
    document.getElementById('formLuluskanBatch').submit();
}

function tidakLulusBatch() {
    const ids = selectedParticipantIds();
    if (ids.length === 0) {
        alert('Pilih peserta terlebih dahulu');
        return;
    }

    if (!confirm('Yakin ingin menandai ' + ids.length + ' peserta sebagai tidak lulus?')) return;

    document.getElementById('pesertaIdsTidakLulus').value = ids.join(',');
    document.getElementById('formTidakLulusBatch').submit();
}

if (searchInput && statusFilter) {
    searchInput.addEventListener('input', applyKelulusanFilter);
    statusFilter.addEventListener('change', applyKelulusanFilter);
}
</script>
@endpush
