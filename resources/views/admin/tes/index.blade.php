@extends('layouts.admin')

@section('title', 'Manajemen Tes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manajemen Tes</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.token-global.index') }}" class="btn btn-success">
                <i class="bi bi-key me-1"></i> Token Global
            </a>
            <a href="{{ route('admin.tes.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Tambah Tes
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="cari" class="form-control" 
                           value="{{ $filter['cari'] ?? '' }}" placeholder="Nama tes...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua</option>
                        <option value="draft" {{ ($filter['status'] ?? '') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="aktif" {{ ($filter['status'] ?? '') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="selesai" {{ ($filter['status'] ?? '') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" class="form-control" 
                           value="{{ $filter['tanggal_mulai'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" class="form-control" 
                           value="{{ $filter['tanggal_selesai'] ?? '' }}">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    <a href="{{ route('admin.tes.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-success btn-sm" id="btnBulkAktifkan" disabled>
                    <i class="bi bi-play-fill me-1"></i> Aktifkan Terpilih
                </button>
                <button type="button" class="btn btn-warning btn-sm" id="btnBulkStop" disabled>
                    <i class="bi bi-stop-fill me-1"></i> Stop Terpilih
                </button>
                <button type="button" class="btn btn-info btn-sm" id="btnBulkDurasi" disabled data-bs-toggle="modal" data-bs-target="#modalDurasiJadwal">
                    <i class="bi bi-clock me-1"></i> Atur Durasi & Jadwal
                </button>
            </div>
            <small class="text-muted"><span id="selectedCount">0</span> tes dipilih</small>
        </div>
        <div class="card-body p-0">
            <form id="formBulkAction" method="POST">
                @csrf
                <input type="hidden" name="action" id="bulkAction">
                <input type="hidden" name="tes_ids" id="tesIds">
            </form>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <input type="checkbox" class="form-check-input" id="checkAll">
                            </th>
                            <th>Nama Tes</th>
                            <th class="text-center">Soal</th>
                            <th class="text-center">Grup</th>
                            <th class="text-center">Durasi</th>
                            <th>Jadwal</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Peserta</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarTes as $tes)
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input tes-checkbox" 
                                           value="{{ $tes->id }}" data-status="{{ $tes->status }}">
                                </td>
                                <td>
                                    <a href="{{ route('admin.tes.show', $tes) }}" class="text-decoration-none fw-medium">
                                        {{ $tes->nama }}
                                    </a>
                                    @if($tes->keterangan)
                                        <br><small class="text-muted">{{ Str::limit($tes->keterangan, 50) }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $tes->soal_count }}</td>
                                <td class="text-center">
                                    @if($tes->grup_count > 0)
                                        <span class="badge bg-info">{{ $tes->grup_count }}</span>
                                    @else
                                        <span class="text-muted" title="Semua peserta">-</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $tes->durasi_menit }}m</td>
                                <td>
                                    @if($tes->mulai)
                                        <small>
                                            {{ $tes->mulai->format('d/m/Y H:i') }}
                                            @if($tes->selesai)
                                                <br>s/d {{ $tes->selesai->format('d/m/Y H:i') }}
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($tes->status === 'draft')
                                        <span class="badge bg-secondary">Draft</span>
                                    @elseif($tes->status === 'aktif')
                                        @if($tes->sedangBerlangsung())
                                            <span class="badge bg-success">Berlangsung</span>
                                        @else
                                            <span class="badge bg-primary">Aktif</span>
                                        @endif
                                    @else
                                        <span class="badge bg-dark">Selesai</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $tes->sesi_tes_count }}</td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <a href="{{ route('admin.tes.show', $tes) }}" 
                                           class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                        <a href="{{ route('admin.tes.edit', $tes) }}" 
                                           class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#modalHapus{{ $tes->id }}">
                                            <i class="bi bi-trash me-1"></i>Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Hapus -->
                            <div class="modal fade" id="modalHapus{{ $tes->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Konfirmasi Hapus</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            Apakah Anda yakin ingin menghapus tes <strong>{{ $tes->nama }}</strong>?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <form action="{{ route('admin.tes.destroy', $tes) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Hapus</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    Belum ada tes. <a href="{{ route('admin.tes.create') }}">Tambah tes baru</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($daftarTes->hasPages())
            <div class="card-footer">
                {{ $daftarTes->withQueryString()->links() }}
            </div>
        @endif
    </div>
    
    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-3"><i class="bi bi-eye text-info"></i> Lihat Detail</span>
                <span class="ms-3"><i class="bi bi-pencil text-warning"></i> Edit</span>
                <span class="ms-3"><i class="bi bi-trash text-danger"></i> Hapus</span>
            </small>
        </div>
    </div>
</div>

{{-- Modal Atur Durasi & Jadwal --}}
<div class="modal fade" id="modalDurasiJadwal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bi bi-clock me-2"></i>Atur Durasi & Jadwal Tes</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDurasiJadwal" action="{{ route('admin.tes.bulk-durasi-jadwal') }}" method="POST">
                @csrf
                <input type="hidden" name="tes_ids" id="durasiTesIds">
                <div class="modal-body">
                    <p class="text-muted mb-3">Atur durasi dan jadwal untuk <strong><span id="durasiSelectedCount">0</span> tes</strong> yang dipilih:</p>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ubahDurasi" name="ubah_durasi" value="1" checked>
                            <label class="form-check-label fw-bold" for="ubahDurasi">Ubah Durasi</label>
                        </div>
                        <div class="input-group mt-2" id="inputDurasi">
                            <input type="number" name="durasi_menit" class="form-control" 
                                   min="1" max="300" value="60">
                            <span class="input-group-text">menit</span>
                        </div>
                        <small class="text-muted">Durasi dalam menit (1-300)</small>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ubahJadwal" name="ubah_jadwal" value="1">
                            <label class="form-check-label fw-bold" for="ubahJadwal">Ubah Jadwal</label>
                        </div>
                        <div id="inputJadwal" style="display: none;">
                            <div class="row mt-2">
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Mulai</label>
                                    <input type="datetime-local" name="mulai" class="form-control">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label">Selesai</label>
                                    <input type="datetime-local" name="selesai" class="form-control">
                                </div>
                            </div>
                            <small class="text-muted">Kosongkan jika tidak ingin membatasi jadwal</small>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning py-2 mb-0">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <small>Perubahan akan berlaku untuk semua tes yang dipilih.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const checkboxes = document.querySelectorAll('.tes-checkbox');
    const btnAktifkan = document.getElementById('btnBulkAktifkan');
    const btnStop = document.getElementById('btnBulkStop');
    const btnDurasi = document.getElementById('btnBulkDurasi');
    const selectedCount = document.getElementById('selectedCount');
    const formBulkAction = document.getElementById('formBulkAction');
    const bulkAction = document.getElementById('bulkAction');
    const tesIds = document.getElementById('tesIds');
    const durasiTesIds = document.getElementById('durasiTesIds');
    const durasiSelectedCount = document.getElementById('durasiSelectedCount');

    // Toggle input durasi
    const ubahDurasi = document.getElementById('ubahDurasi');
    const inputDurasi = document.getElementById('inputDurasi');
    ubahDurasi.addEventListener('change', function() {
        inputDurasi.style.display = this.checked ? 'flex' : 'none';
    });

    // Toggle input jadwal
    const ubahJadwal = document.getElementById('ubahJadwal');
    const inputJadwal = document.getElementById('inputJadwal');
    ubahJadwal.addEventListener('change', function() {
        inputJadwal.style.display = this.checked ? 'block' : 'none';
    });

    function updateButtons() {
        const checked = document.querySelectorAll('.tes-checkbox:checked');
        const count = checked.length;
        selectedCount.textContent = count;
        durasiSelectedCount.textContent = count;
        
        // Enable/disable buttons based on selection
        btnAktifkan.disabled = count === 0;
        btnStop.disabled = count === 0;
        btnDurasi.disabled = count === 0;
        
        // Update hidden input for durasi form
        const ids = Array.from(checked).map(cb => cb.value);
        durasiTesIds.value = JSON.stringify(ids);
    }

    checkAll.addEventListener('change', function() {
        checkboxes.forEach(cb => cb.checked = this.checked);
        updateButtons();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateButtons);
    });

    btnAktifkan.addEventListener('click', function() {
        if (confirm('Aktifkan semua tes yang dipilih?')) {
            const ids = Array.from(document.querySelectorAll('.tes-checkbox:checked')).map(cb => cb.value);
            bulkAction.value = 'aktifkan';
            tesIds.value = JSON.stringify(ids);
            formBulkAction.action = '{{ route("admin.tes.bulk-status") }}';
            formBulkAction.submit();
        }
    });

    btnStop.addEventListener('click', function() {
        if (confirm('Stop/akhiri semua tes yang dipilih?')) {
            const ids = Array.from(document.querySelectorAll('.tes-checkbox:checked')).map(cb => cb.value);
            bulkAction.value = 'stop';
            tesIds.value = JSON.stringify(ids);
            formBulkAction.action = '{{ route("admin.tes.bulk-status") }}';
            formBulkAction.submit();
        }
    });
});
</script>
@endpush
@endsection
