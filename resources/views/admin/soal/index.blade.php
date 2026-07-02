@extends('layouts.admin')

@section('title', 'Bank Soal')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <h1 class="h3 mb-0">Bank Soal</h1>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.soal.topik.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-folder"></i> Topik
            </a>
            <a href="{{ route('admin.soal.preview', request()->query()) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-eye"></i> Preview
            </a>
            <a href="{{ route('admin.soal.impor') }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-upload"></i> Impor
            </a>
            <a href="{{ route('admin.soal.ekspor', request()->query()) }}" class="btn btn-sm btn-outline-info">
                <i class="bi bi-download"></i> Ekspor
            </a>
            <a href="{{ route('admin.soal.create') }}" class="btn btn-sm btn-primary">
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
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Total Soal</h6>
                    <h2 class="mb-0">{{ $statistik['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Soal Aktif</h6>
                    <h2 class="mb-0">{{ $statistik['aktif'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Nonaktif</h6>
                    <h2 class="mb-0">{{ $statistik['nonaktif'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body py-3">
                    <h6 class="card-title mb-1" style="font-size:0.8rem">Pilihan Ganda</h6>
                    <h2 class="mb-0">{{ $statistik['per_tipe']['pilihan_ganda'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Topik</label>
                    <select name="topik_id" class="form-select">
                        <option value="">Semua Topik</option>
                        @foreach($topik as $t)
                            <option value="{{ $t->id }}" {{ $filter['topik_id'] == $t->id ? 'selected' : '' }}>
                                {{ $t->nama }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipe</label>
                    <select name="tipe" class="form-select">
                        <option value="">Semua Tipe</option>
                        <option value="pilihan_ganda" {{ $filter['tipe'] == 'pilihan_ganda' ? 'selected' : '' }}>Pilihan Ganda</option>
                        <option value="jawaban_ganda" {{ $filter['tipe'] == 'jawaban_ganda' ? 'selected' : '' }}>Jawaban Ganda</option>
                        <option value="esai" {{ $filter['tipe'] == 'esai' ? 'selected' : '' }}>Esai</option>
                        <option value="benar_salah" {{ $filter['tipe'] == 'benar_salah' ? 'selected' : '' }}>Benar/Salah</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="aktif" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="1" {{ $filter['aktif'] === true ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ $filter['aktif'] === false ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Cari</label>
                    <input type="text" name="cari" class="form-control" placeholder="Cari pertanyaan..." value="{{ $filter['cari'] }}">
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
            
            {{-- Tombol Lihat Semua --}}
            @if(request()->hasAny(['topik_id', 'tipe', 'aktif', 'cari']))
            <div class="mt-3 pt-3 border-top">
                <a href="{{ route('admin.soal.lihat-semua', request()->query()) }}" class="btn btn-outline-success">
                    <i class="bi bi-list-ul me-1"></i>Lihat Semua Soal ({{ $soal->total() }} soal)
                </a>
                <small class="text-muted ms-2">Tampilkan semua soal hasil filter dalam satu halaman</small>
            </div>
            @endif
        </div>
    </div>

    <!-- Daftar Soal -->
    <div class="card">
        <div class="card-body">
            {{-- Toolbar: Per-page selector + info --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <small class="text-muted">
                    @if($soal->total() > 0)
                        Menampilkan {{ $soal->firstItem() }} - {{ $soal->lastItem() }} dari {{ $soal->total() }} soal
                    @else
                        Tidak ada soal ditemukan
                    @endif
                </small>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 text-muted small">Tampilkan:</label>
                    <select class="form-select form-select-sm" style="width: auto;" onchange="updatePerPage(this.value)">
                        @foreach([15, 25, 50, 100] as $pp)
                            <option value="{{ $pp }}" {{ request('per_page', 15) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Pertanyaan</th>
                            <th width="150">Topik</th>
                            <th width="120">Tipe</th>
                            <th width="80">Bobot</th>
                            <th width="80">Status</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($soal as $index => $s)
                            <tr>
                                <td>{{ $soal->firstItem() + $index }}</td>
                                <td>
                                    <div class="text-truncate" style="max-width: 400px;">
                                        {!! strip_tags($s->pertanyaan) !!}
                                    </div>
                                    @if($s->memiliki_media)
                                        <span class="badge bg-info"><i class="bi bi-image"></i> Media</span>
                                    @endif
                                </td>
                                <td>{{ $s->topik?->nama ?? '-' }}</td>
                                <td>
                                    @switch($s->tipe)
                                        @case('pilihan_ganda')
                                            <span class="badge bg-primary">Pilihan Ganda</span>
                                            @break
                                        @case('jawaban_ganda')
                                            <span class="badge bg-info">Jawaban Ganda</span>
                                            @break
                                        @case('esai')
                                            <span class="badge bg-warning">Esai</span>
                                            @break
                                        @case('benar_salah')
                                            <span class="badge bg-secondary">Benar/Salah</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>{{ $s->bobot }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input toggle-aktif" type="checkbox" 
                                               data-id="{{ $s->id }}" {{ $s->aktif ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.soal.show', $s) }}" class="btn btn-info text-white" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.soal.edit', $s) }}" class="btn btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('admin.soal.duplikat', $s) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Duplikat">
                                                <i class="bi bi-copy"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.soal.destroy', $s) }}" method="POST" class="d-inline" 
                                              onsubmit="return confirm('Yakin ingin menghapus soal ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Belum ada soal. <a href="{{ route('admin.soal.create') }}">Tambah soal pertama</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($soal->hasPages())
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 mt-3 pt-3 border-top">
                <small class="text-muted">
                    {{ $soal->firstItem() }}–{{ $soal->lastItem() }} dari {{ $soal->total() }} soal
                </small>
                <div class="d-flex gap-2">
                    @if($soal->previousPageUrl())
                        <a href="{{ $soal->withQueryString()->previousPageUrl() }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-chevron-left"></i> Sebelumnya
                        </a>
                    @endif
                    @if($soal->nextPageUrl())
                        <a href="{{ $soal->withQueryString()->nextPageUrl() }}" class="btn btn-sm btn-outline-secondary">
                            Selanjutnya <i class="bi bi-chevron-right"></i>
                        </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
// Per-page selector — update URL with per_page param
function updatePerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page'); // Reset to page 1
    window.location.href = url.toString();
}

document.querySelectorAll('.toggle-aktif').forEach(toggle => {
    toggle.addEventListener('change', function() {
        fetch(`/admin/soal/${this.dataset.id}/toggle-aktif`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.sukses) {
                // Optional: show toast notification
            }
        });
    });
});
</script>
@endpush
@endsection
