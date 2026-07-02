@extends('instalasi.layout')

@section('content')
<h5 class="mb-4"><i class="bi bi-check-circle"></i> Cek Requirements Sistem</h5>

@php
    $allPassed = collect($requirements)->every(fn($r) => $r['status']);
@endphp

<div class="requirements-list">
    @foreach($requirements as $key => $req)
    <div class="requirement-item">
        <div class="requirement-icon">
            @if($req['status'])
                <i class="bi bi-check-circle-fill text-success"></i>
            @else
                <i class="bi bi-x-circle-fill text-danger"></i>
            @endif
        </div>
        <div class="flex-grow-1">
            {{ $req['label'] }}
            @if(isset($req['current']))
                <small class="text-muted">({{ $req['current'] }})</small>
            @endif
        </div>
        <div>
            @if($req['status'])
                <span class="badge bg-success">OK</span>
            @else
                <span class="badge bg-danger">Gagal</span>
            @endif
        </div>
    </div>
    @endforeach
</div>

<div class="mt-4 d-flex justify-content-between">
    <div></div>
    @if($allPassed)
        <a href="{{ route('instalasi.database') }}" class="btn btn-primary">
            Lanjutkan <i class="bi bi-arrow-right"></i>
        </a>
    @else
        <button class="btn btn-secondary" disabled>
            Perbaiki requirements terlebih dahulu
        </button>
    @endif
</div>
@endsection
