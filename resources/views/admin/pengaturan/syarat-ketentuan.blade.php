@extends('layouts.admin')

@section('title', 'Pengaturan Syarat & Ketentuan SPMB')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="bi bi-file-earmark-text me-2"></i>Syarat & Ketentuan SPMB</h4>
            <p class="text-muted mb-0">Kelola isi syarat dan ketentuan yang ditampilkan di halaman pendaftaran</p>
        </div>
        <div>
            <a href="{{ route('admin.pengaturan.spmb') }}" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <button type="button" class="btn btn-success" onclick="tambahBagian()">
                <i class="bi bi-plus-circle me-1"></i>Tambah Bagian
            </button>
        </div>
    </div>

    @if(session('sukses'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.pengaturan.syarat-ketentuan.simpan') }}" id="formSyaratKetentuan">
        @csrf
        
        <div class="row">
            <div class="col-lg-8">
                {{-- Daftar Bagian --}}
                <div id="daftarBagian">
                    @foreach($bagian as $index => $item)
                    <div class="card border-0 shadow-sm mb-3 bagian-item" data-index="{{ $index }}">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">{{ $index + 1 }}</span>
                                <input type="text" class="form-control form-control-sm border-0 fw-bold" 
                                       name="bagian[{{ $index }}][judul]" 
                                       value="{{ $item['judul'] }}" 
                                       placeholder="Judul Bagian"
                                       style="width: 300px;">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="pindahAtas(this)" title="Pindah ke atas">
                                    <i class="bi bi-arrow-up"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="pindahBawah(this)" title="Pindah ke bawah">
                                    <i class="bi bi-arrow-down"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBagian(this)" title="Hapus bagian">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label small text-muted">Ikon (Bootstrap Icons)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text"><i class="{{ $item['ikon'] ?? 'bi-circle' }}"></i></span>
                                    <input type="text" class="form-control" 
                                           name="bagian[{{ $index }}][ikon]" 
                                           value="{{ $item['ikon'] ?? 'bi-circle' }}"
                                           placeholder="bi-1-circle">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalIkon" onclick="setTargetIkon(this)">
                                        <i class="bi bi-grid"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label small text-muted">Konten (HTML)</label>
                                <textarea class="form-control editor-konten" 
                                          name="bagian[{{ $index }}][konten]" 
                                          rows="8"
                                          placeholder="Masukkan konten HTML...">{{ $item['konten'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                    </button>
                    <button type="button" class="btn btn-outline-warning btn-lg" onclick="resetDefault()">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset ke Default
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Preview --}}
                <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Preview</h6>
                    </div>
                    <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                        <div id="previewKonten">
                            <p class="text-muted text-center">Klik "Refresh Preview" untuk melihat hasil</p>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <button type="button" class="btn btn-outline-success w-100" onclick="refreshPreview()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Preview
                        </button>
                    </div>
                </div>

                {{-- Panduan --}}
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Panduan</h6>
                    </div>
                    <div class="card-body small">
                        <p class="mb-2"><strong>Format HTML yang didukung:</strong></p>
                        <ul class="mb-3">
                            <li><code>&lt;ol&gt;</code> - Daftar bernomor</li>
                            <li><code>&lt;ul&gt;</code> - Daftar bullet</li>
                            <li><code>&lt;strong&gt;</code> - Teks tebal</li>
                            <li><code>&lt;em&gt;</code> - Teks miring</li>
                            <li><code>&lt;div class="alert"&gt;</code> - Kotak info</li>
                            <li><code>&lt;div class="card"&gt;</code> - Kartu</li>
                        </ul>
                        <p class="mb-2"><strong>Ikon yang tersedia:</strong></p>
                        <p class="text-muted">bi-1-circle, bi-2-circle, ... bi-8-circle, bi-check-circle, bi-exclamation-circle, dll.</p>
                        <a href="https://icons.getbootstrap.com/" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Lihat Semua Ikon
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Modal Pilih Ikon --}}
<div class="modal fade" id="modalIkon" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Ikon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2" id="daftarIkon">
                    @php
                    $ikonList = [
                        'bi-1-circle', 'bi-2-circle', 'bi-3-circle', 'bi-4-circle', 
                        'bi-5-circle', 'bi-6-circle', 'bi-7-circle', 'bi-8-circle', 'bi-9-circle',
                        'bi-check-circle', 'bi-exclamation-circle', 'bi-info-circle', 'bi-x-circle',
                        'bi-heart', 'bi-star', 'bi-bookmark', 'bi-flag', 'bi-award',
                        'bi-person', 'bi-people', 'bi-house', 'bi-building', 'bi-book',
                        'bi-file-earmark-text', 'bi-clipboard-check', 'bi-calendar', 'bi-clock',
                        'bi-cash-stack', 'bi-credit-card', 'bi-shield-check', 'bi-lock',
                    ];
                    @endphp
                    @foreach($ikonList as $ikon)
                    <div class="col-2">
                        <button type="button" class="btn btn-outline-secondary w-100 py-3" onclick="pilihIkon('{{ $ikon }}')">
                            <i class="{{ $ikon }} fs-4"></i>
                            <div class="small mt-1">{{ str_replace('bi-', '', $ikon) }}</div>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Template Bagian Baru --}}
