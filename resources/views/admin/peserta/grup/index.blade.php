@extends('layouts.admin')

@section('title', 'Kelola Grup')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Kelola Grup</h1>
        <div>
            <a href="{{ route('admin.peserta.index') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali ke Peserta
            </a>
            <a href="{{ route('admin.peserta.grup.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Grup
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
                    <h6 class="card-title">Total Grup</h6>
                    <h2 class="mb-0">{{ $statistik['total'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Grup dengan Peserta</h6>
                    <h2 class="mb-0">{{ $statistik['dengan_peserta'] }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h6 class="card-title">Grup Kosong</h6>
                    <h2 class="mb-0">{{ $statistik['kosong'] }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Daftar Grup -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Nama Grup</th>
                            <th>Keterangan</th>
                            <th width="100">Peserta</th>
                            <th width="80">Tes</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($grup as $index => $g)
                            <tr>
                                <td>{{ $grup->firstItem() + $index }}</td>
                                <td><strong>{{ $g->nama }}</strong></td>
                                <td>{{ Str::limit($g->keterangan, 50) ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $g->peserta_count }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $g->tes_count }}</span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('admin.peserta.grup.show', $g) }}" class="btn btn-sm btn-info text-white">
                                            <i class="bi bi-eye me-1"></i>Detail
                                        </a>
                                        <a href="{{ route('admin.peserta.grup.edit', $g) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil me-1"></i>Edit
                                        </a>
                                        <form action="{{ route('admin.peserta.grup.destroy', $g) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Yakin ingin menghapus grup ini?')">
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
                                    Belum ada grup. <a href="{{ route('admin.peserta.grup.create') }}">Tambah grup pertama</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $grup->links() }}
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
            </small>
        </div>
    </div>
</div>
@endsection
