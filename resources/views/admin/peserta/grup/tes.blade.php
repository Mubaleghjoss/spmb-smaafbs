@extends('layouts.admin')

@section('title', 'Atur Tes - ' . $grup->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Atur Tes untuk Grup</h1>
            <p class="text-muted mb-0">{{ $grup->nama }}</p>
        </div>
        <a href="{{ route('admin.peserta.grup.show', $grup) }}" class="btn btn-outline-secondary">
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
                    <h5 class="mb-0">Pilih Tes yang Bisa Diikuti Grup Ini</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.peserta.grup.simpan-tes', $grup) }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Daftar Tes</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                                        Pilih Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                                        Hapus Semua
                                    </button>
                                </div>
                            </div>
                            
                            @if($semuaTes->count() > 0)
                                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                    @foreach($semuaTes as $tes)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input tes-checkbox" type="checkbox" 
                                                   name="tes_ids[]" value="{{ $tes->id }}" 
                                                   id="tes{{ $tes->id }}"
                                                   {{ $tesTerpilih->contains('id', $tes->id) ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex justify-content-between w-100" for="tes{{ $tes->id }}">
                                                <span>
                                                    {{ $tes->nama }}
                                                    @if($tes->status === 'draft')
                                                        <span class="badge bg-secondary ms-1">Draft</span>
                                                    @elseif($tes->status === 'aktif')
                                                        <span class="badge bg-success ms-1">Aktif</span>
                                                    @else
                                                        <span class="badge bg-dark ms-1">Selesai</span>
                                                    @endif
                                                </span>
                                                <span class="badge bg-info">{{ $tes->soal_count }} soal</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted border rounded">
                                    Belum ada tes. <a href="{{ route('admin.tes.create') }}">Buat tes baru</a>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Simpan Pengaturan
                            </button>
                            <a href="{{ route('admin.peserta.grup.show', $grup) }}" class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Info Grup</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Nama Grup</td>
                            <td class="text-end"><strong>{{ $grup->nama }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jumlah Peserta</td>
                            <td class="text-end"><strong>{{ $grup->peserta()->count() }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tes Terpilih</td>
                            <td class="text-end"><strong id="selectedCount">{{ $tesTerpilih->count() }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Tes Terpilih Saat Ini</h5>
                </div>
                <div class="card-body">
                    @if($tesTerpilih->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($tesTerpilih as $tes)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    {{ $tes->nama }}
                                    <span class="badge bg-info">{{ $tes->soal_count }} soal</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 text-center">Belum ada tes yang dipilih</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.tes-checkbox');
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const selectedCountEl = document.getElementById('selectedCount');

    function updateCount() {
        const checked = document.querySelectorAll('.tes-checkbox:checked');
        selectedCountEl.textContent = checked.length;
    }

    selectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateCount();
    });

    deselectAllBtn.addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateCount();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateCount);
    });
});
</script>
@endpush
@endsection
