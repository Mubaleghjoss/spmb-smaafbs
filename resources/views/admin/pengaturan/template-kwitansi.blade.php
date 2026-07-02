@extends('layouts.admin')

@section('title', 'Template Kwitansi')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="bi bi-receipt me-2"></i>Template Kwitansi Pembayaran</h4>
        <div>
            <a href="{{ route('admin.pengaturan.template-kwitansi.reset') }}" class="btn btn-outline-warning"
               onclick="return confirm('Reset template ke pengaturan default?')">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Default
            </a>
            <a href="{{ route('admin.pengaturan.spmb') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>

    @if(session('sukses'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i>{{ session('sukses') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        {{-- Form Template --}}
        <div class="col-lg-6">
            <form action="{{ route('admin.pengaturan.template-kwitansi.simpan') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                {{-- Informasi Institusi --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="bi bi-building me-2"></i>Informasi Institusi</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Institusi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_institusi" 
                                   value="{{ old('nama_institusi', $template['nama_institusi']) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="alamat" rows="2">{{ old('alamat', $template['alamat']) }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telepon</label>
                            <input type="text" class="form-control" name="telepon" 
                                   value="{{ old('telepon', $template['telepon']) }}">
                        </div>
                    </div>
                </div>

                {{-- Judul dan Teks --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="bi bi-card-text me-2"></i>Judul dan Teks</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Kwitansi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="judul_kwitansi" 
                                   value="{{ old('judul_kwitansi', $template['judul_kwitansi']) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teks Footer</label>
                            <textarea class="form-control" name="teks_footer" rows="2">{{ old('teks_footer', $template['teks_footer']) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Penandatangan --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-pen me-2"></i>Penandatangan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Penandatangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_penandatangan" 
                                   value="{{ old('nama_penandatangan', $template['nama_penandatangan']) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jabatan</label>
                            <input type="text" class="form-control" name="jabatan_penandatangan" 
                                   value="{{ old('jabatan_penandatangan', $template['jabatan_penandatangan']) }}">
                        </div>
                    </div>
                </div>

                {{-- Logo --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="bi bi-image me-2"></i>Logo</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="tampilkan_logo" value="1" 
                                   id="tampilkan_logo" {{ $template['tampilkan_logo'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="tampilkan_logo">Tampilkan Logo</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            @if(!empty($template['logo_path']))
                                <div class="mt-2">
                                    <img src="{{ Storage::url($template['logo_path']) }}" alt="Logo" class="img-thumbnail" style="max-height: 60px;">
                                    <small class="text-muted d-block">Logo saat ini</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Watermark --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-warning">
                        <h6 class="mb-0"><i class="bi bi-droplet me-2"></i>Watermark</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="tampilkan_watermark" value="1" 
                                   id="tampilkan_watermark" {{ $template['tampilkan_watermark'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="tampilkan_watermark">Tampilkan Watermark</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Watermark</label>
                            <input type="file" class="form-control" name="watermark" accept="image/*">
                            @if(!empty($template['watermark_path']))
                                <div class="mt-2">
                                    <img src="{{ Storage::url($template['watermark_path']) }}" alt="Watermark" class="img-thumbnail" style="max-height: 60px;">
                                    <small class="text-muted d-block">Watermark saat ini</small>
                                </div>
                            @endif
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Posisi</label>
                                <select class="form-select" name="watermark_posisi">
                                    <option value="center" {{ $template['watermark_posisi'] == 'center' ? 'selected' : '' }}>Tengah</option>
                                    <option value="top-left" {{ $template['watermark_posisi'] == 'top-left' ? 'selected' : '' }}>Kiri Atas</option>
                                    <option value="top-right" {{ $template['watermark_posisi'] == 'top-right' ? 'selected' : '' }}>Kanan Atas</option>
                                    <option value="bottom-left" {{ $template['watermark_posisi'] == 'bottom-left' ? 'selected' : '' }}>Kiri Bawah</option>
                                    <option value="bottom-right" {{ $template['watermark_posisi'] == 'bottom-right' ? 'selected' : '' }}>Kanan Bawah</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Opacity ({{ $template['watermark_opacity'] * 100 }}%)</label>
                                <input type="range" class="form-range" name="watermark_opacity" 
                                       min="0" max="1" step="0.05" value="{{ $template['watermark_opacity'] }}"
                                       id="watermark_opacity_range">
                                <small class="text-muted" id="opacity_value">{{ $template['watermark_opacity'] * 100 }}%</small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ukuran ({{ $template['watermark_ukuran'] }}%)</label>
                                <input type="range" class="form-range" name="watermark_ukuran" 
                                       min="10" max="100" step="5" value="{{ $template['watermark_ukuran'] }}"
                                       id="watermark_ukuran_range">
                                <small class="text-muted" id="ukuran_value">{{ $template['watermark_ukuran'] }}%</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Stempel --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0"><i class="bi bi-stamp me-2"></i>Stempel</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="tampilkan_stempel" value="1" 
                                   id="tampilkan_stempel" {{ $template['tampilkan_stempel'] ? 'checked' : '' }}>
                            <label class="form-check-label" for="tampilkan_stempel">Tampilkan Stempel</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload Stempel</label>
                            <input type="file" class="form-control" name="stempel" accept="image/*">
                            @if(!empty($template['stempel_path']))
                                <div class="mt-2">
                                    <img src="{{ Storage::url($template['stempel_path']) }}" alt="Stempel" class="img-thumbnail" style="max-height: 60px;">
                                    <small class="text-muted d-block">Stempel saat ini</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save me-2"></i>Simpan Template
                    </button>
                </div>
            </form>
        </div>

        {{-- Preview --}}
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm sticky-top" style="top: 80px;">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="bi bi-eye me-2"></i>Preview Kwitansi</h6>
                </div>
                <div class="card-body p-0">
                    <div class="kwitansi-preview p-4" style="background: #fff; min-height: 500px; position: relative; overflow: hidden;">
                        {{-- Watermark --}}
                        @if($template['tampilkan_watermark'] && !empty($template['watermark_path']))
                        <div class="watermark-preview" style="
                            position: absolute;
                            {{ $template['watermark_posisi'] == 'center' ? 'top: 50%; left: 50%; transform: translate(-50%, -50%);' : '' }}
                            {{ $template['watermark_posisi'] == 'top-left' ? 'top: 20px; left: 20px;' : '' }}
                            {{ $template['watermark_posisi'] == 'top-right' ? 'top: 20px; right: 20px;' : '' }}
                            {{ $template['watermark_posisi'] == 'bottom-left' ? 'bottom: 20px; left: 20px;' : '' }}
                            {{ $template['watermark_posisi'] == 'bottom-right' ? 'bottom: 20px; right: 20px;' : '' }}
                            opacity: {{ $template['watermark_opacity'] }};
                            width: {{ $template['watermark_ukuran'] }}%;
                            z-index: 0;
                        ">
                            <img src="{{ Storage::url($template['watermark_path']) }}" alt="Watermark" style="width: 100%;">
                        </div>
                        @endif

                        <div style="position: relative; z-index: 1;">
                            {{-- Header --}}
                            <div class="text-center mb-4">
                                @if($template['tampilkan_logo'] && !empty($template['logo_path']))
                                <img src="{{ Storage::url($template['logo_path']) }}" alt="Logo" style="max-height: 50px;" class="mb-2">
                                @endif
                                <h5 class="mb-1">{{ $template['nama_institusi'] }}</h5>
                                @if($template['alamat'])
                                <small class="text-muted d-block">{{ $template['alamat'] }}</small>
                                @endif
                                @if($template['telepon'])
                                <small class="text-muted">Telp: {{ $template['telepon'] }}</small>
                                @endif
                            </div>

                            <hr>

                            {{-- Judul --}}
                            <h6 class="text-center mb-4"><strong>{{ $template['judul_kwitansi'] }}</strong></h6>

                            {{-- Detail --}}
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td width="40%">No. Kwitansi</td>
                                    <td>: <strong>KWT/2025/12/0001</strong></td>
                                </tr>
                                <tr>
                                    <td>Tanggal</td>
                                    <td>: {{ now()->format('d F Y') }}</td>
                                </tr>
                                <tr>
                                    <td>Diterima dari</td>
                                    <td>: <strong>Nama Peserta</strong></td>
                                </tr>
                                <tr>
                                    <td>No. Pendaftaran</td>
                                    <td>: SPMB-2025-0001</td>
                                </tr>
                                <tr>
                                    <td>Untuk Pembayaran</td>
                                    <td>: Biaya Formulir SPMB</td>
                                </tr>
                                <tr>
                                    <td>Jumlah</td>
                                    <td>: <strong>Rp {{ number_format($spmb['biaya_formulir'] ?? 0, 0, ',', '.') }}</strong></td>
                                </tr>
                            </table>

                            <hr>

                            {{-- Footer --}}
                            @if($template['teks_footer'])
                            <p class="small text-muted text-center mb-4">{{ $template['teks_footer'] }}</p>
                            @endif

                            {{-- Tanda Tangan --}}
                            <div class="row mt-4">
                                <div class="col-6"></div>
                                <div class="col-6 text-center">
                                    <p class="mb-0">{{ now()->format('d F Y') }}</p>
                                    <div style="height: 60px; position: relative;">
                                        @if($template['tampilkan_stempel'] && !empty($template['stempel_path']))
                                        <img src="{{ Storage::url($template['stempel_path']) }}" alt="Stempel" 
                                             style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); max-height: 50px; opacity: 0.8;">
                                        @endif
                                    </div>
                                    <p class="mb-0"><strong>{{ $template['nama_penandatangan'] }}</strong></p>
                                    <small class="text-muted">{{ $template['jabatan_penandatangan'] }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('watermark_opacity_range').addEventListener('input', function() {
    document.getElementById('opacity_value').textContent = Math.round(this.value * 100) + '%';
});

document.getElementById('watermark_ukuran_range').addEventListener('input', function() {
    document.getElementById('ukuran_value').textContent = this.value + '%';
});
</script>
@endpush
@endsection
