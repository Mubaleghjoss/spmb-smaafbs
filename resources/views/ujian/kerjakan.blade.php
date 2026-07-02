@extends('layouts.ujian')

@section('title', 'Ujian - ' . $tes->nama)

@section('content')
<div class="ujian-container" x-data="ujianApp()">
    <!-- Header dengan Timer -->
    <div class="ujian-header bg-dark text-white py-2 px-3 sticky-top">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>{{ $tes->nama }}</strong>
                <span class="ms-3 badge bg-secondary">Soal {{ $dataSoal['nomor'] }} / {{ $dataSoal['total_soal'] }}</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="timer" :class="{ 'text-danger': waktuTersisa < 300 }">
                    <i class="bi bi-clock me-1"></i>
                    <span x-text="formatWaktu(waktuTersisa)">{{ gmdate('H:i:s', $statistik['waktu_tersisa']) }}</span>
                </div>
                <button type="button" class="btn btn-outline-warning btn-sm" @click="tampilKembali()">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </button>
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalSelesai">
                    <i class="bi bi-check-circle me-1"></i> Selesai
                </button>
            </div>
        </div>
    </div>

    <div class="container-fluid py-3">
        <div class="row">
            <!-- Konten Soal -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body">
                        <!-- Pertanyaan -->
                        <div class="soal-pertanyaan mb-4">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-primary">Soal {{ $dataSoal['nomor'] }}</span>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="ragu" 
                                           x-model="ragu" @change="simpanJawaban()">
                                    <label class="form-check-label text-warning" for="ragu">
                                        <i class="bi bi-flag-fill"></i> Ragu-ragu
                                    </label>
                                </div>
                            </div>
                            <div class="pertanyaan-text">
                                {!! $dataSoal['soal']->pertanyaan !!}
                            </div>
                            @if($dataSoal['soal']->media)
                                <div class="mt-3">
                                    <img src="{{ asset('storage/' . $dataSoal['soal']->media) }}" 
                                         class="img-fluid rounded" alt="Media Soal" style="max-height: 300px;">
                                </div>
                            @endif
                        </div>

                        <!-- Pilihan Jawaban -->
                        <div class="soal-jawaban">
                            @if($dataSoal['soal']->tipe === 'pilihan_ganda' || $dataSoal['soal']->tipe === 'benar_salah')
                                @foreach($dataSoal['jawaban'] as $index => $jawaban)
                                    <div class="form-check mb-3 p-3 border rounded" 
                                         :class="{ 'border-primary bg-light': jawabanId == {{ $jawaban->id }} }">
                                        <input class="form-check-input" type="radio" 
                                               name="jawaban" id="jawaban{{ $jawaban->id }}"
                                               value="{{ $jawaban->id }}"
                                               x-model="jawabanId"
                                               @change="simpanJawaban()">
                                        <label class="form-check-label w-100" for="jawaban{{ $jawaban->id }}">
                                            <strong>{{ chr(65 + $index) }}.</strong> {!! $jawaban->isi_jawaban !!}
                                        </label>
                                    </div>
                                @endforeach
                            @elseif($dataSoal['soal']->tipe === 'jawaban_ganda')
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-info-circle"></i> Pilih semua jawaban yang benar
                                </p>
                                @foreach($dataSoal['jawaban'] as $index => $jawaban)
                                    <div class="form-check mb-3 p-3 border rounded"
                                         :class="{ 'border-primary bg-light': jawabanGanda.includes({{ $jawaban->id }}) }">
                                        <input class="form-check-input" type="checkbox" 
                                               id="jawaban{{ $jawaban->id }}"
                                               value="{{ $jawaban->id }}"
                                               x-model="jawabanGanda"
                                               @change="simpanJawaban()">
                                        <label class="form-check-label w-100" for="jawaban{{ $jawaban->id }}">
                                            <strong>{{ chr(65 + $index) }}.</strong> {!! $jawaban->isi_jawaban !!}
                                        </label>
                                    </div>
                                @endforeach
                            @elseif($dataSoal['soal']->tipe === 'esai')
                                <div class="mb-3">
                                    <label class="form-label">Jawaban Anda:</label>
                                    <textarea class="form-control" rows="6" 
                                              x-model="jawabanEsai"
                                              @blur="simpanJawaban()"
                                              placeholder="Tulis jawaban Anda di sini..."></textarea>
                                </div>
                            @endif
                        </div>

                        <!-- Navigasi Soal -->
                        <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                            @if($dataSoal['nomor'] > 1)
                                <a href="{{ route('ujian.kerjakan', ['sesi' => $sesi, 'nomor' => $dataSoal['nomor'] - 1]) }}" 
                                   class="btn btn-outline-secondary">
                                    <i class="bi bi-chevron-left"></i> Sebelumnya
                                </a>
                            @else
                                <span></span>
                            @endif

                            <!-- Tombol Selesai: muncul jika semua soal sudah dijawab -->
                            <button type="button" class="btn btn-success" 
                                    data-bs-toggle="modal" data-bs-target="#modalSelesai"
                                    x-show="statistik.belumDijawab === 0"
                                    x-cloak>
                                <i class="bi bi-check-circle"></i> Selesai
                            </button>
                            
                            <!-- Tombol Selanjutnya: muncul jika masih ada soal belum dijawab DAN bukan soal terakhir -->
                            @if($dataSoal['nomor'] < $dataSoal['total_soal'])
                                <a href="{{ route('ujian.kerjakan', ['sesi' => $sesi, 'nomor' => $dataSoal['nomor'] + 1]) }}" 
                                   class="btn btn-primary"
                                   x-show="statistik.belumDijawab > 0"
                                   x-cloak>
                                    Selanjutnya <i class="bi bi-chevron-right"></i>
                                </a>
                            @else
                                <!-- Di soal terakhir, jika masih ada belum dijawab, tampilkan tombol Selesai juga -->
                                <button type="button" class="btn btn-warning" 
                                        data-bs-toggle="modal" data-bs-target="#modalSelesai"
                                        x-show="statistik.belumDijawab > 0"
                                        x-cloak>
                                    <i class="bi bi-check-circle"></i> Selesai
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigasi -->
            <div class="col-lg-3">
                <div class="card sticky-top" style="top: 70px;">
                    <div class="card-header">
                        <h6 class="mb-0">Navigasi Soal</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($ringkasan as $index => $item)
                                <a href="{{ route('ujian.kerjakan', ['sesi' => $sesi, 'nomor' => $item['nomor']]) }}"
                                   :id="'nav-soal-{{ $item['nomor'] }}'"
                                   :class="getNavClass({{ $item['nomor'] }}, {{ $item['nomor'] == $dataSoal['nomor'] ? 'true' : 'false' }})"
                                   class="btn btn-sm"
                                   style="width: 40px; height: 40px;">
                                    {{ $item['nomor'] }}
                                </a>
                            @endforeach
                        </div>

                        <hr>

                        <div class="small">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-success me-2">&nbsp;</span> Sudah dijawab
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-warning me-2">&nbsp;</span> Ragu-ragu
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-outline-secondary border me-2">&nbsp;</span> Belum dijawab
                            </div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <div class="mb-2">
                                <strong x-text="statistik.dijawab">{{ $statistik['dijawab'] }}</strong> / {{ $statistik['total_soal'] }} dijawab
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" :style="'width: ' + statistik.persentase + '%'"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Selesai -->
    <div class="modal fade" id="modalSelesai" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Selesai</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menyelesaikan ujian?</p>
                    <div class="alert alert-info">
                        <strong>Ringkasan:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Soal dijawab: <span x-text="statistik.dijawab">{{ $statistik['dijawab'] }}</span> / {{ $statistik['total_soal'] }}</li>
                            <li>Soal ragu-ragu: <span x-text="statistik.ragu">{{ $statistik['ragu'] }}</span></li>
                            <li>Belum dijawab: <span x-text="statistik.belumDijawab">{{ $statistik['belum_dijawab'] }}</span></li>
                        </ul>
                    </div>
                    <div class="alert alert-warning" x-show="statistik.belumDijawab > 0">
                        <i class="bi bi-exclamation-triangle"></i>
                        Masih ada <span x-text="statistik.belumDijawab"></span> soal yang belum dijawab!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kembali</button>
                    <form action="{{ route('ujian.selesai', $sesi) }}" method="POST">
                        @csrf
                        <input type="hidden" name="konfirmasi" value="1">
                        <button type="submit" class="btn btn-success">Ya, Selesaikan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Konfirmasi Kembali -->
    <div class="modal fade" id="modalKembali" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Kembali</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" @click="lanjutTimer()"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Waktu akan dijeda!</strong>
                    </div>
                    <p>Anda akan kembali ke halaman pilih tes. Jawaban yang sudah disimpan tidak akan hilang.</p>
                    <p>Anda bisa melanjutkan ujian nanti selama waktu belum habis.</p>
                    <div class="alert alert-info py-2">
                        <small>
                            <i class="bi bi-clock me-1"></i> Waktu tersisa: <strong x-text="formatWaktu(waktuTersisa)"></strong>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="lanjutTimer()">Lanjut Ujian</button>
                    <a href="{{ route('ujian.index') }}" class="btn btn-warning">
                        <i class="bi bi-arrow-left me-1"></i> Ya, Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ujianApp() {
    return {
        waktuTersisa: {{ $statistik['waktu_tersisa'] }},
        jawabanId: {{ $dataSoal['jawaban_peserta']?->jawaban_id ?? 'null' }},
        jawabanGanda: {!! json_encode($dataSoal['jawaban_peserta']?->jawaban_ganda ?? []) !!},
        jawabanEsai: {!! json_encode($dataSoal['jawaban_peserta']?->jawaban_esai ?? '') !!},
        ragu: {{ $dataSoal['jawaban_peserta']?->ragu ? 'true' : 'false' }},
        saving: false,
        nomorSoalSaatIni: {{ $dataSoal['nomor'] }},
        totalSoal: {{ $statistik['total_soal'] }},
        ringkasan: {!! json_encode($ringkasan) !!},
        statistik: {
            dijawab: {{ $statistik['dijawab'] }},
            ragu: {{ $statistik['ragu'] }},
            belumDijawab: {{ $statistik['belum_dijawab'] }},
            persentase: {{ $statistik['persentase_progres'] }}
        },

        timerPaused: false,
        timerInterval: null,

        init() {
            this.startTimer();
        },

        startTimer() {
            this.timerInterval = setInterval(() => {
                if (this.timerPaused) return;
                if (this.waktuTersisa > 0) {
                    this.waktuTersisa--;
                    
                    // Warning saat 5 menit tersisa
                    if (this.waktuTersisa === 300) {
                        alert('Perhatian! Waktu tersisa 5 menit.');
                    }
                    
                    // Auto submit saat waktu habis
                    if (this.waktuTersisa <= 0) {
                        this.autoSubmit();
                    }
                }
            }, 1000);
        },

        tampilKembali() {
            this.timerPaused = true;
            const modal = new bootstrap.Modal(document.getElementById('modalKembali'));
            modal.show();
        },

        lanjutTimer() {
            this.timerPaused = false;
        },

        formatWaktu(detik) {
            const jam = Math.floor(detik / 3600);
            const menit = Math.floor((detik % 3600) / 60);
            const det = detik % 60;
            return `${String(jam).padStart(2, '0')}:${String(menit).padStart(2, '0')}:${String(det).padStart(2, '0')}`;
        },

        getNavClass(nomor, isCurrent) {
            if (isCurrent) return 'btn-primary';
            
            const item = this.ringkasan.find(r => r.nomor === nomor);
            if (!item) return 'btn-outline-secondary';
            
            if (item.dijawab) {
                return item.ragu ? 'btn-warning' : 'btn-success';
            }
            return 'btn-outline-secondary';
        },

        updateStatistik() {
            // Update ringkasan untuk soal saat ini
            const currentIndex = this.ringkasan.findIndex(r => r.nomor === this.nomorSoalSaatIni);
            if (currentIndex !== -1) {
                const sudahDijawab = this.cekSudahDijawab();
                this.ringkasan[currentIndex].dijawab = sudahDijawab;
                this.ringkasan[currentIndex].ragu = this.ragu;
            }
            
            // Hitung ulang statistik
            this.statistik.dijawab = this.ringkasan.filter(r => r.dijawab).length;
            this.statistik.ragu = this.ringkasan.filter(r => r.ragu).length;
            this.statistik.belumDijawab = this.totalSoal - this.statistik.dijawab;
            this.statistik.persentase = Math.round((this.statistik.dijawab / this.totalSoal) * 100);
        },

        cekSudahDijawab() {
            if (this.jawabanId) return true;
            if (this.jawabanGanda && this.jawabanGanda.length > 0) return true;
            if (this.jawabanEsai && this.jawabanEsai.trim() !== '') return true;
            return false;
        },

        async simpanJawaban() {
            if (this.saving) return;
            this.saving = true;

            try {
                const response = await fetch('{{ route("ujian.simpan-jawaban", $sesi) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        soal_id: {{ $dataSoal['soal']->id }},
                        jawaban_id: this.jawabanId,
                        jawaban_ganda: this.jawabanGanda,
                        jawaban_esai: this.jawabanEsai,
                        ragu: this.ragu
                    })
                });

                const data = await response.json();
                if (data.sukses) {
                    // Update statistik dari server response
                    if (data.statistik) {
                        this.statistik.dijawab = data.statistik.dijawab;
                        this.statistik.ragu = data.statistik.ragu;
                        this.statistik.belumDijawab = data.statistik.belum_dijawab;
                        this.statistik.persentase = data.statistik.persentase_progres;
                    }
                    // Update juga ringkasan lokal
                    this.updateStatistik();
                } else if (data.error) {
                    console.error(data.error);
                }
            } catch (error) {
                console.error('Gagal menyimpan jawaban:', error);
            } finally {
                this.saving = false;
            }
        },

        autoSubmit() {
            document.querySelector('form[action="{{ route("ujian.selesai", $sesi) }}"]').submit();
        }
    }
}
</script>
@endpush
@endsection
