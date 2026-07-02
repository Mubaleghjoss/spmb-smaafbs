@extends('layouts.public')

@section('title', 'Jadwal SPMB')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Jadwal SPMB</h1>
            <p class="text-muted lead">Tahun Ajaran {{ $branding['tahun_ajaran'] ?? (date('Y') . '/' . (date('Y') + 1)) }}</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th>Kegiatan</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($jadwal as $item)
                                    <tr>
                                        <td>
                                            <i class="bi bi-{{ $item['icon'] ?? 'calendar' }} text-success me-2"></i>
                                            {{ $item['kegiatan'] }}
                                        </td>
                                        <td>{{ $item['tanggal'] }}</td>
                                        <td>
                                            @php
                                                $badgeClass = match($item['status'] ?? 'info') {
                                                    'dibuka' => 'bg-success',
                                                    'akan_datang' => 'bg-secondary',
                                                    'selesai' => 'bg-dark',
                                                    'persiapan' => 'bg-warning text-dark',
                                                    default => 'bg-info'
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">{{ $item['keterangan'] ?? '-' }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                @if(!empty($catatan))
                <div class="alert alert-info mt-4">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Catatan:</strong> {{ $catatan }}
                </div>
                @endif
                
                <div class="text-center mt-4">
                    <a href="{{ route('daftar') }}" class="btn btn-success btn-lg">
                        <i class="bi bi-pencil-square me-2"></i>Daftar Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
