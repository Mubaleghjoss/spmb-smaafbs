@extends('layouts.admin')

@section('title', 'Penilaian Esai: ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Penilaian Esai</h1>
            <small class="text-muted">{{ $tes->nama }}</small>
        </div>
        <a href="{{ route('admin.hasil.show', $tes) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Esai Belum Dinilai ({{ $esaiBelumDinilai->count() }})</h6>
        </div>
        <div class="card-body">
            @if($esaiBelumDinilai->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-check-circle display-1 text-success"></i>
                    <p class="mt-3 text-muted">Semua esai sudah dinilai.</p>
                </div>
            @else
                @foreach($esaiBelumDinilai as $jawaban)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <strong>{{ $jawaban->sesiTes->peserta->nama }}</strong>
                                <br>
                                <small class="text-muted">{{ $jawaban->sesiTes->peserta->nomor_pendaftaran ?? '-' }}</small>
                            </div>
                            <span class="badge bg-warning text-dark">Belum Dinilai</span>
                        </div>

                        <div class="mb-3">
                            <strong>Pertanyaan:</strong>
                            <div class="mt-1 p-2 bg-light rounded">{!! $jawaban->soal->pertanyaan !!}</div>
                        </div>

                        <div class="mb-3">
                            <strong>Jawaban Peserta:</strong>
                            <div class="mt-1 p-2 bg-white border rounded">
                                {{ $jawaban->jawaban_esai ?? 'Tidak dijawab' }}
                            </div>
                        </div>

                        @if($jawaban->soal->pembahasan)
                            <div class="mb-3">
                                <strong>Kunci/Pembahasan:</strong>
                                <div class="mt-1 p-2 bg-info bg-opacity-10 rounded">
                                    {!! $jawaban->soal->pembahasan !!}
                                </div>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.hasil.simpan-penilaian-esai', [$tes, $jawaban]) }}" class="d-flex gap-2">
                            @csrf
                            <button type="submit" name="benar" value="1" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Benar
                            </button>
                            <button type="submit" name="benar" value="0" class="btn btn-danger">
                                <i class="bi bi-x-lg"></i> Salah
                            </button>
                        </form>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
