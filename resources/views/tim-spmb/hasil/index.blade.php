@extends('layouts.tim-spmb')

@section('title', 'Hasil Ujian')

@section('content')
<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Hasil Ujian</h1>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Tes</th>
                            <th>Peserta Selesai</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tes as $t)
                        <tr>
                            <td>
                                <strong>{{ $t->nama }}</strong>
                                @if($t->deskripsi)
                                    <br><small class="text-muted">{{ Str::limit($t->deskripsi, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $t->peserta_selesai }} peserta</span>
                            </td>
                            <td>
                                <a href="{{ route('tim-spmb.hasil.show', $t) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Lihat Hasil
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Belum ada tes
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tes->hasPages())
        <div class="card-footer">
            {{ $tes->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
