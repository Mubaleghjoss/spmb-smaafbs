@extends('layouts.admin')

@section('title', 'Manajemen Peserta')

@section('content')
<div class="container-fluid">
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
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ $filter['tahap'] == $i ? 'selected' : '' }}>Tahap {{ $i }}</option>
                        @endfor
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

    <!-- Daftar Peserta -->
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h5 class="mb-0">Daftar Peserta</h5>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <span class="text-muted" id="selectedCount" style="font-size:0.85rem">0 dipilih</span>
                <select id="bulkGrupSelect" class="form-select form-select-sm" style="width: auto; min-width: 140px;" disabled>
                    <option value="">-- Pilih Grup --</option>
                    @foreach($grup as $g)
                        <option value="{{ $g->id }}">{{ $g->nama }}</option>
                    @endforeach
                </select>
                <button type="button" id="bulkAssignBtn" class="btn btn-sm btn-success" disabled onclick="submitBulkAssign()">
                    <i class="bi bi-people-fill me-1"></i>Assign
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
                                <td colspan="9" class="text-center py-4 text-muted">
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
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.peserta-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count + ' dipilih';
    
    const grupSelect = document.getElementById('bulkGrupSelect');
    const assignBtn = document.getElementById('bulkAssignBtn');
    
    if (count > 0) {
        grupSelect.disabled = false;
        assignBtn.disabled = false;
    } else {
        grupSelect.disabled = true;
        assignBtn.disabled = true;
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.peserta-checkbox');
    const selectAll = document.getElementById('selectAll');
    if (allCheckboxes.length > 0) {
        selectAll.checked = count === allCheckboxes.length;
        selectAll.indeterminate = count > 0 && count < allCheckboxes.length;
    }
}

function submitBulkAssign() {
    const grupId = document.getElementById('bulkGrupSelect').value;
    if (!grupId) {
        alert('Pilih grup terlebih dahulu!');
        return;
    }
    
    const checkboxes = document.querySelectorAll('.peserta-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Pilih minimal satu peserta!');
        return;
    }
    
    if (!confirm('Yakin ingin menambahkan ' + checkboxes.length + ' peserta ke grup yang dipilih?')) {
        return;
    }
    
    // Set grup_id
    document.getElementById('bulkGrupId').value = grupId;
    
    // Clear and add selected peserta ids
    const container = document.getElementById('selectedPesertaIds');
    container.innerHTML = '';
    checkboxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'peserta_ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });
    
    // Submit form
    document.getElementById('bulkAssignForm').submit();
}
</script>
@endpush
@endsection
