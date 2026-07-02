@extends('layouts.admin')

@section('title', 'Edit Pengguna')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Pengguna</h1>
        <a href="{{ route('admin.pengguna.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.pengguna.update', $pengguna) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" 
                                   value="{{ old('nama', $pengguna->nama) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $pengguna->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Peran <span class="text-danger">*</span></label>
                            <select name="peran" class="form-select @error('peran') is-invalid @enderror" required 
                                    {{ $pengguna->id === auth('pengguna')->id() ? 'disabled' : '' }}>
                                <option value="admin" {{ old('peran', $pengguna->peran) == 'admin' ? 'selected' : '' }}>Admin</option>
                                <option value="operator" {{ old('peran', $pengguna->peran) == 'operator' ? 'selected' : '' }}>Operator</option>
                                <option value="tim_spmb" {{ old('peran', $pengguna->peran) == 'tim_spmb' ? 'selected' : '' }}>Tim SPMB</option>
                            </select>
                            @if($pengguna->id === auth('pengguna')->id())
                                <input type="hidden" name="peran" value="{{ $pengguna->peran }}">
                                <small class="text-muted">Tidak bisa mengubah peran akun sendiri</small>
                            @endif
                            @error('peran')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="aktif" class="form-check-input" id="aktif" value="1"
                                       {{ old('aktif', $pengguna->aktif) ? 'checked' : '' }}
                                       {{ $pengguna->id === auth('pengguna')->id() ? 'disabled' : '' }}>
                                <label class="form-check-label" for="aktif">Akun Aktif</label>
                            </div>
                            @if($pengguna->id === auth('pengguna')->id())
                                <input type="hidden" name="aktif" value="1">
                            @endif
                        </div>

                        {{-- Menu Akses (hanya untuk non-admin) --}}
                        <div class="mb-3" id="menu-akses-section" style="{{ old('peran', $pengguna->peran) === 'admin' ? 'display:none' : '' }}">
                            <label class="form-label">Akses Menu</label>
                            <p class="text-muted small mb-2">Pilih menu yang dapat diakses oleh pengguna ini</p>
                            
                            @php
                                $daftarMenu = \App\Models\Pengguna::daftarMenu();
                                $menuAkses = old('menu_akses', $pengguna->menu_akses ?? \App\Models\Pengguna::menuDefault());
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

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Update
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
    document.querySelector('select[name="peran"]')?.addEventListener('change', function() {
        const menuSection = document.getElementById('menu-akses-section');
        if (this.value === 'admin') {
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
