@extends('layouts.admin')

@section('title', 'Atur Grup - ' . $tes->nama)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Atur Grup Peserta</h1>
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
                    <h5 class="mb-0">Pilih Grup yang Boleh Mengikuti Tes</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.tes.simpan-grup', $tes) }}" method="POST">
                        @csrf
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-1"></i>
                            Jika tidak ada grup yang dipilih, tes akan tersedia untuk <strong>semua peserta</strong>.
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Daftar Grup</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAll">
                                        Pilih Semua
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAll">
                                        Hapus Semua
                                    </button>
                                </div>
                            </div>
                            
                            @if($semuaGrup->count() > 0)
                                <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto;">
                                    @foreach($semuaGrup as $grup)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input grup-checkbox" type="checkbox" 
                                                   name="grup_ids[]" value="{{ $grup->id }}" 
                                                   id="grup{{ $grup->id }}"
                                                   {{ $grupTerpilih->contains('id', $grup->id) ? 'checked' : '' }}>
                                            <label class="form-check-label d-flex justify-content-between w-100" for="grup{{ $grup->id }}">
                                                <span>{{ $grup->nama }}</span>
                                                <span class="badge bg-secondary">{{ $grup->peserta_count }} peserta</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted border rounded">
                                    Belum ada grup. <a href="{{ route('admin.peserta.grup.create') }}">Buat grup baru</a>
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
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Ringkasan</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Grup Terpilih</td>
                            <td class="text-end"><strong id="selectedCount">{{ $grupTerpilih->count() }}</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Potensial Peserta</td>
                            <td class="text-end"><strong id="potentialCount">{{ $potensialPeserta }}</strong></td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Grup Terpilih Saat Ini</h5>
                </div>
                <div class="card-body">
                    @if($grupTerpilih->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($grupTerpilih as $grup)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    {{ $grup->nama }}
                                    <span class="badge bg-info">{{ $grup->peserta_count }} peserta</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted mb-0 text-center">
                            <i class="bi bi-globe me-1"></i> Tersedia untuk semua peserta
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
    const checkboxes = document.querySelectorAll('.grup-checkbox');
    const selectAllBtn = document.getElementById('selectAll');
    const deselectAllBtn = document.getElementById('deselectAll');
    const selectedCountEl = document.getElementById('selectedCount');

    function updateCount() {
        const checked = document.querySelectorAll('.grup-checkbox:checked');
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
