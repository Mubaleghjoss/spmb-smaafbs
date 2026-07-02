@extends('layouts.tim-spmb')

@section('title', 'Daftar Peserta')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Daftar Peserta</h1>
        <a href="{{ route('tim-spmb.peserta.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> Tambah Peserta
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="cari" class="form-control" placeholder="Cari nama, nomor, email..." value="{{ request('cari') }}">
                </div>
                <div class="col-md-3">
                    <select name="tahap" class="form-select">
                        <option value="">Semua Tahap</option>
                        @for($i = 1; $i <= 7; $i++)
                            <option value="{{ $i }}" {{ request('tahap') == $i ? 'selected' : '' }}>Tahap {{ $i }}</option>
                        @endfor
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
                            <th>No. Pendaftaran</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Asal Sekolah</th>
                            <th>Tahap</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($peserta as $p)
                        <tr>
                            <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                            <td>{{ $p->nama }}</td>
                            <td>{{ $p->email }}</td>
                            <td>{{ $p->asal_sekolah ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $p->tahap_saat_ini >= 7 ? 'success' : 'primary' }}">
                                    Tahap {{ $p->tahap_saat_ini }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('tim-spmb.peserta.show', $p) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada peserta
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($peserta->hasPages())
        <div class="card-footer">
            {{ $peserta->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
