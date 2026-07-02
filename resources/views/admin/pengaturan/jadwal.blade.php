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

    <form action="{{ route('admin.pengaturan.jadwal.simpan') }}" method="POST">
        @csrf
        
        <div class="card mb-4">
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

        <div class="card mb-4">
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
