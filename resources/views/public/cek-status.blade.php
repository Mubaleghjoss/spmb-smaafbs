@extends('layouts.public')

@section('title', 'Cek Status Kelulusan')

@section('content')
<section class="tk-hero">
    <div class="container text-center">
        <span class="tk-eyebrow mb-3"><span class="dot"></span>Cek Status</span>
        <h1 class="fw-bold display-6 mb-2">Cek Status Kelulusan</h1>
        <p class="tk-sub mb-0" style="opacity:.92">Masukkan Nomor Pendaftaran SPMB untuk melihat status kelulusan Anda</p>
    </div>
</section>

<section class="tk-section cream">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                {{-- Satu kartu: form + hasil menyatu --}}
                <div class="cs-card reveal">
                    {{-- Form --}}
                    <form method="POST" action="{{ route('cek-status') }}" class="mb-0">
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

                    {{-- Hasil (di dalam kartu yang sama) --}}
                    @if($hasil !== null)
                        <hr class="my-4">
                        @if($hasil['ditemukan'])
                            @php
                                $colorMap = [
                                    'lulus' => ['bg' => 'success', 'icon' => 'check-circle-fill', 'gradient' => 'linear-gradient(135deg, #10b981, #059669)'],
                                    'tidak_lulus' => ['bg' => 'danger', 'icon' => 'x-circle-fill', 'gradient' => 'linear-gradient(135deg, #ef4444, #dc2626)'],
                                    'proses' => ['bg' => 'warning', 'icon' => 'hourglass-split', 'gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)'],
                                ];
                                $c = $colorMap[$hasil['status']];
                            @endphp

                            {{-- Banner status --}}
                            <div class="text-center text-white py-4 px-3 rounded-3 mb-3" style="background: {{ $c['gradient'] }};">
                                <i class="bi bi-{{ $c['icon'] }} d-block mb-2" style="font-size: 3rem;"></i>
                                <h3 class="fw-bold mb-1">{{ $hasil['status_label'] }}</h3>
                                <p class="mb-0 opacity-75">{{ $hasil['keterangan'] }}</p>
                            </div>

                            {{-- Detail --}}
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
                            <div class="d-flex justify-content-between mt-3 langkah-row">
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
                                            <p class="text-muted text-center small mb-0 mt-2">SK {{ $hasil['sk_gelombang'] }}</p>
                                        @endif
                                    @else
                                        <div class="alert alert-warning mb-0">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            File SK kelulusan belum tersedia. Silakan hubungi panitia SPMB.
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            {{-- Tidak Ditemukan --}}
                            <div class="text-center py-4">
                                <i class="bi bi-question-circle text-muted d-block mb-3" style="font-size: 3rem;"></i>
                                <h5 class="fw-bold text-muted">Data Tidak Ditemukan</h5>
                                <p class="text-muted mb-0">Nomor pendaftaran <code>{{ $nomorPendaftaran }}</code> tidak ditemukan dalam sistem. Pastikan nomor yang Anda masukkan sudah benar.</p>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* Satu kartu pencarian yang membungkus form + hasil */
    .cs-card {
        background: #fff;
        border: 1px solid rgba(16,36,26,.06);
        border-radius: 1.1rem;
        padding: 1.5rem;
        box-shadow: 0 18px 40px -30px rgba(16,36,26,.30);
    }
    .cs-card table { table-layout: fixed; width: 100%; }
    .cs-card td { overflow-wrap: anywhere; word-break: break-word; }
    .cs-card .langkah-row { flex-wrap: wrap; gap: .35rem; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const els = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) { els.forEach(e => e.classList.add('is-visible')); return; }
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('is-visible'); obs.unobserve(en.target); } });
    }, { threshold: 0.05 });
    els.forEach(e => obs.observe(e));
});
</script>
@endpush
