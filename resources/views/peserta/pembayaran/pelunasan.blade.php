@extends('layouts.peserta')

@section('title', 'Upload Bukti Pelunasan')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Pembayaran Tahap Pertama</h5>
                </div>
                <div class="card-body">
                    @if(($pembayaran ?? null)?->status === \App\Enums\StatusPembayaran::DITOLAK->value)
                        <div class="alert alert-danger mb-4">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Bukti pembayaran sebelumnya ditolak.
                            @if($pembayaran->catatan)
                                <div class="small mt-1">{{ $pembayaran->catatan }}</div>
                            @endif
                            Silakan upload bukti baru.
                        </div>
                    @endif

                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Informasi Pembayaran</h6>
                        <hr>
                        <p class="mb-1">Selamat! Anda telah lulus seleksi dan wawancara.</p>
                        <p class="mb-1">Silakan lakukan pembayaran tahap pertama sesuai ketentuan.</p>
                        <hr>
                        <p class="mb-1"><strong>Bank:</strong> {{ $spmb['rekening_bank'] ?? 'BSI' }}</p>
                        <p class="mb-1"><strong>No. Rekening:</strong> <code class="fs-5">{{ $spmb['nomor_rekening'] ?? '-' }}</code></p>
                        <p class="mb-1"><strong>Atas Nama:</strong> {{ $spmb['nama_rekening'] ?? '-' }}</p>
                        @if(!empty($spmb['biaya_pelunasan']))
                        <p class="mb-0"><strong>Nominal:</strong> <span class="text-success fw-bold">Rp {{ number_format($spmb['biaya_pelunasan'], 0, ',', '.') }}</span></p>
                        @endif
                    </div>

                    <form action="{{ route('peserta.pembayaran.simpan-pelunasan') }}" method="POST" enctype="multipart/form-data" x-data="uploadForm()">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Nominal Pembayaran <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('nominal') is-invalid @enderror" 
                                       name="nominal" value="{{ old('nominal') }}" required>
                            </div>
                            @error('nominal')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Upload Bukti Transfer <span class="text-danger">*</span></label>
                            
                            {{-- Pilihan metode upload --}}
                            <div class="d-flex gap-2 mb-2">
                                <button type="button" class="btn btn-outline-primary flex-fill" @click="openFileUpload()">
                                    <i class="bi bi-folder2-open me-1"></i>Pilih File
                                </button>
                                <button type="button" class="btn btn-outline-success flex-fill" @click="openCamera()" data-bs-toggle="modal" data-bs-target="#modalCamera">
                                    <i class="bi bi-camera me-1"></i>Ambil Foto
                                </button>
                            </div>
                            
                            {{-- Input file tersembunyi untuk upload biasa --}}
                            <input type="file" class="d-none" id="fileInput"
                                   accept="image/*" @change="previewImage">
                            
                            {{-- Input file yang akan dikirim ke server --}}
                            <input type="file" class="d-none @error('bukti') is-invalid @enderror" 
                                   name="bukti" x-ref="buktiInput" accept="image/*" :required="!hasFile">
                            
                            @error('bukti')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Format: JPG, PNG. Maksimal 2MB</div>
                        </div>
                        
                        {{-- Preview gambar --}}
                        <div x-show="preview" class="mb-3">
                            <label class="form-label">Preview</label>
                            <div class="position-relative d-inline-block w-100">
                                <img :src="preview" class="img-fluid rounded border" style="max-height: 300px;">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" @click="clearPreview()">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Gambar siap diupload</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success w-100" :disabled="loading || !hasFile">
                            <span x-show="!loading"><i class="bi bi-upload me-2"></i>Upload Bukti</span>
                            <span x-show="loading"><span class="spinner-border spinner-border-sm me-2"></span>Mengupload...</span>
                        </button>
                    </form>
                    
                    {{-- Tombol Bantuan WhatsApp --}}
                    <div class="mt-4 pt-3 border-top">
                        <p class="text-muted small mb-2">
                            <i class="bi bi-question-circle me-1"></i>Mengalami kendala saat upload?
                        </p>
                        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalBantuanWa">
                            <i class="bi bi-whatsapp me-1"></i>Minta Bantuan via WhatsApp
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('peserta.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Modal Kamera --}}
<div class="modal fade" id="modalCamera" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-camera me-2"></i>Ambil Foto Bukti Transfer</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="stopCamera()"></button>
            </div>
            <div class="modal-body p-0">
                <div id="cameraContainer" class="position-relative bg-dark" style="min-height: 300px;">
                    <video id="cameraVideo" autoplay playsinline class="w-100" style="max-height: 60vh;"></video>
                    <canvas id="cameraCanvas" class="d-none"></canvas>
                    <div id="cameraLoading" class="position-absolute top-50 start-50 translate-middle text-white text-center">
                        <div class="spinner-border mb-2" role="status"></div>
                        <p class="mb-0">Mengakses kamera...</p>
                    </div>
                    <div id="cameraError" class="position-absolute top-50 start-50 translate-middle text-white text-center d-none">
                        <i class="bi bi-camera-video-off" style="font-size: 3rem;"></i>
                        <p class="mb-0 mt-2">Tidak dapat mengakses kamera.<br>Pastikan izin kamera sudah diberikan.</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="stopCamera()">
                    <i class="bi bi-x-lg me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-success btn-lg" id="btnCapture" onclick="capturePhoto()">
                    <i class="bi bi-camera me-1"></i>Ambil Foto
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Bantuan WhatsApp --}}
<div class="modal fade" id="modalBantuanWa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-whatsapp me-2"></i>Hubungi Tim SPMB</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Pilih salah satu kontak Tim SPMB untuk meminta bantuan:</p>
                
                @php
                    $kontakTim = app(\App\Services\PengaturanService::class)->ambilKontakTimSpmb();
                    $pesanBantuan = "Assalamu'alaikum, saya *{$peserta->nama}* dengan nomor pendaftaran *{$peserta->nomor_pendaftaran}*.\n\nSaya mengalami kendala saat mengupload bukti pembayaran pelunasan di website SPMB. Apakah bisa dibantu untuk mengirimkan bukti pembayaran melalui WhatsApp ini?\n\nTerima kasih.";
                @endphp
                
                @if(count($kontakTim) > 0)
                <div class="list-group">
                    @foreach($kontakTim as $kontak)
                    @if(!empty($kontak['whatsapp']))
                    <a href="https://wa.me/62{{ ltrim($kontak['whatsapp'], '0') }}?text={{ urlencode($pesanBantuan) }}" 
                       target="_blank" 
                       class="list-group-item list-group-item-action d-flex align-items-center">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                            <i class="bi bi-whatsapp"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $kontak['nama'] ?: 'Tim SPMB' }}</h6>
                            <small class="text-muted">+62{{ $kontak['whatsapp'] }}</small>
                        </div>
                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                    </a>
                    @endif
                    @endforeach
                </div>
                @else
                <div class="text-center text-muted py-4">
                    <i class="bi bi-person-x" style="font-size: 2rem;"></i>
                    <p class="mb-0 mt-2">Belum ada kontak Tim SPMB yang tersedia.</p>
                </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let cameraStream = null;
