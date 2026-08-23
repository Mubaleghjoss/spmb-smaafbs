@extends('layouts.admin')

@section('title', 'Atur Periode - ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Atur Periode Tes</h1>
            <p class="text-muted mb-0">{{ $tes->nama }}</p>
        </div>
        <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pilih Tahun Ajaran yang Memakai Tes Ini</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.tes.simpan-periode', $tes) }}" method="POST">
                        @csrf

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Tes hanya akan muncul untuk peserta pada <strong>tahun ajaran</strong> yang dicentang.
                            Jika <strong>tidak ada</strong> yang dicentang, tes berlaku untuk <strong>semua periode</strong>.
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Daftar Tahun Ajaran</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">Pilih Semua</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">Hapus Semua</button>
                                </div>
                            </div>

                            @if($semuaTahun->count() > 0)
                                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                    @foreach($semuaTahun as $tahun)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input tahun-checkbox" type="checkbox"
                                                   name="tahun_ajaran_ids[]" value="{{ $tahun->id }}"
                                                   id="tahun{{ $tahun->id }}"
                                                   {{ in_array($tahun->id, $tahunTerpilih) ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex justify-content-between w-100" for="tahun{{ $tahun->id }}">
                                                <span>
                                                    {{ $tahun->nama }}
                                                    @if($tahun->default)
                                                        <span class="badge bg-primary ms-1">Default</span>
                                                    @endif
                                                    @if($tahun->aktif)
                                                        <span class="badge bg-success ms-1">Aktif</span>
                                                    @endif
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted border rounded">
                                    Belum ada tahun ajaran.
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan
                            </button>
                            <a href="{{ route('admin.tes.show', $tes) }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Status Saat Ini</h5>
                </div>
                <div class="card-body">
                    @if(count($tahunTerpilih) > 0)
                        <p class="small text-muted mb-2">Tes ini aktif untuk tahun ajaran:</p>
                        <ul class="list-group list-group-flush">
                            @foreach($semuaTahun->whereIn('id', $tahunTerpilih) as $tahun)
                                <li class="list-group-item px-0">{{ $tahun->nama }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 text-center">
                            <i class="bi bi-globe me-1"></i> Tersedia untuk semua periode
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.tahun-checkbox');
    document.getElementById('selectAll').addEventListener('click', () => checkboxes.forEach(cb => cb.checked = true));
    document.getElementById('deselectAll').addEventListener('click', () => checkboxes.forEach(cb => cb.checked = false));
});
</script>
@endpush
@endsection
