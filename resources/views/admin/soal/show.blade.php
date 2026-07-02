@extends('layouts.admin')

@section('title', 'Detail Soal')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Detail Soal</h1>
        <div>
            <a href="{{ route('admin.soal.edit', $soal) }}" class="btn btn-primary me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('admin.soal.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pertanyaan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        {!! $soal->pertanyaan !!}
                    </div>

                    @if($soal->media)
                        <div class="mb-4">
                            @if($soal->tipe_media === 'gambar')
                                <img src="{{ Storage::url($soal->media) }}" class="img-fluid rounded" alt="Media" style="max-height: 300px;">
                            @elseif($soal->tipe_media === 'audio')
                                <audio controls class="w-100">
                                    <source src="{{ Storage::url($soal->media) }}">
                                </audio>
                            @elseif($soal->tipe_media === 'video')
                                <video controls class="w-100" style="max-height: 300px;">
                                    <source src="{{ Storage::url($soal->media) }}">
                                </video>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @if($soal->tipe !== 'esai')
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pilihan Jawaban</h5>
                </div>
                <div class="card-body">
                    @foreach($soal->jawaban as $index => $jawaban)
                        <div class="d-flex align-items-start mb-3 p-3 rounded {{ $jawaban->benar ? 'bg-success bg-opacity-10 border border-success' : 'bg-light' }}">
                            <span class="badge {{ $jawaban->benar ? 'bg-success' : 'bg-secondary' }} me-3">
                                {{ chr(65 + $index) }}
                            </span>
                            <div class="flex-grow-1">
                                {!! $jawaban->isi_jawaban !!}
                            </div>
                            @if($jawaban->benar)
                                <span class="badge bg-success ms-2">
                                    <i class="bi bi-check-lg"></i> Benar
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($soal->pembahasan)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pembahasan</h5>
                </div>
                <div class="card-body">
                    {!! $soal->pembahasan !!}
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">ID</td>
                            <td class="text-end">{{ $soal->id }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Topik</td>
                            <td class="text-end">{{ $soal->topik?->nama ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tipe</td>
                            <td class="text-end">
                                @switch($soal->tipe)
                                    @case('pilihan_ganda')
                                        <span class="badge bg-primary">Pilihan Ganda</span>
                                        @break
                                    @case('jawaban_ganda')
                                        <span class="badge bg-info">Jawaban Ganda</span>
                                        @break
                                    @case('esai')
                                        <span class="badge bg-warning">Esai</span>
                                        @break
                                    @case('benar_salah')
                                        <span class="badge bg-secondary">Benar/Salah</span>
                                        @break
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Bobot</td>
                            <td class="text-end">{{ $soal->bobot }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td class="text-end">
                                @if($soal->aktif)
                                    <span class="badge bg-success">Aktif</span>
                                @else
                                    <span class="badge bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dibuat oleh</td>
                            <td class="text-end">{{ $soal->pembuat?->nama ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Dibuat</td>
                            <td class="text-end">{{ $soal->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Diperbarui</td>
                            <td class="text-end">{{ $soal->updated_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($soal->riwayat->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Riwayat Perubahan</h5>
                </div>
                <div class="card-body">
                    @foreach($soal->riwayat as $riwayat)
                        <div class="border-start border-3 border-primary ps-3 mb-3">
                            <small class="text-muted">
                                {{ $riwayat->created_at->format('d/m/Y H:i') }}
                                oleh {{ $riwayat->pengubah?->nama ?? 'Sistem' }}
                            </small>
                            <p class="mb-0 small">Pertanyaan diubah</p>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
