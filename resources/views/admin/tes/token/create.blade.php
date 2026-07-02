@extends('layouts.admin')

@section('title', 'Generate Token')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Generate Token Baru</h1>
            <p class="text-muted mb-0">{{ $tes->nama }}</p>
        </div>
        <a href="{{ route('admin.tes.token.index', $tes) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Generate Batch Token</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.tes.token.store', $tes) }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah Token <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('jumlah') is-invalid @enderror" 
                                   id="jumlah" name="jumlah" value="{{ old('jumlah', 10) }}" 
                                   min="1" max="500" required>
                            @error('jumlah')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maksimal 500 token per batch</div>
                        </div>

                        <div class="mb-3">
                            <label for="kedaluwarsa" class="form-label">Kedaluwarsa</label>
                            <input type="datetime-local" class="form-control @error('kedaluwarsa') is-invalid @enderror" 
                                   id="kedaluwarsa" name="kedaluwarsa" value="{{ old('kedaluwarsa') }}">
                            @error('kedaluwarsa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Kosongkan jika token tidak memiliki batas waktu</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-key me-1"></i> Generate Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Token digunakan peserta untuk mengakses tes
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Setiap token hanya dapat digunakan sekali
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Token yang sudah kedaluwarsa tidak dapat digunakan
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-info-circle text-primary me-2"></i>
                            Anda dapat mengekspor daftar token ke Excel
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Preset Waktu Kedaluwarsa</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setKedaluwarsa(1)">+1 Jam</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setKedaluwarsa(3)">+3 Jam</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setKedaluwarsa(6)">+6 Jam</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setKedaluwarsa(12)">+12 Jam</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setKedaluwarsa(24)">+1 Hari</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" 
                                onclick="setKedaluwarsa(168)">+1 Minggu</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function setKedaluwarsa(jam) {
    const now = new Date();
    now.setHours(now.getHours() + jam);
    const formatted = now.toISOString().slice(0, 16);
    document.getElementById('kedaluwarsa').value = formatted;
}
</script>
@endpush
@endsection
