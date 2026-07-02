@extends('layouts.admin')

@section('title', 'Kelola Topik')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Kelola Topik</h1>
        <div>
            <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Bank Soal
            </a>
            <a href="{{ route('admin.soal.topik.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Topik
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
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Topik</h6>
                    <h2 class="mb-0">{{ $statistik['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Topik dengan Soal</h6>
                    <h2 class="mb-0">{{ $statistik['dengan_soal'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">Topik Kosong</h6>
                    <h2 class="mb-0">{{ $statistik['kosong'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Topik -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Nama Topik</th>
                            <th>Parent</th>
                            <th>Keterangan</th>
                            <th width="100">Jumlah Soal</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topik as $index => $t)
                            <tr>
                                <td>{{ $topik->firstItem() + $index }}</td>
                                <td>
                                    <strong>{{ $t->nama }}</strong>
                                </td>
                                <td>{{ $t->parent?->nama ?? '-' }}</td>
                                <td>{{ Str::limit($t->keterangan, 50) ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $t->soal_count }} soal</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.soal.topik.edit', $t) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <form action="{{ route('admin.soal.topik.destroy', $t) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus topik ini? Soal dalam topik ini akan dipindahkan ke parent atau menjadi tanpa topik.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada topik. <a href="{{ route('admin.soal.topik.create') }}">Tambah topik pertama</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $topik->links() }}
        </div>
    </div>
    
    {{-- Legend --}}
    <div class="card mt-3">
        <div class="card-body py-2">
            <small class="text-muted">
                <strong>Keterangan Aksi:</strong>
                <span class="ms-3"><i class="bi bi-pencil text-warning"></i> Edit</span>
                <span class="ms-3"><i class="bi bi-trash text-danger"></i> Hapus</span>
            </small>
        </div>
    </div>
</div>
@endsection
