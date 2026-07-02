@extends('layouts.admin')

@section('title', 'Detail Grup')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $grup->nama }}</h1>
        <div>
            <a href="{{ route('admin.peserta.grup.edit', $grup) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('admin.peserta.grup.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Grup</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Nama</td>
                            <td class="text-end">{{ $grup->nama }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Keterangan</td>
                            <td class="text-end">{{ $grup->keterangan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jumlah Peserta</td>
                            <td class="text-end"><span class="badge bg-info">{{ $grup->peserta->count() }}</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jumlah Tes</td>
                            <td class="text-end"><span class="badge bg-primary">{{ $grup->tes()->count() }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Tes yang Di-assign -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Tes yang Bisa Diikuti</h5>
                    <a href="{{ route('admin.peserta.grup.tes', $grup) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-journal-text me-1"></i> Atur Tes
                    </a>
                </div>
                <div class="card-body">
                    @php
                        $tesTerpilih = $grup->tes()->withCount('soal')->get();
                    @endphp
                    @if($tesTerpilih->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($tesTerpilih as $tes)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <a href="{{ route('admin.tes.show', $tes) }}" class="text-decoration-none">
                                            {{ $tes->nama }}
                                        </a>
                                        @if($tes->status === 'aktif')
                                            <span class="badge bg-success ms-1">Aktif</span>
                                        @elseif($tes->status === 'draft')
                                            <span class="badge bg-secondary ms-1">Draft</span>
                                        @else
                                            <span class="badge bg-dark ms-1">Selesai</span>
                                        @endif
                                    </div>
                                    <span class="badge bg-info">{{ $tes->soal_count }} soal</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
                            <p class="mb-0">Belum ada tes yang di-assign</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar Peserta dalam Grup</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>No. Pendaftaran</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grup->peserta as $index => $p)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><code>{{ $p->nomor_pendaftaran }}</code></td>
                                        <td>{{ $p->nama }}</td>
                                        <td>{{ $p->email }}</td>
                                        <td>
                                            <a href="{{ route('admin.peserta.show', $p) }}" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Belum ada peserta dalam grup ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
