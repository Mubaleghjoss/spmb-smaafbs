@extends('layouts.public')

@section('title', 'Cek Status Kelulusan')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                {{-- Header --}}
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-search text-success" style="font-size: 2rem;"></i>
                    </div>
                    <h2 class="fw-bold">Cek Status Kelulusan</h2>
                    <p class="text-muted">Masukkan Nomor Pendaftaran SPMB untuk melihat status kelulusan Anda</p>
                </div>

                {{-- Form --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('cek-status') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="nomor_pendaftaran" class="form-label fw-medium">Nomor Pendaftaran SPMB</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-success text-white"><i class="bi bi-hash"></i></span>
                                    <input type="text" class="form-control" id="nomor_pendaftaran" name="nomor_pendaftaran" 
                                           placeholder="Contoh: SPMB-2026-0001" value="{{ $nomorPendaftaran }}"
                                           required autofocus>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success btn-lg w-100">
                                <i class="bi bi-search me-2"></i>Cek Status
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Hasil --}}
                @if($hasil !== null)
                    @if($hasil['ditemukan'])
                        {{-- Status Card --}}
                        @php
                            $colorMap = [
                                'lulus' => ['bg' => 'success', 'icon' => 'check-circle-fill', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
                                'tidak_lulus' => ['bg' => 'danger', 'icon' => 'x-circle-fill', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
                                'proses' => ['bg' => 'warning', 'icon' => 'hourglass-split', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
                            ];
                            $c = $colorMap[$hasil['status']];
                        @endphp

                        <div class="card border-0 shadow overflow-hidden" style="animation: fadeInUp 0.5s ease;">
                            {{-- Status Banner --}}
                            <div class="text-center text-white py-4 px-3" style="background: {{ $c['gradient'] }};">
                                <i class="bi bi-{{ $c['icon'] }} d-block mb-2" style="font-size: 3rem;"></i>
                                <h3 class="fw-bold mb-1">{{ $hasil['status_label'] }}</h3>
                                <p class="mb-0 opacity-75">{{ $hasil['keterangan'] }}</p>
                            </div>

                            {{-- Detail --}}
                            <div class="card-body p-4">
                                <table class="table table-borderless mb-3">
                                    <tr>
                                        <td class="text-muted" style="width: 140px;">No. Pendaftaran</td>
                                        <td class="fw-medium"><code>{{ $hasil['nomor_pendaftaran'] }}</code></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nama</td>
                                        <td class="fw-medium">{{ $hasil['nama'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Asal Sekolah</td>
                                        <td>{{ $hasil['asal_sekolah'] }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Posisi Saat Ini</td>
                                        <td>
                                            <span class="badge bg-{{ $c['bg'] }}">
                                                Tahap {{ $hasil['tahap'] }}: {{ $hasil['tahap_label'] }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>

                                {{-- Progress Bar --}}
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted">Progress SPMB</small>
                                        <small class="fw-medium">{{ $hasil['progres'] }}%</small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $c['bg'] }}" role="progressbar" 
                                             style="width: {{ $hasil['progres'] }}%" 
                                             aria-valuenow="{{ $hasil['progres'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>

                                {{-- Steps Visual --}}
                                <div class="d-flex justify-content-between mt-3">
                                    @for($i = 1; $i <= 7; $i++)
                                    <div class="text-center">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center {{ $i <= $hasil['tahap'] ? 'bg-'.$c['bg'].' text-white' : 'bg-light text-muted' }}" 
                                             style="width: 30px; height: 30px; font-size: 0.75rem; font-weight: 600;">
                                            @if($i < $hasil['tahap'])
                                                <i class="bi bi-check"></i>
                                            @else
                                                {{ $i }}
                                            @endif
                                        </div>
                                    </div>
                                    @endfor
                                </div>

                                @if($hasil['status'] === 'lulus')
                                    <div class="mt-4 pt-3 border-top">
                                        @if(!empty($hasil['download_sk_url']))
                                            <a href="{{ $hasil['download_sk_url'] }}" class="btn btn-success btn-lg w-100">
                                                <i class="bi bi-download me-2"></i>Download SK Kelulusan
                                            </a>
                                            @if(!empty($hasil['sk_gelombang']))
                                                <p class="text-muted text-center small mb-0 mt-2">
                                                    SK {{ $hasil['sk_gelombang'] }}
                                                </p>
                                            @endif
                                        @else
                                            <div class="alert alert-warning mb-0">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                File SK kelulusan belum tersedia. Silakan hubungi panitia SPMB.
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                    @else
                        {{-- Tidak Ditemukan --}}
                        <div class="card border-0 shadow-sm" style="animation: fadeInUp 0.5s ease;">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-question-circle text-muted d-block mb-3" style="font-size: 3rem;"></i>
                                <h5 class="fw-bold text-muted">Data Tidak Ditemukan</h5>
                                <p class="text-muted mb-0">Nomor pendaftaran <code>{{ $nomorPendaftaran }}</code> tidak ditemukan dalam sistem. Pastikan nomor yang Anda masukkan sudah benar.</p>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</section>

@push('styles')
<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
@endpush
@endsection
