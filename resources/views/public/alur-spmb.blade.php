@extends('layouts.public')

@section('title', 'Alur SPMB')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Alur Pendaftaran SPMB</h1>
            <p class="text-muted lead">{{ $branding['teks_alur_spmb'] ?? 'Ikuti setiap tahapan untuk menjadi bagian dari keluarga besar' }} {{ $branding['nama_institusi'] ?? 'SMA Al Furqon Boarding School' }} - Tahun Ajaran {{ $branding['tahun_ajaran'] ?? date('Y') . '/' . (date('Y') + 1) }}</p>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @foreach($alurSpmb as $index => $item)
                <div class="card border-0 shadow-sm mb-4 tahapan-card">
                    <div class="card-body p-4">
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="tahapan-number">{{ $item['nomor'] ?? ($index + 1) }}</div>
                            </div>
                            <div class="flex-grow-1 ms-4">
                                <h5 class="fw-bold mb-2">
                                    <i class="bi bi-{{ $item['icon'] ?? 'circle-fill' }} text-success me-2"></i>
                                    {{ $item['judul'] }}
                                </h5>
                                <p class="text-muted mb-3">{{ $item['deskripsi'] }}</p>
                                @if(!empty($item['detail']))
                                <ul class="list-unstyled mb-0">
                                    @foreach($item['detail'] as $detail)
                                    <li class="mb-1">
                                        <i class="bi bi-check2 text-success me-2"></i>{{ $detail }}
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                @if($index < count($alurSpmb) - 1)
                <div class="text-center mb-4">
                    <i class="bi bi-arrow-down text-success" style="font-size: 1.5rem;"></i>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="{{ route('daftar') }}" class="btn btn-success btn-lg">
                <i class="bi bi-pencil-square me-2"></i>Mulai Pendaftaran
            </a>
        </div>
    </div>
</section>
@endsection
