@extends('layouts.admin')

@section('title', 'Manajemen Token')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Manajemen Token</h1>
            <p class="text-muted mb-0">{{ $tes->nama }}</p>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.tes.token.create', $tes) }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Generate Token
            </a>
            <a href="{{ route('admin.tes.token.ekspor', $tes) }}" class="btn btn-outline-success">
                <i class="bi bi-download me-1"></i> Ekspor
            </a>
            <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
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

    <!-- Statistik -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <div class="h3 mb-0">{{ $statistik['total'] }}</div>
                    <small>Total Token</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <div class="h3 mb-0">{{ $statistik['tersedia'] }}</div>
                    <small>Tersedia</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <div class="h3 mb-0">{{ $statistik['terpakai'] }}</div>
                    <small>Terpakai</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <div class="h3 mb-0">{{ $statistik['kedaluwarsa'] }}</div>
                    <small>Kedaluwarsa</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Cari Kode</label>
                    <input type="text" name="cari" class="form-control" 
                           value="{{ $filter['cari'] ?? '' }}" placeholder="Kode token...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="terpakai" class="form-select">
                        <option value="">Semua</option>
                        <option value="0" {{ isset($filter['terpakai']) && $filter['terpakai'] === false ? 'selected' : '' }}>Belum Terpakai</option>
                        <option value="1" {{ isset($filter['terpakai']) && $filter['terpakai'] === true ? 'selected' : '' }}>Sudah Terpakai</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Kedaluwarsa</label>
                    <select name="kedaluwarsa" class="form-select">
                        <option value="">Semua</option>
                        <option value="valid" {{ ($filter['kedaluwarsa'] ?? '') === 'valid' ? 'selected' : '' }}>Masih Valid</option>
                        <option value="expired" {{ ($filter['kedaluwarsa'] ?? '') === 'expired' ? 'selected' : '' }}>Sudah Expired</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                    <a href="{{ route('admin.tes.token.index', $tes) }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Daftar Token -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Daftar Token</h5>
            @if($statistik['belum_terpakai'] > 0)
                <form action="{{ route('admin.tes.token.hapus-semua', $tes) }}" method="POST" 
                      onsubmit="return confirm('Hapus semua token yang belum terpakai?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Hapus Semua Belum Terpakai
                    </button>
                </form>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Kode Token</th>
                            <th>Kedaluwarsa</th>
                            <th class="text-center">Status</th>
                            <th>Dipakai Oleh</th>
                            <th>Dipakai Pada</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($daftarToken as $token)
                            <tr>
                                <td>
                                    <code class="fs-6">{{ $token->kode }}</code>
                                </td>
                                <td>
                                    @if($token->kedaluwarsa)
                                        {{ $token->kedaluwarsa->format('d/m/Y H:i') }}
                                        @if($token->sudah_kedaluwarsa)
                                            <br><small class="text-danger">Expired</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($token->terpakai)
                                        <span class="badge bg-secondary">Terpakai</span>
                                    @elseif($token->sudah_kedaluwarsa)
                                        <span class="badge bg-warning text-dark">Expired</span>
                                    @else
                                        <span class="badge bg-success">Tersedia</span>
                                    @endif
                                </td>
                                <td>{{ $token->peserta?->nama ?? '-' }}</td>
                                <td>{{ $token->dipakai_pada?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($token->terpakai)
                                            <form action="{{ route('admin.tes.token.reset', [$tes, $token]) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" 
                                                        title="Reset Token"
                                                        onclick="return confirm('Reset token ini?')">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.tes.token.destroy', [$tes, $token]) }}" 
                                                  method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" 
                                                        title="Hapus"
                                                        onclick="return confirm('Hapus token ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada token. <a href="{{ route('admin.tes.token.create', $tes) }}">Generate token baru</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($daftarToken->hasPages())
            <div class="card-footer">
                {{ $daftarToken->withQueryString()->links() }}
            </div>
        @endif
    </div>
    
    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-3"><i class="bi bi-arrow-counterclockwise text-warning"></i> Reset Token</span>
                <span class="ms-3"><i class="bi bi-trash text-danger"></i> Hapus</span>
            </small>
        </div>
    </div>
</div>
@endsection
