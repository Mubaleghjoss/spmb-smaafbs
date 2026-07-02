@extends('layouts.admin')

@section('title', 'Pengaturan Wawancara')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Pengaturan Wawancara & Surat Pernyataan</h1>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.pengaturan.wawancara.simpan') }}">
        @csrf

        {{-- Pertanyaan Orang Tua --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Pertanyaan Orang Tua / Wali</h5>
                    <button type="button" class="btn btn-dark btn-sm" onclick="tambahItem('pertanyaan-ortu', 'pertanyaan_ortu[]', 'bg-warning text-dark')">
                        <i class="bi bi-plus-circle me-1"></i>Tambah
                    </button>
                </div>
            </div>
            <div class="card-body" id="pertanyaan-ortu">
                @foreach($pertanyaanOrtu as $no => $pertanyaan)
                <div class="input-group mb-2 crud-item">
                    <span class="input-group-text bg-warning text-dark fw-bold" style="min-width:45px">{{ $no }}</span>
                    <input type="text" name="pertanyaan_ortu[]" class="form-control" value="{{ $pertanyaan }}">
                    <button type="button" class="btn btn-outline-danger" onclick="hapusItem(this)" title="Hapus"><i class="bi bi-trash"></i></button>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Pertanyaan Siswa --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-person me-2"></i>Pertanyaan Calon Siswa</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="tambahItem('pertanyaan-siswa', 'pertanyaan_siswa[]', 'bg-danger text-white')">
                        <i class="bi bi-plus-circle me-1"></i>Tambah
                    </button>
                </div>
            </div>
            <div class="card-body" id="pertanyaan-siswa">
                @foreach($pertanyaanSiswa as $no => $pertanyaan)
                <div class="input-group mb-2 crud-item">
                    <span class="input-group-text bg-danger text-white fw-bold" style="min-width:45px">{{ $no }}</span>
                    <input type="text" name="pertanyaan_siswa[]" class="form-control" value="{{ $pertanyaan }}">
                    <button type="button" class="btn btn-outline-danger" onclick="hapusItem(this)" title="Hapus"><i class="bi bi-trash"></i></button>
                </div>
                @endforeach
            </div>
        </div>

        <hr class="my-4">
        <h4 class="mb-3"><i class="bi bi-file-earmark-text me-2"></i>Surat Pernyataan</h4>

        {{-- Surat Pernyataan Siswa --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Poin Surat Pernyataan Siswa</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="tambahItemTextarea('sp-siswa', 'sp_siswa_poin[]', 'bg-primary text-white')">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Poin
                    </button>
                </div>
            </div>
            <div class="card-body" id="sp-siswa">
                <small class="text-muted d-block mb-3">Poin-poin yang muncul di surat pernyataan siswa. Gunakan sub-poin dengan format: <code>a. ...\nb. ...</code></small>
                @foreach($spSiswaPoin as $no => $poin)
                <div class="mb-3 crud-item">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-primary mt-1" style="min-width:30px">{{ $no }}</span>
                        <textarea name="sp_siswa_poin[]" class="form-control" rows="2">{{ $poin }}</textarea>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusItem(this)" title="Hapus"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Surat Pernyataan Orangtua --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-file-earmark-person me-2"></i>Poin Surat Pernyataan Orangtua</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="tambahItemTextarea('sp-ortu', 'sp_ortu_poin[]', 'bg-info text-white')">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Poin
                    </button>
                </div>
            </div>
            <div class="card-body" id="sp-ortu">
                <small class="text-muted d-block mb-3">Poin-poin yang muncul di surat pernyataan orangtua/wali.</small>
                @foreach($spOrtuPoin as $no => $poin)
                <div class="mb-3 crud-item">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-info mt-1" style="min-width:30px">{{ $no }}</span>
                        <textarea name="sp_ortu_poin[]" class="form-control" rows="2">{{ $poin }}</textarea>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusItem(this)" title="Hapus"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <hr class="my-4">
        <h4 class="mb-3"><i class="bi bi-pencil-square me-2"></i>Soal Tes Pegon</h4>

        {{-- Teks Pegon --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Teks / Soal Tes Pegon</h5>
                    <button type="button" class="btn btn-light btn-sm" onclick="tambahItem('teks-pegon', 'teks_pegon[]', 'bg-dark text-white')">
                        <i class="bi bi-plus-circle me-1"></i>Tambah Item
                    </button>
                </div>
            </div>
            <div class="card-body" id="teks-pegon">
                <small class="text-muted d-block mb-3">Teks yang akan diubah menjadi pegon oleh siswa. Setiap item = 1 baris soal.</small>
                @foreach($teksPegon as $no => $teks)
                <div class="input-group mb-2 crud-item">
                    <span class="input-group-text bg-dark text-white fw-bold" style="min-width:45px">{{ $no }}</span>
                    <input type="text" name="teks_pegon[]" class="form-control" value="{{ $teks }}">
                    <button type="button" class="btn btn-outline-danger" onclick="hapusItem(this)" title="Hapus"><i class="bi bi-trash"></i></button>
                </div>
                @endforeach
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary" onclick="resetDefault()">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Semua ke Default
            </button>
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save me-1"></i>Simpan Semua
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function tambahItem(containerId, inputName, badgeClass) {
    const container = document.getElementById(containerId);
    const items = container.querySelectorAll('.crud-item');
    const nomor = items.length + 1;

    const div = document.createElement('div');
    div.className = 'input-group mb-2 crud-item';
    div.innerHTML = `
        <span class="input-group-text ${badgeClass} fw-bold" style="min-width:45px">${nomor}</span>
        <input type="text" name="${inputName}" class="form-control" placeholder="Tulis teks baru..." autofocus>
        <button type="button" class="btn btn-outline-danger" onclick="hapusItem(this)" title="Hapus">
            <i class="bi bi-trash"></i>
        </button>
    `;
    container.appendChild(div);
    div.querySelector('input').focus();
    reindex(containerId);
}

function tambahItemTextarea(containerId, inputName, badgeClass) {
    const container = document.getElementById(containerId);
    const items = container.querySelectorAll('.crud-item');
    const nomor = items.length + 1;

    const div = document.createElement('div');
    div.className = 'mb-3 crud-item';
    div.innerHTML = `
        <div class="d-flex align-items-start gap-2">
            <span class="badge ${badgeClass} mt-1" style="min-width:30px">${nomor}</span>
            <textarea name="${inputName}" class="form-control" rows="2" placeholder="Tulis poin baru..."></textarea>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="hapusItem(this)" title="Hapus"><i class="bi bi-trash"></i></button>
        </div>
    `;
    container.appendChild(div);
    div.querySelector('textarea').focus();
    reindex(containerId);
}

function hapusItem(btn) {
    const item = btn.closest('.crud-item');
    const container = item.parentElement;
    if (confirm('Hapus item ini?')) {
        item.remove();
        reindex(container.id);
    }
}

function reindex(containerId) {
    const container = document.getElementById(containerId);
    const items = container.querySelectorAll('.crud-item');
    items.forEach((item, i) => {
        const badge = item.querySelector('.input-group-text, .badge');
        if (badge) badge.textContent = i + 1;
    });
}

function resetDefault() {
    if (confirm('Reset semua pengaturan ke default? Perubahan yang belum disimpan akan hilang.')) {
        window.location.href = '{{ route("admin.pengaturan.wawancara") }}?reset=1';
    }
}
</script>
@endpush
@endsection
