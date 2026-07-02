@extends('layouts.admin')

@section('title', 'Pengaturan Alur SPMB')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Pengaturan Alur SPMB</h1>
            <p class="text-muted mb-0">Atur deskripsi dan detail setiap tahapan SPMB yang ditampilkan di halaman publik</p>
        </div>
        <div>
            <a href="{{ route('alur-spmb') }}" target="_blank" class="btn btn-outline-primary me-2">
                <i class="bi bi-eye"></i> Lihat Halaman
            </a>
            <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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

    <form action="{{ route('admin.pengaturan.alur-spmb.simpan') }}" method="POST">
        @csrf
        
        <div id="tahapan-container">
            @foreach($alurSpmb as $index => $tahap)
            <div class="card mb-3 tahapan-item" data-index="{{ $index }}">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">Tahap {{ $index + 1 }}</span>
                        <span class="tahap-judul-preview">{{ $tahap['judul'] ?? 'Tahapan Baru' }}</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-move-up" title="Pindah ke atas">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-move-down" title="Pindah ke bawah">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tahap" title="Hapus tahapan">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Judul Tahapan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-judul" name="tahapan[{{ $index }}][judul]" 
                                   value="{{ $tahap['judul'] ?? '' }}" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon Bootstrap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-{{ $tahap['icon'] ?? 'circle-fill' }}"></i></span>
                                <input type="text" class="form-control input-icon" name="tahapan[{{ $index }}][icon]" 
                                       value="{{ $tahap['icon'] ?? 'circle-fill' }}" placeholder="person-plus-fill">
                            </div>
                            <small class="text-muted">Lihat: <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="tahapan[{{ $index }}][deskripsi]" rows="2" required>{{ $tahap['deskripsi'] ?? '' }}</textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Detail Poin</label>
                        <div class="detail-container">
                            @foreach(($tahap['detail'] ?? []) as $detailIndex => $detail)
                            <div class="input-group mb-2 detail-item">
                                <span class="input-group-text"><i class="bi bi-check2"></i></span>
                                <input type="text" class="form-control" name="tahapan[{{ $index }}][detail][]" value="{{ $detail }}">
                                <button type="button" class="btn btn-outline-danger btn-hapus-detail">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success btn-tambah-detail">
                            <i class="bi bi-plus"></i> Tambah Poin
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mb-4">
            <button type="button" class="btn btn-success" id="btn-tambah-tahap">
                <i class="bi bi-plus-circle"></i> Tambah Tahapan
            </button>
        </div>

        <div class="card">
            <div class="card-body d-flex justify-content-between">
                <a href="{{ route('admin.pengaturan.alur-spmb.reset') }}" class="btn btn-outline-warning"
                   onclick="return confirm('Reset ke pengaturan default? Semua perubahan akan hilang.')">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset ke Default
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('tahapan-container');
    
    // Update semua index
    function updateIndexes() {
        const items = container.querySelectorAll('.tahapan-item');
        items.forEach((item, index) => {
            item.dataset.index = index;
            item.querySelector('.badge').textContent = 'Tahap ' + (index + 1);
            
            // Update nama input
            item.querySelectorAll('input, textarea').forEach(input => {
                const name = input.name;
                if (name) {
                    input.name = name.replace(/tahapan\[\d+\]/, 'tahapan[' + index + ']');
                }
            });
        });
    }
    
    // Tambah tahapan baru
    document.getElementById('btn-tambah-tahap').addEventListener('click', function() {
        const index = container.querySelectorAll('.tahapan-item').length;
        const html = `
            <div class="card mb-3 tahapan-item" data-index="${index}">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success me-2">Tahap ${index + 1}</span>
                        <span class="tahap-judul-preview">Tahapan Baru</span>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-move-up" title="Pindah ke atas">
                            <i class="bi bi-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-move-down" title="Pindah ke bawah">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-hapus-tahap" title="Hapus tahapan">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Judul Tahapan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-judul" name="tahapan[${index}][judul]" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Icon Bootstrap</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-circle-fill"></i></span>
                                <input type="text" class="form-control input-icon" name="tahapan[${index}][icon]" value="circle-fill" placeholder="person-plus-fill">
                            </div>
                            <small class="text-muted">Lihat: <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a></small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="tahapan[${index}][deskripsi]" rows="2" required></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Detail Poin</label>
                        <div class="detail-container"></div>
                        <button type="button" class="btn btn-sm btn-outline-success btn-tambah-detail">
                            <i class="bi bi-plus"></i> Tambah Poin
                        </button>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    });
    
    // Event delegation untuk tombol-tombol
    container.addEventListener('click', function(e) {
        const target = e.target.closest('button');
        if (!target) return;
        
        const item = target.closest('.tahapan-item');
        
        // Hapus tahapan
        if (target.classList.contains('btn-hapus-tahap')) {
            if (container.querySelectorAll('.tahapan-item').length > 1) {
                if (confirm('Hapus tahapan ini?')) {
                    item.remove();
                    updateIndexes();
                }
            } else {
                alert('Minimal harus ada satu tahapan');
            }
        }
        
        // Pindah ke atas
        if (target.classList.contains('btn-move-up')) {
            const prev = item.previousElementSibling;
            if (prev) {
                container.insertBefore(item, prev);
                updateIndexes();
            }
        }
        
        // Pindah ke bawah
        if (target.classList.contains('btn-move-down')) {
            const next = item.nextElementSibling;
            if (next) {
                container.insertBefore(next, item);
                updateIndexes();
            }
        }
        
        // Tambah detail
        if (target.classList.contains('btn-tambah-detail')) {
            const detailContainer = item.querySelector('.detail-container');
            const index = item.dataset.index;
            const html = `
                <div class="input-group mb-2 detail-item">
                    <span class="input-group-text"><i class="bi bi-check2"></i></span>
                    <input type="text" class="form-control" name="tahapan[${index}][detail][]">
                    <button type="button" class="btn btn-outline-danger btn-hapus-detail">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            `;
            detailContainer.insertAdjacentHTML('beforeend', html);
        }
        
        // Hapus detail
        if (target.classList.contains('btn-hapus-detail')) {
            target.closest('.detail-item').remove();
        }
    });
    
    // Update preview judul
    container.addEventListener('input', function(e) {
        if (e.target.classList.contains('input-judul')) {
            const item = e.target.closest('.tahapan-item');
            const preview = item.querySelector('.tahap-judul-preview');
            preview.textContent = e.target.value || 'Tahapan Baru';
        }
        
        // Update icon preview
        if (e.target.classList.contains('input-icon')) {
            const iconSpan = e.target.closest('.input-group').querySelector('.input-group-text i');
            iconSpan.className = 'bi bi-' + (e.target.value || 'circle-fill');
        }
    });
});
</script>
@endpush
@endsection
