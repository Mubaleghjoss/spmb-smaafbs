@extends('layouts.admin')

@section('title', 'Tambah Pengguna')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Tambah Pengguna</h1>
        <a href="{{ route('admin.pengguna.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.pengguna.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" 
                                   value="{{ old('nama') }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Peran <span class="text-danger">*</span></label>
                            <select name="peran" id="peran" class="form-select @error('peran') is-invalid @enderror" required>
                                <option value="">Pilih Peran</option>
                                <option value="admin" {{ old('peran') == 'admin' ? 'selected' : '' }}>Admin - Akses penuh ke semua fitur</option>
                                <option value="operator" {{ old('peran') == 'operator' ? 'selected' : '' }}>Operator - Akses terbatas</option>
                                <option value="tim_spmb" {{ old('peran') == 'tim_spmb' ? 'selected' : '' }}>Tim SPMB - Verifikasi, peserta, hasil ujian</option>
                            </select>
                            @error('peran')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Menu Akses (hanya untuk non-admin) --}}
                        <div class="mb-3" id="menu-akses-section" style="{{ old('peran') === 'admin' || !old('peran') ? 'display:none' : '' }}">
                            <label class="form-label">Akses Menu</label>
                            <p class="text-muted small mb-2">Pilih menu yang dapat diakses oleh pengguna ini</p>
                            
                            @php
                                $daftarMenu = \App\Models\Pengguna::daftarMenu();
                                $menuDefault = \App\Models\Pengguna::menuDefault();
                                $menuAkses = old('menu_akses', $menuDefault);
                            @endphp
                            
                            <div class="row">
                                @foreach($daftarMenu as $key => $menu)
                                <div class="col-md-6 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" name="menu_akses[]" class="form-check-input" 
                                               id="menu_{{ $key }}" value="{{ $key }}"
                                               {{ in_array($key, $menuAkses ?? []) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="menu_{{ $key }}">
                                            <i class="bi {{ $menu['icon'] }} me-1"></i> {{ $menu['label'] }}
                                        </label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="pilihSemuaMenu()">Pilih Semua</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="hapusSemuaMenu()">Hapus Semua</button>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="menuDefault()">Default</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>

                        <div class="alert alert-info">
                            <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Keterangan Peran:</h6>
                            <ul class="mb-0 small">
                                <li><strong>Admin:</strong> Akses penuh ke semua fitur sistem</li>
                                <li><strong>Operator:</strong> Akses terbatas untuk operasional</li>
                                <li><strong>Tim SPMB:</strong> Hanya bisa verifikasi peserta, tambah peserta baru, dan lihat hasil ujian</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Simpan
                            </button>
                            <a href="{{ route('admin.pengguna.index') }}" class="btn btn-secondary">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Toggle menu akses section berdasarkan peran
    document.getElementById('peran')?.addEventListener('change', function() {
        const menuSection = document.getElementById('menu-akses-section');
        if (this.value === 'admin' || this.value === '') {
            menuSection.style.display = 'none';
        } else {
            menuSection.style.display = 'block';
        }
    });
    
    function pilihSemuaMenu() {
        document.querySelectorAll('input[name="menu_akses[]"]').forEach(cb => cb.checked = true);
    }
    
    function hapusSemuaMenu() {
        document.querySelectorAll('input[name="menu_akses[]"]').forEach(cb => cb.checked = false);
    }
    
    function menuDefault() {
        const defaultMenus = ['peserta', 'verifikasi', 'monitoring', 'hasil'];
        document.querySelectorAll('input[name="menu_akses[]"]').forEach(cb => {
            cb.checked = defaultMenus.includes(cb.value);
        });
    }
</script>
@endpush