<template id="templateBagian">
    <div class="card border-0 shadow-sm mb-3 bagian-item" data-index="__INDEX__">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="badge bg-success me-2">__NOMOR__</span>
                <input type="text" class="form-control form-control-sm border-0 fw-bold" 
                       name="bagian[__INDEX__][judul]" 
                       value="BAGIAN BARU"
                       placeholder="Judul Bagian"
                       style="width: 300px;">
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="pindahAtas(this)" title="Pindah ke atas">
                    <i class="bi bi-arrow-up"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="pindahBawah(this)" title="Pindah ke bawah">
                    <i class="bi bi-arrow-down"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="hapusBagian(this)" title="Hapus bagian">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label small text-muted">Ikon (Bootstrap Icons)</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi-circle"></i></span>
                    <input type="text" class="form-control" 
                           name="bagian[__INDEX__][ikon]" 
                           value="bi-circle"
                           placeholder="bi-1-circle">
                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalIkon" onclick="setTargetIkon(this)">
                        <i class="bi bi-grid"></i>
                    </button>
                </div>
            </div>
            <div class="mb-0">
                <label class="form-label small text-muted">Konten (HTML)</label>
                <textarea class="form-control editor-konten" 
                          name="bagian[__INDEX__][konten]" 
                          rows="8"
                          placeholder="Masukkan konten HTML..."></textarea>
            </div>
        </div>
    </div>
</template>

@push('scripts')
<script>
let targetIkonInput = null;

function tambahBagian() {
    const container = document.getElementById('daftarBagian');
    const template = document.getElementById('templateBagian').innerHTML;
    const index = container.querySelectorAll('.bagian-item').length;
    
    const html = template
        .replace(/__INDEX__/g, index)
        .replace(/__NOMOR__/g, index + 1);
    
    container.insertAdjacentHTML('beforeend', html);
    updateNomorBagian();
}

function hapusBagian(btn) {
    if (confirm('Yakin ingin menghapus bagian ini?')) {
        btn.closest('.bagian-item').remove();
        updateNomorBagian();
    }
}

function pindahAtas(btn) {
    const item = btn.closest('.bagian-item');
    const prev = item.previousElementSibling;
    if (prev && prev.classList.contains('bagian-item')) {
        item.parentNode.insertBefore(item, prev);
        updateNomorBagian();
    }
}

function pindahBawah(btn) {
    const item = btn.closest('.bagian-item');
    const next = item.nextElementSibling;
    if (next && next.classList.contains('bagian-item')) {
        item.parentNode.insertBefore(next, item);
        updateNomorBagian();
    }
}

function updateNomorBagian() {
    const items = document.querySelectorAll('.bagian-item');
    items.forEach((item, index) => {
        item.dataset.index = index;
        item.querySelector('.badge').textContent = index + 1;
        
        // Update name attributes
        item.querySelectorAll('[name]').forEach(input => {
            input.name = input.name.replace(/bagian\[\d+\]/, `bagian[${index}]`);
        });
    });
}

function setTargetIkon(btn) {
    targetIkonInput = btn.closest('.input-group').querySelector('input');
}

function pilihIkon(ikon) {
    if (targetIkonInput) {
        targetIkonInput.value = ikon;
        targetIkonInput.closest('.input-group').querySelector('.input-group-text i').className = ikon;
    }
    bootstrap.Modal.getInstance(document.getElementById('modalIkon')).hide();
}

function refreshPreview() {
    const container = document.getElementById('previewKonten');
    const items = document.querySelectorAll('.bagian-item');
    let html = '';
    
    items.forEach((item, index) => {
        const judul = item.querySelector('input[name*="[judul]"]').value;
        const ikon = item.querySelector('input[name*="[ikon]"]').value;
        const konten = item.querySelector('textarea[name*="[konten]"]').value;
        
        html += `
            <h6 class="fw-bold text-success mt-3 mb-2">
                <i class="${ikon} me-2"></i>${judul}
            </h6>
            ${konten}
        `;
    });
    
    container.innerHTML = html || '<p class="text-muted text-center">Tidak ada konten</p>';
}

function resetDefault() {
    if (confirm('Yakin ingin mereset ke pengaturan default? Semua perubahan akan hilang.')) {
        window.location.href = '{{ route("admin.pengaturan.syarat-ketentuan.reset") }}';
    }
}

// Update ikon preview saat input berubah
document.addEventListener('input', function(e) {
    if (e.target.name && e.target.name.includes('[ikon]')) {
        const iconSpan = e.target.closest('.input-group').querySelector('.input-group-text i');
        iconSpan.className = e.target.value || 'bi-circle';
    }
});
</script>
@endpush
@endsection
