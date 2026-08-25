{{--
    Popup penjelasan "Bagaimana masuk hitungan kuota".

    Dipakai bersama oleh halaman publik /daftar, dashboard admin, dan dashboard
    peserta. Satu sumber teks agar penjelasan tidak berbeda-beda antar halaman.

    Cara pakai:
        @include('partials.modal-penjelasan-kuota')
    lalu pasang tombol di mana pun:
        <button type="button" data-bs-toggle="modal" data-bs-target="#modalPenjelasanKuota">…</button>

    Catatan: partial ini tidak memakai variabel dari controller supaya bisa
    disertakan di halaman publik maupun admin tanpa syarat data apa pun.
--}}
<div class="modal fade" id="modalPenjelasanKuota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h6 class="modal-title">
                    <i class="bi bi-patch-question-fill me-2"></i>Bagaimana Cara Masuk Hitungan Kuota?
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p class="mb-3">
                    Kursi penerimaan terbatas, jadi kuota tidak langsung terpakai begitu akun dibuat.
                    Pendaftaran <strong>menempati kuota</strong> setelah dua syarat di bawah terpenuhi.
                </p>

                {{-- Dua syarat --}}
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-success rounded-circle px-2 py-1">1</span>
                                <div>
                                    <div class="fw-semibold">Formulir Biodata terisi</div>
                                    <div class="small text-muted">
                                        Tahap 2 — data siswa, orang tua, alamat, dan berkas wajib sudah dilengkapi.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="d-flex align-items-start gap-2">
                                <span class="badge bg-success rounded-circle px-2 py-1">2</span>
                                <div>
                                    <div class="fw-semibold">Pembayaran Pendaftaran diverifikasi</div>
                                    <div class="small text-muted">
                                        Tahap 3 — bukti pembayaran biaya pendaftaran sudah diperiksa dan
                                        disetujui Tim SPMB.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tiga status --}}
                <h6 class="fw-semibold">Tiga Status Kuota</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr>
                                <td style="width:9rem">
                                    <span class="badge bg-secondary">Belum Lengkap</span>
                                </td>
                                <td class="small">
                                    Salah satu syarat di atas belum selesai. Pendaftaran <strong>tercatat</strong>
                                    tetapi <strong>belum mengambil kursi</strong>.
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success">Masuk Kuota</span></td>
                                <td class="small">
                                    Kedua syarat sudah terpenuhi dan kursi masih tersedia — pendaftaran
                                    <strong>menempati kuota</strong>.
                                </td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-warning text-dark">Waiting List</span></td>
                                <td class="small">
                                    Kedua syarat sudah terpenuhi, tetapi kursi (total atau kuota
                                    laki-laki/perempuan) sudah habis. Pendaftaran <strong>mengantre</strong> dan
                                    otomatis naik ke Masuk Kuota bila ada kursi yang kembali.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Kapan kursi kembali --}}
                <h6 class="fw-semibold">Kapan Kursi Kembali Tersedia?</h6>
                <ul class="small mb-4">
                    <li>Peserta dinyatakan <strong>tidak lulus</strong> pada tahap mana pun.</li>
                    <li>Peserta <strong>mengundurkan diri</strong> atau datanya dihapus Tim SPMB.</li>
                    <li>Pembayaran pendaftaran <strong>dibatalkan / ditolak</strong> sehingga syarat tidak lagi terpenuhi.</li>
                </ul>
                <div class="alert alert-light border small">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Saat kursi kembali, peserta <strong>Waiting List dengan nomor urut terkecil</strong>
                    langsung dipromosikan menjadi Masuk Kuota.
                </div>

                {{-- Urutan --}}
                <h6 class="fw-semibold">Urutan Kursi</h6>
                <p class="small mb-3">
                    Urutan mengikuti <strong>tanggal pendaftaran</strong>, bukan tanggal pembayaran diverifikasi.
                    Jadi peserta yang mendaftar lebih dulu tidak dirugikan hanya karena verifikasinya
                    diproses lebih lambat.
                </p>

                <div class="alert alert-warning small mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Masuk kuota bukan berarti diterima.</strong> Kuota hanya menentukan ketersediaan
                    kursi. Keputusan penerimaan dinyatakan resmi melalui <strong>SK Kelulusan</strong> pada
                    tahap akhir.
                </div>

                <div class="alert alert-primary small mt-3 mb-0">
                    <i class="bi bi-arrow-left-right me-1"></i>
                    <strong>Jalur Siswa Pindahan tidak dibatasi kuota.</strong>
                    Seluruh penjelasan di atas berlaku untuk jalur <strong>Siswa Baru</strong>.
                    Pendaftar pindahan (Kelas 10 &amp; 11) tidak mengantre kursi dan tidak pernah
                    berstatus Waiting List, tetapi tetap harus melengkapi Formulir Biodata serta
                    Pembayaran Pendaftaran, dan penerimaannya tetap ditentukan SK Kelulusan.
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-sm" data-bs-dismiss="modal">Mengerti</button>
            </div>
        </div>
    </div>
</div>
