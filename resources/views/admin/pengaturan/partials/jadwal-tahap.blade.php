@php
    $fieldPrefix = $fieldPrefix ?? 'jadwal';
    $judulJadwal = $judulJadwal ?? 'Jadwal Tahap';
    $deskripsiJadwal = $deskripsiJadwal ?? 'Atur periode akses peserta untuk tahap ini.';
    $warnaJadwal = $warnaJadwal ?? 'primary';
    $pakaiLokasi = $pakaiLokasi ?? false;
    $jadwalAktif = filter_var($jadwalTahap['dibuka'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
@endphp

<div class="card border-0 shadow-sm mb-4 border-start border-4 border-{{ $warnaJadwal }}">
    <div class="card-header bg-white py-3">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h5 class="mb-1"><i class="bi bi-calendar-check me-2 text-{{ $warnaJadwal }}"></i>{{ $judulJadwal }}</h5>
                <p class="text-muted small mb-0">{{ $deskripsiJadwal }}</p>
            </div>
            <span class="badge {{ ($statusJadwal['dibuka'] ?? true) ? 'bg-success' : 'bg-warning text-dark' }} px-3 py-2">
                <i class="bi bi-{{ ($statusJadwal['dibuka'] ?? true) ? 'unlock' : 'lock' }} me-1"></i>
                {{ ($statusJadwal['dibuka'] ?? true) ? 'Sedang Dibuka' : 'Sedang Ditutup' }}
            </span>
        </div>
    </div>
    <div class="card-body">
        @if(!empty($statusJadwal['jadwal_label']) || !empty($statusJadwal['alasan']))
            <div class="alert {{ ($statusJadwal['dibuka'] ?? true) ? 'alert-success' : 'alert-warning' }} py-2 mb-3">
                <i class="bi bi-info-circle me-1"></i>
                {{ $statusJadwal['alasan'] ?? $statusJadwal['jadwal_label'] }}
            </div>
        @endif

        <div class="form-check form-switch mb-3">
            <input type="hidden" name="{{ $fieldPrefix }}[dibuka]" value="0">
            <input class="form-check-input" type="checkbox" name="{{ $fieldPrefix }}[dibuka]" value="1"
                   id="{{ str_replace('_', '-', $fieldPrefix) }}-dibuka"
                   {{ old($fieldPrefix . '.dibuka', $jadwalAktif) ? 'checked' : '' }}>
            <label class="form-check-label fw-semibold" for="{{ str_replace('_', '-', $fieldPrefix) }}-dibuka">
                Izinkan peserta mengakses tahap ini
            </label>
            <div class="form-text">Toggle ini dapat menutup akses segera, terlepas dari tanggal yang diisi.</div>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Tanggal Buka</label>
                <input type="date" name="{{ $fieldPrefix }}[tanggal_buka]" class="form-control"
                       value="{{ old($fieldPrefix . '.tanggal_buka', $jadwalTahap['tanggal_buka'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Buka</label>
                <input type="time" name="{{ $fieldPrefix }}[waktu_mulai]" class="form-control"
                       value="{{ old($fieldPrefix . '.waktu_mulai', $jadwalTahap['waktu_mulai'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal Tutup</label>
                <input type="date" name="{{ $fieldPrefix }}[tanggal_tutup]" class="form-control"
                       value="{{ old($fieldPrefix . '.tanggal_tutup', $jadwalTahap['tanggal_tutup'] ?? '') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Jam Tutup</label>
                <input type="time" name="{{ $fieldPrefix }}[waktu_selesai]" class="form-control"
                       value="{{ old($fieldPrefix . '.waktu_selesai', $jadwalTahap['waktu_selesai'] ?? '') }}">
            </div>
            @if($pakaiLokasi)
                <div class="col-md-5">
                    <label class="form-label">Lokasi</label>
                    <input type="text" name="{{ $fieldPrefix }}[lokasi]" class="form-control"
                           value="{{ old($fieldPrefix . '.lokasi', $jadwalTahap['lokasi'] ?? '') }}"
                           placeholder="Contoh: Aula SMA Al Furqon">
                </div>
            @endif
            <div class="{{ $pakaiLokasi ? 'col-md-7' : 'col-12' }}">
                <label class="form-label">Keterangan untuk Peserta</label>
                <input type="text" name="{{ $fieldPrefix }}[keterangan]" class="form-control"
                       value="{{ old($fieldPrefix . '.keterangan', $jadwalTahap['keterangan'] ?? '') }}"
                       placeholder="Informasi singkat yang perlu diketahui peserta">
            </div>
        </div>
        <div class="form-text mt-2">Kosongkan seluruh tanggal jika tahap ingin dibuka tanpa batas waktu. Zona waktu menggunakan WIB.</div>
    </div>
</div>
