@extends('layouts.admin')

@section('title', 'Pengaturan Ujian')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Pengaturan Ujian</h1>
        <a href="{{ route('admin.pengaturan.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    @if(session('sukses') || session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('sukses') ?? session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <div class="fw-bold mb-1">Pengaturan belum bisa disimpan:</div>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.pengaturan.ujian.simpan') }}">
        @csrf

        <div class="card mb-4 border-primary">
            <div class="card-header bg-primary text-white">
                <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Jadwal Akses Tes Online</h6>
            </div>
            <div class="card-body">
                <div class="alert {{ $aksesUjian['dibuka'] ? 'alert-success' : 'alert-warning' }} mb-4">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-{{ $aksesUjian['dibuka'] ? 'unlock' : 'lock' }} me-2 mt-1"></i>
                        <div>
                            <strong>Status saat ini: {{ $aksesUjian['dibuka'] ? 'Tes Online Dibuka' : 'Tes Online Ditutup' }}</strong>
                            @if(!empty($aksesUjian['alasan']))
                                <div>{{ $aksesUjian['alasan'] }}</div>
                            @elseif(!empty($aksesUjian['mulai_label']) || !empty($aksesUjian['selesai_label']))
                                <div>
                                    @if(!empty($aksesUjian['mulai_label'])) Mulai: {{ $aksesUjian['mulai_label'] }} @endif
                                    @if(!empty($aksesUjian['selesai_label'])) | Tutup: {{ $aksesUjian['selesai_label'] }} @endif
                                </div>
                            @else
                                <div>Tidak ada batas jadwal. Peserta tahap tes dapat membuka tes online.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="ujian_dibuka" value="0">
                    <input type="checkbox" name="ujian_dibuka" value="1" class="form-check-input" id="ujianDibuka"
                           {{ old('ujian_dibuka', $ujian['ujian_dibuka']) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="ujianDibuka">Tes Online Dibuka</label>
                    <div class="form-text">Matikan jika admin ingin menutup semua akses mulai tes, walaupun tanggal sudah masuk.</div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Buka</label>
                        <input type="date" name="ujian_tanggal_buka" class="form-control"
                               value="{{ old('ujian_tanggal_buka', $ujian['ujian_tanggal_buka']) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Waktu Buka</label>
                        <input type="time" name="ujian_waktu_buka" class="form-control"
                               value="{{ old('ujian_waktu_buka', $ujian['ujian_waktu_buka']) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Tutup</label>
                        <input type="date" name="ujian_tanggal_tutup" class="form-control"
                               value="{{ old('ujian_tanggal_tutup', $ujian['ujian_tanggal_tutup']) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Waktu Tutup</label>
                        <input type="time" name="ujian_waktu_tutup" class="form-control"
                               value="{{ old('ujian_waktu_tutup', $ujian['ujian_waktu_tutup']) }}">
                    </div>
                </div>
                <small class="text-muted d-block mt-2">
                    Kosongkan jadwal jika tes online ingin terbuka tanpa batas waktu. Waktu mengikuti zona waktu server/WIB.
                </small>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Pengaturan Default</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Durasi Default (menit)</label>
                            <input type="number" name="durasi_default" class="form-control" 
                                   value="{{ old('durasi_default', $ujian['durasi_default']) }}" min="1" max="300" required>
                            <small class="text-muted">Durasi default untuk tes baru</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nilai Lulus Default</label>
                            <div class="input-group">
                                <input type="number" name="nilai_lulus_default" class="form-control" 
                                       value="{{ old('nilai_lulus_default', $ujian['nilai_lulus_default']) }}" min="0" max="100" step="0.1" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted">Nilai minimum untuk lulus</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Opsi Pengacakan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="acak_soal_default" value="0">
                                <input type="checkbox" name="acak_soal_default" value="1" class="form-check-input" id="acakSoal"
                                       {{ $ujian['acak_soal_default'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="acakSoal">Acak Urutan Soal (Default)</label>
                            </div>
                            <small class="text-muted">Urutan soal diacak untuk setiap peserta</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="acak_jawaban_default" value="0">
                                <input type="checkbox" name="acak_jawaban_default" value="1" class="form-check-input" id="acakJawaban"
                                       {{ $ujian['acak_jawaban_default'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="acakJawaban">Acak Urutan Jawaban (Default)</label>
                            </div>
                            <small class="text-muted">Urutan pilihan jawaban diacak</small>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Tampilan Hasil</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="tampilkan_nilai" value="0">
                                <input type="checkbox" name="tampilkan_nilai" value="1" class="form-check-input" id="tampilkanNilai"
                                       {{ $ujian['tampilkan_nilai'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="tampilkanNilai">Tampilkan Nilai ke Peserta</label>
                            </div>
                            <small class="text-muted">Peserta dapat melihat nilai setelah selesai</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input type="hidden" name="tampilkan_pembahasan" value="0">
                                <input type="checkbox" name="tampilkan_pembahasan" value="1" class="form-check-input" id="tampilkanPembahasan"
                                       {{ $ujian['tampilkan_pembahasan'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="tampilkanPembahasan">Tampilkan Pembahasan</label>
                            </div>
                            <small class="text-muted">Peserta dapat melihat pembahasan soal</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Simpan Pengaturan
            </button>
        </div>
    </form>
</div>
@endsection
