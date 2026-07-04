@php
    $pesertaKategori = $peserta ?? null;
    $tahunTerpilih = (string) old(
        'tahun_ajaran_id',
        $pesertaKategori?->tahun_ajaran_id ?? $kategoriDefault['tahun_ajaran_id'] ?? ''
    );
    $gelombangTerpilih = (string) old(
        'gelombang_pendaftaran_id',
        $pesertaKategori?->gelombang_pendaftaran_id ?? $kategoriDefault['gelombang_pendaftaran_id'] ?? ''
    );
    $jenisTerpilih = old(
        'jenis_pendaftaran',
        $pesertaKategori?->jenis_pendaftaran ?? $kategoriDefault['jenis_pendaftaran'] ?? 'siswa_baru'
    );
    $kelasTerpilih = (string) old(
        'kelas_tujuan',
        $pesertaKategori?->kelas_tujuan ?? $kategoriDefault['kelas_tujuan'] ?? 10
    );
@endphp

<div class="border-top pt-3 mt-3"
     x-data="{ jenis: @js($jenisTerpilih), kelas: @js($kelasTerpilih) }">
    <h6 class="mb-3"><i class="bi bi-tags me-1"></i>Kategori Pendaftaran</h6>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Tahun Ajaran <span class="text-danger">*</span></label>
            <select name="tahun_ajaran_id"
                    class="form-select @error('tahun_ajaran_id') is-invalid @enderror"
                    onchange="filterGelombangPendaftaran(this, 'gelombangPendaftaranSelect')"
                    required>
                <option value="">-- Pilih Tahun Ajaran --</option>
                @foreach($tahunAjaran as $tahun)
                    <option value="{{ $tahun->id }}" {{ $tahunTerpilih === (string) $tahun->id ? 'selected' : '' }}>
                        {{ $tahun->nama }}{{ $tahun->aktif ? '' : ' (Nonaktif)' }}
                    </option>
                @endforeach
            </select>
            @error('tahun_ajaran_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Gelombang <span class="text-danger">*</span></label>
            <select name="gelombang_pendaftaran_id"
                    id="gelombangPendaftaranSelect"
                    class="form-select @error('gelombang_pendaftaran_id') is-invalid @enderror"
                    data-selected="{{ $gelombangTerpilih }}"
                    required>
                <option value="">-- Pilih Gelombang --</option>
                @foreach($gelombangPendaftaran as $gelombang)
                    <option value="{{ $gelombang->id }}"
                            data-tahun="{{ $gelombang->tahun_ajaran_id }}"
                            {{ $gelombangTerpilih === (string) $gelombang->id ? 'selected' : '' }}>
                        {{ $gelombang->nama }}{{ $gelombang->aktif ? '' : ' (Nonaktif)' }}
                    </option>
                @endforeach
            </select>
            @error('gelombang_pendaftaran_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Jenis Pendaftaran <span class="text-danger">*</span></label>
            <select name="jenis_pendaftaran"
                    class="form-select @error('jenis_pendaftaran') is-invalid @enderror"
                    x-model="jenis"
                    @change="if (jenis === 'siswa_baru') kelas = '10'"
                    required>
                <option value="siswa_baru">Siswa Baru</option>
                <option value="pindahan">Pindahan</option>
            </select>
            @error('jenis_pendaftaran')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Kelas Tujuan <span class="text-danger">*</span></label>
            <select name="kelas_tujuan"
                    class="form-select @error('kelas_tujuan') is-invalid @enderror"
                    x-model="kelas"
                    :disabled="jenis === 'siswa_baru'"
                    required>
                <option value="10">Kelas 10</option>
                <option value="11">Kelas 11</option>
            </select>
            <input type="hidden" name="kelas_tujuan" value="10"
                   :disabled="jenis !== 'siswa_baru'">
            @error('kelas_tujuan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
function filterGelombangPendaftaran(tahunSelect, targetId) {
    const target = document.getElementById(targetId);
    if (!target) return;

    const tahunId = tahunSelect.value;
    const selected = target.dataset.selected;
    let selectedStillVisible = false;

    Array.from(target.options).forEach(option => {
        if (!option.value) return;
        option.hidden = option.dataset.tahun !== tahunId;
        option.disabled = option.hidden;
        if (!option.hidden && option.value === selected) {
            selectedStillVisible = true;
        }
    });

    if (!selectedStillVisible || target.selectedOptions[0]?.disabled) {
        target.value = '';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const target = document.getElementById('gelombangPendaftaranSelect');
    const tahun = document.querySelector('select[name="tahun_ajaran_id"]');
    if (target && tahun) {
        filterGelombangPendaftaran(tahun, target.id);
    }
});
</script>
@endpush
@endonce
