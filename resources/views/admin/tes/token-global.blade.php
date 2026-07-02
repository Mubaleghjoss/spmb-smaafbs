@extends('layouts.admin')

@section('title', 'Token Global')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Token Global</h1>
            <p class="text-muted mb-0">Token yang bisa dipakai untuk semua tes oleh banyak peserta</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.tes.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Tes
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-lg me-1"></i> Buat Token Baru
            </button>
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

    {{-- Statistik --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Total Token</h6>
                            <h2 class="mb-0">{{ $statistik['total'] }}</h2>
                        </div>
                        <i class="bi bi-key fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Token Aktif</h6>
                            <h2 class="mb-0">{{ $statistik['aktif'] }}</h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Tidak Aktif</h6>
                            <h2 class="mb-0">{{ $statistik['tidak_aktif'] }}</h2>
                        </div>
                        <i class="bi bi-x-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title mb-0">Total Penggunaan</h6>
                            <h2 class="mb-0">{{ $statistik['total_penggunaan'] }}</h2>
                        </div>
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Cari</label>
                    <input type="text" name="cari" class="form-control" 
                           value="{{ $filter['cari'] ?? '' }}" placeholder="Kode atau nama token...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="aktif" class="form-select">
                        <option value="">Semua</option>
                        <option value="1" {{ ($filter['aktif'] ?? '') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ ($filter['aktif'] ?? '') === '0' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    <a href="{{ route('admin.token-global.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Daftar Token --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-key me-2"></i>Daftar Token Global</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Token</th>
                            <th>Nama/Label</th>
                            <th>Tes yang Diizinkan</th>
                            <th>Jadwal</th>
                            <th class="text-center">Penggunaan</th>
                            <th class="text-center">Status</th>
                            <th width="180">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarToken as $token)
                            <tr>
                                <td>
                                    <code class="fs-5 user-select-all" style="letter-spacing: 2px;">{{ $token->kode }}</code>
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-1" 
                                            onclick="copyToClipboard('{{ $token->kode }}')" title="Salin">
                                        <i class="bi bi-clipboard"></i>
                                    </button>
                                </td>
                                <td>
                                    {{ $token->nama ?? '-' }}
                                    @if($token->keterangan)
                                        <br><small class="text-muted">{{ Str::limit($token->keterangan, 50) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @if($token->tes->isEmpty())
                                        <span class="badge bg-success">Semua Tes Aktif</span>
                                    @else
                                        @foreach($token->tes->take(3) as $tes)
                                            <span class="badge bg-secondary">{{ $tes->nama }}</span>
                                        @endforeach
                                        @if($token->tes->count() > 3)
                                            <span class="badge bg-light text-dark">+{{ $token->tes->count() - 3 }} lainnya</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($token->mulai || $token->selesai)
                                        <small>
                                            @if($token->mulai)
                                                {{ $token->mulai->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                            <br>s/d 
                                            @if($token->selesai)
                                                {{ $token->selesai->format('d/m/Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-muted">Tanpa batas waktu</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">{{ $token->jumlah_penggunaan }}x</span>
                                </td>
                                <td class="text-center">
                                    @if(!$token->aktif)
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    @elseif($token->sudah_kedaluwarsa)
                                        <span class="badge bg-danger">Kedaluwarsa</span>
                                    @elseif($token->belum_mulai)
                                        <span class="badge bg-warning">Belum Mulai</span>
                                    @else
                                        <span class="badge bg-success">Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-info text-white" 
                                                data-bs-toggle="modal" data-bs-target="#modalEdit{{ $token->id }}"
                                                title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.token-global.toggle', $token) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $token->aktif ? 'btn-warning' : 'btn-success' }}"
                                                    title="{{ $token->aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="bi {{ $token->aktif ? 'bi-pause' : 'bi-play' }}"></i>
                                            </button>
                                        </form>
                                        <a href="{{ route('admin.token-global.logs', $token) }}" 
                                           class="btn btn-sm btn-outline-secondary" title="Lihat Log">
                                            <i class="bi bi-list-ul"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                data-bs-toggle="modal" data-bs-target="#modalHapus{{ $token->id }}"
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Modal Edit --}}
                            <div class="modal fade" id="modalEdit{{ $token->id }}" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form action="{{ route('admin.token-global.update', $token) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header bg-info text-white">
                                                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Token: {{ $token->kode }}</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Nama/Label</label>
                                                        <input type="text" name="nama" class="form-control" 
                                                               value="{{ $token->nama }}" placeholder="Contoh: Token Gelombang 1">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Status</label>
                                                        <select name="aktif" class="form-select">
                                                            <option value="1" {{ $token->aktif ? 'selected' : '' }}>Aktif</option>
                                                            <option value="0" {{ !$token->aktif ? 'selected' : '' }}>Nonaktif</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Keterangan</label>
                                                    <textarea name="keterangan" class="form-control" rows="2">{{ $token->keterangan }}</textarea>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Waktu Mulai</label>
                                                        <input type="datetime-local" name="mulai" class="form-control"
                                                               value="{{ $token->mulai?->format('Y-m-d\TH:i') }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Waktu Selesai</label>
                                                        <input type="datetime-local" name="selesai" class="form-control"
                                                               value="{{ $token->selesai?->format('Y-m-d\TH:i') }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Tes yang Diizinkan</label>
                                                    <select name="tes_ids[]" class="form-select" multiple size="5">
                                                        @foreach($daftarTes as $tes)
                                                            <option value="{{ $tes->id }}" 
                                                                {{ $token->tes->contains($tes->id) ? 'selected' : '' }}>
                                                                {{ $tes->nama }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="text-muted">Kosongkan untuk mengizinkan semua tes aktif. Tahan Ctrl untuk memilih lebih dari satu.</small>
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

                            {{-- Modal Hapus --}}
                            <div class="modal fade" id="modalHapus{{ $token->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Hapus Token</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Apakah Anda yakin ingin menghapus token <strong>{{ $token->kode }}</strong>?</p>
                                            @if($token->jumlah_penggunaan > 0)
                                                <div class="alert alert-warning py-2">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    Token ini sudah digunakan {{ $token->jumlah_penggunaan }}x. Log penggunaan juga akan dihapus.
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                            <form action="{{ route('admin.token-global.destroy', $token) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="bi bi-trash me-1"></i>Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-key fs-1 d-block mb-2"></i>
                                    Belum ada token global. 
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#modalTambah">Buat token baru</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Info Penggunaan --}}
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Cara Penggunaan Token Global</h6>
        </div>
        <div class="card-body">
            <ol class="mb-0">
                <li>Buat token global baru dengan klik tombol "Buat Token Baru"</li>
                <li>Bagikan kode token kepada peserta</li>
                <li>Peserta login di <a href="{{ route('login.token') }}" target="_blank">{{ route('login.token') }}</a></li>
                <li>Peserta memasukkan <strong>Nomor Pendaftaran</strong> dan <strong>Token</strong></li>
                <li>Peserta akan langsung diarahkan ke daftar tes yang tersedia</li>
            </ol>
        </div>
    </div>
</div>

{{-- Modal Tambah Token --}}
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('admin.token-global.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Buat Token Global Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Token akan di-generate otomatis. Satu token bisa dipakai oleh banyak peserta.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nama/Label Token</label>
                            <input type="text" name="nama" class="form-control" 
                                   placeholder="Contoh: Token Gelombang 1, Token Ujian Harian, dll">
                            <small class="text-muted">Opsional, untuk memudahkan identifikasi</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2" 
                                  placeholder="Keterangan tambahan (opsional)"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Waktu Mulai Berlaku</label>
                            <input type="datetime-local" name="mulai" class="form-control">
                            <small class="text-muted">Kosongkan jika langsung aktif</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Waktu Berakhir</label>
                            <input type="datetime-local" name="selesai" class="form-control">
                            <small class="text-muted">Kosongkan jika tanpa batas waktu</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="untukSemuaTes" name="untuk_semua_tes" value="1" checked>
                            <label class="form-check-label fw-bold" for="untukSemuaTes">
                                Untuk Semua Tes Aktif
                            </label>
                        </div>
                        <div id="pilihTesContainer" style="display: none;">
                            <label class="form-label">Pilih Tes yang Diizinkan</label>
                            <select name="tes_ids[]" class="form-select" multiple size="5">
                                @foreach($daftarTes as $tes)
                                    <option value="{{ $tes->id }}">{{ $tes->nama }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Tahan Ctrl untuk memilih lebih dari satu tes</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-key me-1"></i>Buat Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Token berhasil disalin: ' + text);
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const untukSemuaTes = document.getElementById('untukSemuaTes');
    const pilihTesContainer = document.getElementById('pilihTesContainer');
    
    untukSemuaTes.addEventListener('change', function() {
        pilihTesContainer.style.display = this.checked ? 'none' : 'block';
    });
});
</script>
@endpush
@endsection