let alpineFormInstance = null;

function uploadForm() {
    return {
        preview: null,
        loading: false,
        hasFile: false,
        selectedFile: null,
        
        init() {
            alpineFormInstance = this;
        },
        
        openFileUpload() {
            document.getElementById('fileInput').click();
        },
        
        openCamera() {
            startCamera();
        },
        
        previewImage(e) {
            const file = e.target.files[0];
            if (file) {
                this.setFile(file);
            }
        },
        
        setFile(file) {
            // Validasi ukuran file (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file terlalu besar. Maksimal 2MB.');
                return false;
            }
            
            // Validasi tipe file
            if (!file.type.startsWith('image/')) {
                alert('File harus berupa gambar (JPG, PNG).');
                return false;
            }
            
            this.preview = URL.createObjectURL(file);
            this.hasFile = true;
            this.selectedFile = file;
            
            // Transfer file ke input yang akan dikirim
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            this.$refs.buktiInput.files = dataTransfer.files;
            return true;
        },
        
        clearPreview() {
            this.preview = null;
            this.hasFile = false;
            this.selectedFile = null;
            this.$refs.buktiInput.value = '';
            document.getElementById('fileInput').value = '';
        }
    }
}

// Fungsi kamera
async function startCamera() {
    const video = document.getElementById('cameraVideo');
    const loading = document.getElementById('cameraLoading');
    const error = document.getElementById('cameraError');
    const btnCapture = document.getElementById('btnCapture');
    
    loading.classList.remove('d-none');
    error.classList.add('d-none');
    btnCapture.disabled = true;
    
    try {
        // Coba kamera belakang dulu (untuk HP), fallback ke kamera depan
        const constraints = {
            video: {
                facingMode: { ideal: 'environment' },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            }
        };
        
        cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = cameraStream;
        
        video.onloadedmetadata = () => {
            loading.classList.add('d-none');
            btnCapture.disabled = false;
        };
    } catch (err) {
        console.error('Error accessing camera:', err);
        loading.classList.add('d-none');
        error.classList.remove('d-none');
    }
}

function stopCamera() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    const video = document.getElementById('cameraVideo');
    if (video) {
        video.srcObject = null;
    }
}

function capturePhoto() {
    const video = document.getElementById('cameraVideo');
    const canvas = document.getElementById('cameraCanvas');
    const ctx = canvas.getContext('2d');
    
    // Set canvas size sesuai video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    
    // Gambar frame video ke canvas
    ctx.drawImage(video, 0, 0);
    
    // Convert canvas ke blob
    canvas.toBlob(function(blob) {
        // Buat file dari blob
        const file = new File([blob], 'bukti-transfer-' + Date.now() + '.jpg', { type: 'image/jpeg' });
        
        // Set file ke form
        if (alpineFormInstance && alpineFormInstance.setFile(file)) {
            // Tutup modal dan stop kamera
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCamera'));
            modal.hide();
            stopCamera();
        }
    }, 'image/jpeg', 0.85);
}

// Stop kamera saat modal ditutup
document.getElementById('modalCamera')?.addEventListener('hidden.bs.modal', function() {
    stopCamera();
});

// Start kamera saat modal dibuka
document.getElementById('modalCamera')?.addEventListener('shown.bs.modal', function() {
    startCamera();
});
</script>
@endpush
