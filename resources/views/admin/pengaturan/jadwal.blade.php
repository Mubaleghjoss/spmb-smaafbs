@extends('layouts.admin')

@section('title', 'Pengaturan Jadwal SPMB')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Pengaturan Jadwal SPMB</h1>
            <p class="text-muted mb-0">Atur jadwal kegiatan SPMB yang ditampilkan di halaman publik</p>
        </div>
        <div>
            <a href="{{ route('jadwal') }}" target="_blank" class="btn btn-outline-primary me-2">
                <i class="bi bi-eye"></i> Lihat Halaman
            </a>
            <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Penjelas mode + tombol ke Alur & Jadwal --}}
    <div class="alert {{ $mode === 'manual' ? 'alert-warning' : 'alert-info' }} d-flex align-items-start gap-2">
        <i class="bi bi-{{ $mode === 'manual' ? 'pencil-square' : 'magic' }} fs-5 mt-1"></i>
        <div class="flex-grow-1">
            @if($mode === 'manual')
                <strong>Mode saat ini: MANUAL.</strong>
                Halaman publik <a href="{{ route('jadwal') }}" target="_blank" class="alert-link">/jadwal</a>
                memakai daftar yang Anda ketik di bawah — <strong>menggantikan</strong> jadwal otomatis dari menu Alur &amp; Jadwal.
            @else
                <strong>Mode saat ini: OTOMATIS.</strong>
                Halaman publik <a href="{{ route('jadwal') }}" target="_blank" class="alert-link">/jadwal</a>
                otomatis mengikuti <strong>Alur &amp; Jadwal</strong> per gelombang (sumber sebenarnya).
                Daftar manual di bawah <strong>diabaikan</strong> selama mode otomatis.
            @endif
        </div>
        <a href="{{ route('admin.alur-jadwal.index') }}" class="btn btn-sm btn-outline-success flex-shrink-0">
            <i class="bi bi-calendar-week me-1"></i>Buka Alur &amp; Jadwal
        </a>
    </div>

    <form action="{{ route('admin.pengaturan.jadwal.simpan') }}" method="POST" id="form-jadwal">
        @csrf
        <input type="hidden" name="jadwal_mode" id="jadwal_mode" value="{{ $mode }}">

        {{-- Pemilih Mode --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-sliders me-1"></i>Sumber Jadwal Halaman Publik</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="w-100 mode-opt {{ $mode === 'otomatis' ? 'mode-active' : '' }}" data-mode="otomatis" style="cursor:pointer;">
                            <div class="border rounded p-3 h-100 {{ $mode === 'otomatis' ? 'border-success bg-success bg-opacity-10' : '' }}">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <input class="form-check-input mt-0 mode-radio" type="radio" name="__mode_ui" value="otomatis" {{ $mode === 'otomatis' ? 'checked' : '' }}>
                                    <span class="fw-bold"><i class="bi bi-magic me-1 text-success"></i>Otomatis (disarankan)</span>
                                </div>
                                <div class="small text-muted">Ikut menu Alur &amp; Jadwal per gelombang. Sekali atur, halaman publik &amp; dashboard peserta sinkron.</div>
                            </div>
                        </label>
                    </div>
                    <div class="col-md-6">
                        <label class="w-100 mode-opt {{ $mode === 'manual' ? 'mode-active' : '' }}" data-mode="manual" style="cursor:pointer;">
                            <div class="border rounded p-3 h-100 {{ $mode === 'manual' ? 'border-warning bg-warning bg-opacity-10' : '' }}">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <input class="form-check-input mt-0 mode-radio" type="radio" name="__mode_ui" value="manual" {{ $mode === 'manual' ? 'checked' : '' }}>
                                    <span class="fw-bold"><i class="bi bi-pencil-square me-1 text-warning"></i>Manual</span>
                                </div>
                                <div class="small text-muted">Ketik sendiri daftar jadwal di bawah. <strong>Menggantikan</strong> jadwal otomatis untuk halaman publik.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pratinjau jadwal OTOMATIS (read-only) — tampil saat mode otomatis --}}
        <div class="card mb-4 border-0 shadow-sm" id="card-otomatis" style="{{ $mode === 'otomatis' ? '' : 'display:none;' }}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-magic me-1"></i>Jadwal Otomatis (dari Alur &amp; Jadwal)
                    @if($gelombangOtomatis)<span class="badge bg-success ms-1">{{ $gelombangOtomatis->nama }}</span>@endif
                </h6>
                <a href="{{ route('admin.alur-jadwal.index') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil me-1"></i>Ubah di Alur &amp; Jadwal</a>
            </div>
            <div class="card-body">
                @if(!empty($jadwalOtomatis))
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Kegiatan</th><th style="width:35%">Tanggal</th><th style="width:120px">Status</th></tr></thead>
                        <tbody>
                            @foreach($jadwalOtomatis as $jo)
                            <tr>
                                <td><i class="bi bi-{{ $jo['icon'] ?? 'calendar' }} me-1 text-muted"></i>{{ $jo['kegiatan'] }}</td>
                                <td class="small text-muted">{{ $jo['tanggal'] }}</td>
                                <td><span class="badge bg-{{ ['dibuka'=>'success','akan_datang'=>'secondary','selesai'=>'dark','persiapan'=>'warning text-dark'][$jo['status']] ?? 'info' }}">{{ $jo['keterangan'] ?? '-' }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>Belum ada jadwal di Alur &amp; Jadwal. Silakan atur di sana.</p>
                @endif
            </div>
        </div>
        
        <div class="card mb-4" id="card-manual" style="{{ $mode === 'manual' ? '' : 'display:none;' }}">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-calendar3"></i> Daftar Jadwal Kegiatan</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="jadwal-table">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Kegiatan</th>
                                <th width="10%">Icon</th>
                                <th width="25%">Tanggal</th>
                                <th width="15%">Status</th>
                                <th width="15%">Keterangan</th>
                                <th width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="jadwal-container">
                            @foreach($jadwal as $index => $item)
                            <tr class="jadwal-item" data-index="{{ $index }}">
                                <td class="text-center align-middle nomor-urut">{{ $index + 1 }}</td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="jadwal[{{ $index }}][kegiatan]" 
                                           value="{{ $item['kegiatan'] ?? '' }}" required>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text"><i class="bi bi-{{ $item['icon'] ?? 'calendar' }}"></i></span>
                                        <input type="text" class="form-control input-icon" 
                                               name="jadwal[{{ $index }}][icon]" 
                                               value="{{ $item['icon'] ?? 'calendar' }}">
                                    </div>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="jadwal[{{ $index }}][tanggal]" 
                                           value="{{ $item['tanggal'] ?? '' }}" required>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm" name="jadwal[{{ $index }}][status]" required>
                                        <option value="dibuka" {{ ($item['status'] ?? '') == 'dibuka' ? 'selected' : '' }}>Dibuka</option>
                                        <option value="akan_datang" {{ ($item['status'] ?? '') == 'akan_datang' ? 'selected' : '' }}>Akan Datang</option>
                                        <option value="selesai" {{ ($item['status'] ?? '') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                                        <option value="info" {{ ($item['status'] ?? '') == 'info' ? 'selected' : '' }}>Info</option>
                                        <option value="persiapan" {{ ($item['status'] ?? '') == 'persiapan' ? 'selected' : '' }}>Persiapan</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm" 
                                           name="jadwal[{{ $index }}][keterangan]" 
                                           value="{{ $item['keterangan'] ?? '' }}">
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-jadwal" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <button type="button" class="btn btn-success btn-sm" id="btn-tambah-jadwal">
                    <i class="bi bi-plus-circle"></i> Tambah Jadwal
                </button>
            </div>
        </div>

        <div class="card mb-4" id="card-catatan" style="{{ $mode === 'manual' ? '' : 'display:none;' }}">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Catatan</h6>
            </div>
            <div class="card-body">
                <textarea class="form-control" name="catatan" rows="2" 
                          placeholder="Catatan yang ditampilkan di bawah tabel jadwal">{{ $catatan }}</textarea>
                <small class="text-muted">Catatan ini akan ditampilkan di halaman jadwal publik</small>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex justify-content-between">
                <a href="{{ route('admin.pengaturan.jadwal.reset') }}" class="btn btn-outline-warning"
                   onclick="return confirm('Reset daftar jadwal manual ke pengaturan default? Semua perubahan manual akan hilang.')">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset ke Default
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Legend Status -->
<div class="container-fluid mt-4">
    <div class="card">
        <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-question-circle"></i> Keterangan Status</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <span class="badge bg-success me-2">Dibuka</span> Kegiatan sedang berlangsung
                </div>
                <div class="col-md-4 mb-2">
                    <span class="badge bg-secondary me-2">Akan Datang</span> Kegiatan belum dimulai
                </div>
                <div class="col-md-4 mb-2">
                    <span class="badge bg-dark me-2">Selesai</span> Kegiatan sudah selesai
                </div>
                <div class="col-md-4 mb-2">
                    <span class="badge bg-info me-2">Info</span> Informasi umum
                </div>
                <div class="col-md-4 mb-2">
                    <span class="badge bg-warning text-dark me-2">Persiapan</span> Tahap persiapan
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('jadwal-container');

    // === Toggle mode Otomatis / Manual ===
    const hiddenMode = document.getElementById('jadwal_mode');
    const cardOtomatis = document.getElementById('card-otomatis');
    const cardManual = document.getElementById('card-manual');
    const cardCatatan = document.getElementById('card-catatan');
    const initialMode = hiddenMode ? hiddenMode.value : 'otomatis';

    function paintModeCards(mode) {
        document.querySelectorAll('.mode-opt').forEach(opt => {
            const box = opt.querySelector('.border');
            const isActive = opt.dataset.mode === mode;
            box.classList.remove('border-success','bg-success','border-warning','bg-warning','bg-opacity-10');
            if (isActive) {
                if (mode === 'otomatis') box.classList.add('border-success','bg-success','bg-opacity-10');
                else box.classList.add('border-warning','bg-warning','bg-opacity-10');
            }
        });
        if (cardOtomatis) cardOtomatis.style.display = (mode === 'otomatis') ? '' : 'none';
        if (cardManual) cardManual.style.display = (mode === 'manual') ? '' : 'none';
        if (cardCatatan) cardCatatan.style.display = (mode === 'manual') ? '' : 'none';
    }

    document.querySelectorAll('.mode-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const chosen = this.value;
            // Konfirmasi hanya saat BERALIH ke manual dari otomatis
            if (chosen === 'manual' && initialMode !== 'manual') {
                const ok = confirm(
                    'Beralih ke MODE MANUAL?\n\n' +
                    'Halaman publik /jadwal akan MENGGANTIKAN jadwal otomatis dari menu ' +
                    '"Alur & Jadwal" dengan daftar manual yang Anda ketik di sini.\n\n' +
                    'Jadwal otomatis per gelombang tetap tersimpan, tapi tidak lagi tampil ' +
                    'di halaman publik sampai Anda kembali ke mode Otomatis.\n\nLanjutkan?'
                );
                if (!ok) {
                    // batalkan: kembalikan pilihan ke otomatis
                    const auto = document.querySelector('.mode-radio[value="otomatis"]');
                    if (auto) auto.checked = true;
                    paintModeCards('otomatis');
                    if (hiddenMode) hiddenMode.value = 'otomatis';
                    return;
                }
            }
            if (hiddenMode) hiddenMode.value = chosen;
            paintModeCards(chosen);
        });
    });

    // Update semua index dan nomor urut
    function updateIndexes() {
        const items = container.querySelectorAll('.jadwal-item');
        items.forEach((item, index) => {
            item.dataset.index = index;
            item.querySelector('.nomor-urut').textContent = index + 1;
            
            // Update nama input
            item.querySelectorAll('input, select').forEach(input => {
                const name = input.name;
                if (name) {
                    input.name = name.replace(/jadwal\[\d+\]/, 'jadwal[' + index + ']');
                }
            });
        });
    }
    
    // Tambah jadwal baru
    document.getElementById('btn-tambah-jadwal').addEventListener('click', function() {
        const index = container.querySelectorAll('.jadwal-item').length;
        const html = `
            <tr class="jadwal-item" data-index="${index}">
                <td class="text-center align-middle nomor-urut">${index + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="jadwal[${index}][kegiatan]" required>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="bi bi-calendar"></i></span>
                        <input type="text" class="form-control input-icon" 
                               name="jadwal[${index}][icon]" value="calendar">
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="jadwal[${index}][tanggal]" required>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="jadwal[${index}][status]" required>
                        <option value="dibuka">Dibuka</option>
                        <option value="akan_datang" selected>Akan Datang</option>
                        <option value="selesai">Selesai</option>
                        <option value="info">Info</option>
                        <option value="persiapan">Persiapan</option>
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="jadwal[${index}][keterangan]">
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-jadwal" title="Hapus">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
    
    // Event delegation untuk tombol hapus
    container.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (!target) return;
        
        if (target.classList.contains('btn-hapus-jadwal')) {
            if (container.querySelectorAll('.jadwal-item').length > 1) {
                if (confirm('Hapus jadwal ini?')) {
                    target.closest('.jadwal-item').remove();
                    updateIndexes();
                }
            } else {
                alert('Minimal harus ada satu jadwal');
            }
        }
    });
    
    // Update icon preview
    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-icon')) {
            const iconSpan = e.target.closest('.input-group').querySelector('.input-group-text i');
            iconSpan.className = 'bi bi-' + (e.target.value || 'calendar');
        }
    });
});
</script>
@endpush
@endsection
