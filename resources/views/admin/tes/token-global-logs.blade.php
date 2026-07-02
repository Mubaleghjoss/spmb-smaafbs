@extends('layouts.admin')

@section('title', 'Log Penggunaan Token')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Log Penggunaan Token</h1>
            <p class="text-muted mb-0">Token: <code class="fs-5">{{ $tokenGlobal->kode }}</code></p>
        </div>
        <a href="{{ route('admin.token-global.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    {{-- Info Token --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Nama:</strong><br>
                    {{ $tokenGlobal->nama ?? '-' }}
                </div>
                <div class="col-md-3">
                    <strong>Status:</strong><br>
                    @if(!$tokenGlobal->aktif)
                        <span class="badge bg-secondary">Nonaktif</span>
                    @elseif($tokenGlobal->sudah_kedaluwarsa)
                        <span class="badge bg-danger">Kedaluwarsa</span>
                    @elseif($tokenGlobal->belum_mulai)
                        <span class="badge bg-warning">Belum Mulai</span>
                    @else
                        <span class="badge bg-success">Aktif</span>
                    @endif
                </div>
                <div class="col-md-3">
                    <strong>Total Penggunaan:</strong><br>
                    <span class="badge bg-info fs-6">{{ $tokenGlobal->jumlah_penggunaan }}x</span>
                </div>
                <div class="col-md-3">
                    <strong>Tes yang Diizinkan:</strong><br>
                    @if($tokenGlobal->tes->isEmpty())
                        <span class="badge bg-success">Semua Tes</span>
                    @else
                        {{ $tokenGlobal->tes->count() }} tes
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Daftar Log --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Riwayat Penggunaan</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Peserta</th>
                            <th>No Pendaftaran</th>
                            <th>IP Address</th>
                            <th>Browser</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $index => $log)
                            <tr>
                                <td>{{ $logs->firstItem() + $index }}</td>
                                <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>
                                    @if($log->peserta)
                                        <a href="{{ route('admin.peserta.show', $log->peserta) }}">
                                            {{ $log->peserta->nama }}
                                        </a>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->peserta)
                                        <code>{{ $log->peserta->nomor_pendaftaran }}</code>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td><code>{{ $log->ip_address ?? '-' }}</code></td>
                                <td>
                                    <small class="text-muted">{{ Str::limit($log->user_agent, 50) }}</small>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada log penggunaan untuk token ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
