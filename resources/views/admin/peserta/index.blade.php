@extends('layouts.admin')

@section('title', 'Manajemen Peserta')

@section('content')
<div class="container-fluid">
    @php
        $tahapLabels = [
            1 => 'Pendaftaran',
            2 => 'Isi Formulir',
            3 => 'Bayar Formulir',
            4 => 'Tes Online',
            5 => 'Wawancara',
            6 => 'Pelunasan',
            7 => 'Kelulusan',
        ];
    @endphp

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h1 class="h3 mb-0">Manajemen Peserta</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.peserta.download-akun') }}" class="btn btn-sm btn-outline-info">
                <i class="bi bi-download"></i> <span class="d-none d-sm-inline">Download</span> Akun
            </a>
            <a href="{{ route('admin.peserta.download-biodata') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet"></i> <span class="d-none d-sm-inline">Download</span> Biodata
            </a>
            <a href="{{ route('admin.peserta.grup.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-people"></i> Grup
            </a>
            <a href="{{ route('admin.peserta.impor') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-upload"></i> Impor
            </a>
            <a href="{{ route('admin.peserta.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
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

    <!-- Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Total Peserta</h6>
                    <h2 class="mb-0">{{ $statistik['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Peserta Aktif</h6>
                    <h2 class="mb-0">{{ $statistik['aktif'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Tahap Tes</h6>
                    <h2 class="mb-0">{{ $statistik['per_tahap'][4] ?? 0 }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Diterima</h6>
                    <h2 class="mb-0">{{ $statistik['per_tahap'][7] ?? 0 }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Grup</label>
                    <select name="grup_id" class="form-select">
                        <option value="">Semua Grup</option>
                        @foreach($grup as $g)
                            <option value="{{ $g->id }}" {{ $filter['grup_id'] == $g->id ? 'selected' : '' }}>
                                {{ $g->nama }} ({{ $g->peserta_count }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tahapan</label>
                    <select name="tahap" class="form-select">
                        <option value="">Semua Tahap</option>
                        @foreach($tahapLabels as $i => $label)
                            <option value="{{ $i }}" {{ $filter['tahap'] == $i ? 'selected' : '' }}>Tahap {{ $i }} - {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tahun Ajaran</label>
                    <select name="tahun_ajaran_id" class="form-select" id="filterTahunAjaran"
                            onchange="filterGelombangByTahun(this.value, 'filterGelombang')">
                        <option value="">Semua Tahun</option>
                        @foreach($tahunAjaran as $tahun)
                            <option value="{{ $tahun->id }}" {{ (string) $filter['tahun_ajaran_id'] === (string) $tahun->id ? 'selected' : '' }}>
                                {{ $tahun->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Gelombang</label>
                    <select name="gelombang_pendaftaran_id" class="form-select" id="filterGelombang">
                        <option value="">Semua Gelombang</option>
                        @foreach($gelombangPendaftaran as $gelombang)
                            <option value="{{ $gelombang->id }}"
                                    data-tahun="{{ $gelombang->tahun_ajaran_id }}"
                                    {{ (string) $filter['gelombang_pendaftaran_id'] === (string) $gelombang->id ? 'selected' : '' }}>
                                {{ $gelombang->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Jenis</label>
                    <select name="jenis_pendaftaran" class="form-select">
                        <option value="">Semua Jenis</option>
                        <option value="siswa_baru" {{ $filter['jenis_pendaftaran'] === 'siswa_baru' ? 'selected' : '' }}>Siswa Baru</option>
                        <option value="pindahan" {{ $filter['jenis_pendaftaran'] === 'pindahan' ? 'selected' : '' }}>Pindahan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kelas</label>
                    <select name="kelas_tujuan" class="form-select">
                        <option value="">Semua Kelas</option>
                        <option value="10" {{ (string) $filter['kelas_tujuan'] === '10' ? 'selected' : '' }}>Kelas 10</option>
                        <option value="11" {{ (string) $filter['kelas_tujuan'] === '11' ? 'selected' : '' }}>Kelas 11</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status Kuota</label>
                    <select name="status_kuota" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="{{ \App\Models\Peserta::STATUS_KUOTA_DALAM }}" {{ $filter['status_kuota'] === \App\Models\Peserta::STATUS_KUOTA_DALAM ? 'selected' : '' }}>Masuk Kuota</option>
                        <option value="{{ \App\Models\Peserta::STATUS_KUOTA_WAITING }}" {{ $filter['status_kuota'] === \App\Models\Peserta::STATUS_KUOTA_WAITING ? 'selected' : '' }}>Waiting List</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="cari" class="form-control" placeholder="Nama/No. Pendaftaran/Email" value="{{ $filter['cari'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="form-check">
                        <input type="checkbox" name="dengan_dihapus" value="1" class="form-check-input" 
                               id="denganDihapus" {{ $filter['dengan_dihapus'] ? 'checked' : '' }}>
                        <label class="form-check-label" for="denganDihapus">Tampilkan dihapus</label>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Bulk Assign (hidden) -->
    <form id="bulkAssignForm" action="{{ route('admin.peserta.bulk-assign-grup') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="grup_id" id="bulkGrupId">
        <div id="selectedPesertaIds"></div>
    </form>

    <!-- Form Bulk Update Tahap (hidden) -->
    <form id="bulkTahapForm" action="{{ route('admin.peserta.bulk-update-tahap') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="tahap_baru" id="bulkTahapBaru">
        <input type="hidden" name="luluskan_final" id="bulkLuluskanFinalValue" value="0">
        <input type="hidden" name="sk_gelombang_kelulusan" id="bulkSkGelombangValue">
        <div id="selectedPesertaIdsTahap"></div>
    </form>

    <form id="bulkKategoriForm" action="{{ route('admin.peserta.bulk-update-kategori') }}" method="POST">
        @csrf
        <div id="selectedPesertaIdsKategori"></div>
        <div class="modal fade" id="bulkKategoriModal" tabindex="-1" aria-labelledby="bulkKategoriLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bulkKategoriLabel">Ubah Kategori Pendaftaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tahun Ajaran</label>
                                <select name="tahun_ajaran_id" id="bulkTahunAjaran" class="form-select"
                                        onchange="filterGelombangByTahun(this.value, 'bulkGelombang')" required>
                                    <option value="">Pilih tahun</option>
                                    @foreach($tahunAjaran as $tahun)
                                        <option value="{{ $tahun->id }}">{{ $tahun->nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gelombang</label>
                                <select name="gelombang_pendaftaran_id" id="bulkGelombang" class="form-select" required>
                                    <option value="">Pilih gelombang</option>
                                    @foreach($gelombangPendaftaran as $gelombang)
                                        <option value="{{ $gelombang->id }}" data-tahun="{{ $gelombang->tahun_ajaran_id }}">
                                            {{ $gelombang->nama }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jenis Pendaftaran</label>
                                <select name="jenis_pendaftaran" id="bulkJenisPendaftaran" class="form-select"
                                        onchange="toggleBulkKelas()" required>
                                    <option value="siswa_baru">Siswa Baru</option>
                                    <option value="pindahan">Pindahan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kelas Tujuan</label>
                                <select name="kelas_tujuan" id="bulkKelasTujuan" class="form-select" required>
                                    <option value="10">Kelas 10</option>
                                    <option value="11">Kelas 11</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" onclick="return prepareBulkKategori()">
                            <i class="bi bi-check-lg me-1"></i>Terapkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Daftar Peserta -->
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h5 class="mb-0">Daftar Peserta</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted" id="selectedCount" style="font-size:0.85rem">0 dipilih</span>
                <button type="button" id="bulkKategoriBtn" class="btn btn-sm btn-outline-primary"
                        data-bs-toggle="modal" data-bs-target="#bulkKategoriModal" disabled>
                    <i class="bi bi-tags me-1"></i>Kategori
                </button>
                <select id="bulkGrupSelect" class="form-select form-select-sm" style="width: auto; min-width: 140px;" disabled>
                    <option value="">-- Pilih Grup --</option>
                    @foreach($grup as $g)
                        <option value="{{ $g->id }}">{{ $g->nama }}</option>
                    @endforeach
                </select>
                <button type="button" id="bulkAssignBtn" class="btn btn-sm btn-success" disabled onclick="submitBulkAssign()">
                    <i class="bi bi-people-fill me-1"></i>Assign
                </button>
                <span class="vr d-none d-md-inline-block"></span>
                <select id="bulkTahapSelect" class="form-select form-select-sm" style="width: auto; min-width: 190px;" disabled onchange="toggleBulkLulusOption()">
                    <option value="">-- Pindah ke Tahap --</option>
                    @foreach($tahapLabels as $i => $label)
                        <option value="{{ $i }}">Tahap {{ $i }} - {{ $label }}</option>
                    @endforeach
                </select>
                <div class="form-check form-check-inline mb-0" id="bulkLulusWrapper" style="display:none;">
                    <input class="form-check-input" type="checkbox" value="1" id="bulkLuluskanFinal" onchange="toggleBulkLulusOption()" {{ empty($skGelombang) ? 'disabled' : '' }}>
                    <label class="form-check-label small" for="bulkLuluskanFinal">
                        Tandai LULUS{{ empty($skGelombang) ? ' (SK belum ada)' : '' }}
                    </label>
                </div>
                <select id="bulkSkGelombangSelect" class="form-select form-select-sm" style="display:none; width: auto; min-width: 190px;" {{ empty($skGelombang) ? 'disabled' : '' }}>
                    <option value="">-- Pilih SK --</option>
                    @forelse($skGelombang as $sk)
                        <option value="{{ $sk['id'] }}">{{ $sk['nama'] }}</option>
                    @empty
                        <option value="" disabled>SK belum tersedia</option>
                    @endforelse
                </select>
                <button type="button" id="bulkTahapBtn" class="btn btn-sm btn-primary" disabled onclick="submitBulkTahap()">
                    <i class="bi bi-signpost-split me-1"></i>Update Tahap
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="selectAll" onclick="toggleSelectAll()">
                            </th>
                            <th width="50">#</th>
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>No HP</th>
                            <th>Password</th>
                            <th>Kategori Pendaftaran</th>
                            <th>Grup</th>
                            <th width="80">Tahap</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peserta as $index => $p)
                            <tr class="{{ $p->trashed() ? 'table-secondary' : '' }}">
                                <td>
                                    @if(!$p->trashed())
                                    <input type="checkbox" class="form-check-input peserta-checkbox" 
                                           value="{{ $p->id }}" onclick="updateSelectedCount()">
                                    @endif
                                </td>
                                <td>{{ $peserta->firstItem() + $index }}</td>
                                <td>
                                    <code>{{ $p->nomor_pendaftaran }}</code>
                                    @if($p->trashed())
                                        <span class="badge bg-danger">Dihapus</span>
                                    @endif
                                </td>
                                <td>{{ $p->nama }}</td>
                                <td>{{ $p->telepon ?? '-' }}</td>
                                <td>
                                    @if($p->password_temp)
                                        <code class="user-select-all">{{ $p->password_temp }}</code>
                                    @else
                                        <span class="text-muted small">(sudah diubah)</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge bg-dark">{{ $p->tahunAjaran?->nama ?? 'Belum ada tahun' }}</span>
                                        <span class="badge bg-secondary">{{ $p->gelombangPendaftaran?->nama ?? 'Belum ada gelombang' }}</span>
                                        <span class="badge bg-{{ $p->status_kuota_badge }}">{{ $p->status_kuota_label }}</span>
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ $p->jenis_pendaftaran_label }}
                                        @if($p->kelas_tujuan)
                                            · Kelas {{ $p->kelas_tujuan }}
                                        @endif
                                        @if($p->kelas_penempatan)
                                            &middot; {{ $p->kelas_penempatan }}
                                        @endif
                                    </small>
                                </td>
                                <td>
                                    @forelse($p->grup as $g)
                                        <span class="badge bg-secondary">{{ $g->nama }}</span>
                                    @empty
                                        <span class="text-muted">-</span>
                                    @endforelse
                                </td>
                                <td>
                                    <span class="badge bg-primary">Tahap {{ $p->tahap_saat_ini }}</span>
                                </td>
                                <td>
                                    @if($p->trashed())
                                        <form action="{{ route('admin.peserta.restore', $p->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Pulihkan
                                            </button>
                                        </form>
                                    @else
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="{{ route('admin.peserta.show', $p) }}" class="btn btn-sm btn-info text-white">
                                                <i class="bi bi-eye me-1"></i>Detail
                                            </a>
                                            <a href="{{ route('admin.peserta.edit', $p) }}" class="btn btn-sm btn-warning">
                                                <i class="bi bi-pencil me-1"></i>Edit
                                            </a>
                                            <form action="{{ route('admin.peserta.destroy', $p) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Yakin ingin menghapus peserta ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash me-1"></i>Hapus
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Belum ada peserta. <a href="{{ route('admin.peserta.create') }}">Tambah peserta pertama</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $peserta->withQueryString()->links() }}
        </div>
    </div>
    
    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-3"><i class="bi bi-eye text-info"></i> Lihat Detail</span>
                <span class="ms-3"><i class="bi bi-pencil text-warning"></i> Edit</span>
                <span class="ms-3"><i class="bi bi-trash text-danger"></i> Hapus</span>
                <span class="ms-3"><i class="bi bi-arrow-counterclockwise text-success"></i> Pulihkan</span>
            </small>
        </div>
    </div>
</div>

@push('scripts')
<script>
function selectedPesertaCheckboxes() {
    return document.querySelectorAll('.peserta-checkbox:checked');
}

function appendSelectedPesertaIds(containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    selectedPesertaCheckboxes().forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'peserta_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });
}

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.peserta-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = selectedPesertaCheckboxes();
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count + ' dipilih';
    
    const grupSelect = document.getElementById('bulkGrupSelect');
    const assignBtn = document.getElementById('bulkAssignBtn');
    const tahapSelect = document.getElementById('bulkTahapSelect');
    const tahapBtn = document.getElementById('bulkTahapBtn');
    const kategoriBtn = document.getElementById('bulkKategoriBtn');
    
    if (count > 0) {
        grupSelect.disabled = false;
        assignBtn.disabled = false;
        tahapSelect.disabled = false;
        tahapBtn.disabled = false;
        kategoriBtn.disabled = false;
    } else {
        grupSelect.disabled = true;
        assignBtn.disabled = true;
        tahapSelect.disabled = true;
        tahapBtn.disabled = true;
        kategoriBtn.disabled = true;
        tahapSelect.value = '';
        document.getElementById('bulkLuluskanFinal').checked = false;
    }

    toggleBulkLulusOption();
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.peserta-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (allCheckboxes.length > 0) {
        selectAll.checked = count === allCheckboxes.length;
        selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}

function filterGelombangByTahun(tahunId, targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;

    Array.from(target.options).forEach(option => {
        if (!option.value) return;
        const visible = !tahunId || option.dataset.tahun === tahunId;
        option.hidden = !visible;
        option.disabled = !visible;
    });

    if (target.selectedOptions[0]?.disabled) {
        target.value = '';
    }
}

function toggleBulkKelas() {
    const jenis = document.getElementById('bulkJenisPendaftaran').value;
    const kelas = document.getElementById('bulkKelasTujuan');
    if (jenis === 'siswa_baru') {
        kelas.value = '10';
        kelas.disabled = true;
    } else {
        kelas.disabled = false;
    }
}

function prepareBulkKategori() {
    const checkboxes = selectedPesertaCheckboxes();
    if (checkboxes.length === 0) {
        alert('Pilih minimal satu peserta.');
        return false;
    }

    const tahun = document.getElementById('bulkTahunAjaran');
    const gelombang = document.getElementById('bulkGelombang');
    if (!tahun.value || !gelombang.value) {
        alert('Pilih tahun ajaran dan gelombang.');
        return false;
    }

    const kelas = document.getElementById('bulkKelasTujuan');
    kelas.disabled = false;
    appendSelectedPesertaIds('selectedPesertaIdsKategori');

    return confirm('Terapkan kategori baru ke ' + checkboxes.length + ' peserta?');
}

document.addEventListener('DOMContentLoaded', () => {
    filterGelombangByTahun(document.getElementById('filterTahunAjaran')?.value ?? '', 'filterGelombang');
    filterGelombangByTahun('', 'bulkGelombang');
    toggleBulkKelas();
});

function toggleBulkLulusOption() {
    const tahapSelect = document.getElementById('bulkTahapSelect');
    const wrapper = document.getElementById('bulkLulusWrapper');
    const checkbox = document.getElementById('bulkLuluskanFinal');
    const skSelect = document.getElementById('bulkSkGelombangSelect');

    if (tahapSelect.value === '7' && !tahapSelect.disabled) {
        wrapper.style.display = 'inline-flex';
        skSelect.style.display = checkbox.checked ? 'inline-block' : 'none';
        return;
    }

    wrapper.style.display = 'none';
    checkbox.checked = false;
    skSelect.style.display = 'none';
    skSelect.value = '';
}

function submitBulkAssign() {
    const grupId = document.getElementById('bulkGrupSelect').value;
    if (!grupId) {
        alert('Pilih grup terlebih dahulu!');
        return;
    }
    
    const checkboxes = selectedPesertaCheckboxes();
    if (checkboxes.length === 0) {
        alert('Pilih minimal satu peserta!');
        return;
    }
    
    if (!confirm('Yakin ingin menambahkan ' + checkboxes.length + ' peserta ke grup yang dipilih?')) {
        return;
    }
    
    // Set grup_id
    document.getElementById('bulkGrupId').value = grupId;
    
    appendSelectedPesertaIds('selectedPesertaIds');
    
    // Submit form
    document.getElementById('bulkAssignForm').submit();
}

function submitBulkTahap() {
    const tahapSelect = document.getElementById('bulkTahapSelect');
    const tahapBaru = tahapSelect.value;
    if (!tahapBaru) {
        alert('Pilih tahap tujuan terlebih dahulu!');
        return;
    }

    const checkboxes = selectedPesertaCheckboxes();
    if (checkboxes.length === 0) {
        alert('Pilih minimal satu peserta!');
        return;
    }

    const selectedText = tahapSelect.options[tahapSelect.selectedIndex].text;
    const luluskanFinal = tahapBaru === '7' && document.getElementById('bulkLuluskanFinal').checked;
    const skSelect = document.getElementById('bulkSkGelombangSelect');
    if (luluskanFinal && !skSelect.value) {
        alert('Pilih SK gelombang terlebih dahulu untuk menandai peserta lulus final.');
        return;
    }

    const pesanTambahan = luluskanFinal
        ? '\n\nPeserta juga akan ditandai LULUS final.'
        : '';

    if (!confirm('Yakin ingin memindahkan ' + checkboxes.length + ' peserta ke ' + selectedText + '?' + pesanTambahan)) {
        return;
    }

    document.getElementById('bulkTahapBaru').value = tahapBaru;
    document.getElementById('bulkLuluskanFinalValue').value = luluskanFinal ? '1' : '0';
    document.getElementById('bulkSkGelombangValue').value = luluskanFinal ? skSelect.value : '';
    appendSelectedPesertaIds('selectedPesertaIdsTahap');
    document.getElementById('bulkTahapForm').submit();
}
</script>
@endpush
@endsection
