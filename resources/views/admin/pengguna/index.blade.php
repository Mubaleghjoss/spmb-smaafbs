@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Manajemen Pengguna</h1>
        <a href="{{ route('admin.pengguna.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Tambah Pengguna
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama atau email..." value="{{ request('cari') }}">
                </div>
                <div class="col-md-3">
                    <select name="peran" class="form-select">
                        <option value="">Semua Peran</option>
                        <option value="admin" {{ request('peran') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="operator" {{ request('peran') == 'operator' ? 'selected' : '' }}>Operator</option>
                        <option value="tim_spmb" {{ request('peran') == 'tim_spmb' ? 'selected' : '' }}>Tim SPMB</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Peran</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pengguna as $p)
                        <tr>
                            <td>
                                <strong>{{ $p->nama }}</strong>
                                @if($p->id === auth('pengguna')->id())
                                    <span class="badge bg-info ms-1">Anda</span>
                                @endif
                            </td>
                            <td>{{ $p->email }}</td>
                            <td>
                                @if($p->peran == 'admin')
                                    <span class="badge bg-danger">Admin</span>
                                    <small class="text-muted d-block">Akses penuh</small>
                                @elseif($p->peran == 'operator')
                                    <span class="badge bg-primary">Operator</span>
                                    @if($p->menu_akses)
                                        <small class="text-muted d-block">{{ count($p->menu_akses) }} menu</small>
                                    @endif
                                @else
                                    <span class="badge bg-success">Tim SPMB</span>
                                    @if($p->menu_akses)
                                        <small class="text-muted d-block">{{ count($p->menu_akses) }} menu</small>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($p->aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td>{{ $p->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.pengguna.edit', $p) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($p->id !== auth('pengguna')->id())
                                        <form action="{{ route('admin.pengguna.toggle-aktif', $p) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-{{ $p->aktif ? 'warning' : 'success' }}" title="{{ $p->aktif ? 'Nonaktifkan' : 'Aktifkan' }}">
                                                <i class="bi bi-{{ $p->aktif ? 'pause' : 'play' }}"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.pengguna.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus pengguna ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">
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
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada pengguna
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($pengguna->hasPages())
        <div class="card-footer">
            {{ $pengguna->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
