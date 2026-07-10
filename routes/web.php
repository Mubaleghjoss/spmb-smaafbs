<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PendaftaranController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Peserta\LoginPesertaController;
use App\Http\Controllers\Peserta\DashboardSpmbController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\InstalasiController;

/*
|--------------------------------------------------------------------------
| Wizard Instalasi
|--------------------------------------------------------------------------
*/
Route::prefix('instalasi')->name('instalasi.')->group(function () {
    Route::get('/', [InstalasiController::class, 'index'])->name('index');
    Route::get('/database', [InstalasiController::class, 'database'])->name('database');
    Route::post('/database', [InstalasiController::class, 'simpanDatabase'])->name('simpan-database');
    Route::get('/admin', [InstalasiController::class, 'admin'])->name('admin');
    Route::post('/admin', [InstalasiController::class, 'simpanAdmin'])->name('simpan-admin');
    Route::get('/proses', [InstalasiController::class, 'proses'])->name('proses');
    Route::post('/jalankan', [InstalasiController::class, 'jalankan'])->name('jalankan');
    Route::get('/selesai', [InstalasiController::class, 'selesai'])->name('selesai');
});

/*
|--------------------------------------------------------------------------
| Halaman Publik
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'beranda'])->name('beranda');
Route::get('/alur-spmb', [PublicController::class, 'alurSpmb'])->name('alur-spmb');
Route::get('/jadwal', [PublicController::class, 'jadwal'])->name('jadwal');
Route::get('/kontak', [PublicController::class, 'kontak'])->name('kontak');
Route::match(['get', 'post'], '/cek-status', [PublicController::class, 'cekStatus'])->name('cek-status');
Route::get('/cek-status/download-sk/{peserta}', [PublicController::class, 'downloadSk'])
    ->middleware(\Illuminate\Routing\Middleware\ValidateSignature::class)
    ->name('cek-status.download-sk');

/*
|--------------------------------------------------------------------------
| Pendaftaran
|--------------------------------------------------------------------------
*/
Route::get('/daftar', [PendaftaranController::class, 'form'])->name('daftar');
Route::post('/daftar', [PendaftaranController::class, 'proses'])->name('daftar.proses');

/*
|--------------------------------------------------------------------------
| Autentikasi Admin/Operator
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'formLogin'])->name('login');
Route::post('/login', [LoginController::class, 'masuk'])->name('login.proses');
Route::post('/logout', [LoginController::class, 'keluar'])->name('logout');

// Login dengan token (untuk ujian)
Route::get('/login/token', [LoginController::class, 'formLoginToken'])->name('login.token');
Route::post('/login/token', [LoginController::class, 'masukDenganToken'])->name('login.token.proses');

/*
|--------------------------------------------------------------------------
| Autentikasi Peserta SPMB
|--------------------------------------------------------------------------
*/
Route::prefix('peserta')->name('peserta.')->group(function () {
    Route::get('/login', [LoginPesertaController::class, 'form'])->name('login');
    Route::post('/login', [LoginPesertaController::class, 'masuk'])->name('login.proses');
    Route::post('/logout', [LoginPesertaController::class, 'keluar'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Dashboard Peserta SPMB (memerlukan login peserta)
|--------------------------------------------------------------------------
*/
Route::prefix('peserta')->name('peserta.')->middleware('cek.peserta')->group(function () {
    Route::get('/dashboard', [DashboardSpmbController::class, 'index'])->name('dashboard');
    Route::get('/status-tahapan', [DashboardSpmbController::class, 'ambilStatusTahapan'])->name('status-tahapan');
    
    // Pembayaran
    Route::get('/pembayaran/formulir', [\App\Http\Controllers\Peserta\PembayaranController::class, 'uploadBuktiFormulir'])->middleware('cek.tahap.spmb:3')->name('pembayaran.formulir');
    Route::post('/pembayaran/formulir', [\App\Http\Controllers\Peserta\PembayaranController::class, 'simpanBuktiFormulir'])->middleware('cek.tahap.spmb:3')->name('pembayaran.simpan-formulir');
    Route::get('/pembayaran/status-formulir', [\App\Http\Controllers\Peserta\PembayaranController::class, 'statusFormulir'])->name('pembayaran.status-formulir');
    Route::get('/pembayaran/kwitansi/{pembayaran}', [\App\Http\Controllers\Peserta\PembayaranController::class, 'cetakKwitansi'])->name('pembayaran.kwitansi');
    
    // Formulir SPMB
    Route::get('/formulir', [\App\Http\Controllers\Peserta\FormulirController::class, 'isi'])->middleware('cek.tahap.spmb:2')->name('formulir.isi');
    Route::post('/formulir', [\App\Http\Controllers\Peserta\FormulirController::class, 'simpan'])->middleware('cek.tahap.spmb:2')->name('formulir.simpan');
    Route::post('/formulir/submit', [\App\Http\Controllers\Peserta\FormulirController::class, 'submit'])->middleware('cek.tahap.spmb:2')->name('formulir.submit');
    Route::get('/formulir/review', [\App\Http\Controllers\Peserta\FormulirController::class, 'review'])->name('formulir.review');
    Route::post('/formulir/upload-berkas', [\App\Http\Controllers\Peserta\FormulirController::class, 'uploadBerkas'])->middleware('cek.tahap.spmb:2')->name('formulir.upload-berkas');
    Route::post('/formulir/update-data-fisik', [\App\Http\Controllers\Peserta\FormulirController::class, 'updateDataFisik'])->middleware('cek.tahap.spmb:2')->name('formulir.update-data-fisik');
    
    // Pelunasan (Tahap 6)
    Route::get('/pembayaran/pelunasan', [\App\Http\Controllers\Peserta\PembayaranController::class, 'uploadBuktiPelunasan'])->middleware('cek.tahap.spmb:6')->name('pembayaran.pelunasan');
    Route::post('/pembayaran/pelunasan', [\App\Http\Controllers\Peserta\PembayaranController::class, 'simpanBuktiPelunasan'])->middleware('cek.tahap.spmb:6')->name('pembayaran.simpan-pelunasan');
    Route::get('/pembayaran/status-pelunasan', [\App\Http\Controllers\Peserta\PembayaranController::class, 'statusPelunasan'])->name('pembayaran.status-pelunasan');
    
    // Konfirmasi Diterima (Tahap 7)
    Route::get('/konfirmasi-diterima', [DashboardSpmbController::class, 'konfirmasiDiterima'])->middleware('cek.tahap.spmb:7')->name('konfirmasi-diterima');
    Route::get('/konfirmasi-diterima/sk', [DashboardSpmbController::class, 'downloadSuratKelulusan'])->name('surat-kelulusan.download');
    
    // Info Wawancara (Tahap 5)
    Route::get('/wawancara/info', [DashboardSpmbController::class, 'infoWawancara'])->middleware('cek.tahap.spmb:5')->name('wawancara.info');
    Route::post('/wawancara/simpan', [DashboardSpmbController::class, 'simpanWawancara'])->middleware('cek.tahap.spmb:5')->name('wawancara.simpan');
    Route::get('/wawancara/download-pegon', [DashboardSpmbController::class, 'downloadTesPegon'])->middleware('cek.tahap.spmb:5')->name('wawancara.download-pegon');
    Route::get('/wawancara/surat-pernyataan/pdf', [DashboardSpmbController::class, 'downloadSuratPernyataanPdf'])->middleware('cek.tahap.spmb:5')->name('wawancara.surat-pernyataan.pdf');
    Route::get('/wawancara/surat-pernyataan/cetak', [DashboardSpmbController::class, 'cetakSuratPernyataan'])->middleware('cek.tahap.spmb:5')->name('wawancara.surat-pernyataan.cetak');
});

/*
|--------------------------------------------------------------------------
| Ujian (memerlukan login peserta)
|--------------------------------------------------------------------------
*/
Route::prefix('ujian')->name('ujian.')->middleware('cek.peserta')->group(function () {
    Route::get('/', [\App\Http\Controllers\UjianController::class, 'index'])->name('index');
    Route::get('/pulihkan', [\App\Http\Controllers\UjianController::class, 'pulihkan'])->name('pulihkan');
    Route::get('/{tes}/konfirmasi', [\App\Http\Controllers\UjianController::class, 'konfirmasi'])->name('konfirmasi');
    Route::post('/{tes}/mulai', [\App\Http\Controllers\UjianController::class, 'mulai'])->name('mulai');
    Route::get('/sesi/{sesi}', [\App\Http\Controllers\UjianController::class, 'kerjakan'])->name('kerjakan');
    Route::post('/sesi/{sesi}/jawaban', [\App\Http\Controllers\UjianController::class, 'simpanJawaban'])->name('simpan-jawaban');
    Route::post('/sesi/{sesi}/selesai', [\App\Http\Controllers\UjianController::class, 'selesai'])->name('selesai');
    Route::get('/sesi/{sesi}/hasil', [\App\Http\Controllers\UjianController::class, 'hasil'])->name('hasil');
    Route::post('/sesi/{sesi}/permohonan-ulang', [\App\Http\Controllers\UjianController::class, 'ajukanPermohonanUlang'])->name('permohonan-ulang');
    Route::get('/sesi/{sesi}/waktu', [\App\Http\Controllers\UjianController::class, 'waktuTersisa'])->name('waktu-tersisa');
    Route::post('/sesi/{sesi}/peringatan', [\App\Http\Controllers\UjianController::class, 'catatPeringatan'])->name('catat-peringatan');
});

/*
|--------------------------------------------------------------------------
| Dashboard Admin (memerlukan login admin/operator/tim_spmb dengan akses menu)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth:pengguna', 'cek.akses.menu'])->group(function () {
    Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('index');
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    
    // Alur Peserta (Pipeline SPMB)
    Route::get('/alur-peserta', [\App\Http\Controllers\Admin\AlurPesertaController::class, 'index'])->name('alur-peserta.index');
    Route::get('/alur-peserta/ekspor', [\App\Http\Controllers\Admin\AlurPesertaController::class, 'eksporCsv'])->name('alur-peserta.ekspor');
    
    // Verifikasi SPMB
    Route::get('/verifikasi', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'index'])->name('verifikasi.index');
    Route::get('/verifikasi/peserta', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'daftarPeserta'])->name('verifikasi.peserta');
    Route::get('/verifikasi/history/{peserta}', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'historyPeserta'])->name('verifikasi.history');
    Route::get('/verifikasi/ekspor-peserta', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'eksporPeserta'])->name('verifikasi.ekspor-peserta');
    
    // Verifikasi Pembayaran Formulir
    Route::get('/verifikasi/pembayaran-formulir', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'pembayaranFormulir'])->name('verifikasi.pembayaran-formulir');
    Route::post('/verifikasi/pembayaran-formulir/{pembayaran}/terima', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'verifikasiPembayaranFormulir'])->name('verifikasi.pembayaran-formulir.terima');
    Route::post('/verifikasi/pembayaran-formulir/{pembayaran}/tolak', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tolakPembayaranFormulir'])->name('verifikasi.pembayaran-formulir.tolak');
    Route::post('/verifikasi/pembayaran-formulir/{peserta}/upload', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'uploadBuktiFormulir'])->name('verifikasi.pembayaran-formulir.upload');
    Route::get('/verifikasi/pembayaran/{pembayaran}/kwitansi', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'cetakKwitansi'])->name('verifikasi.kwitansi');
    
    // Verifikasi Formulir
    Route::get('/verifikasi/formulir', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'formulir'])->name('verifikasi.formulir');
    Route::get('/verifikasi/formulir/ekspor', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'eksporFormulir'])->name('verifikasi.formulir.ekspor');
    Route::get('/verifikasi/formulir/{formulir}', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'detailFormulir'])->name('verifikasi.formulir.detail');
    Route::post('/verifikasi/formulir/{formulir}/terima', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'verifikasiFormulir'])->name('verifikasi.formulir.terima');
    Route::post('/verifikasi/formulir/{formulir}/tolak', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tolakFormulir'])->name('verifikasi.formulir.tolak');
    
    // Verifikasi Pelunasan
    Route::get('/verifikasi/pelunasan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'pelunasan'])->name('verifikasi.pelunasan');
    Route::post('/verifikasi/pelunasan/{pembayaran}/terima', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'verifikasiPelunasan'])->name('verifikasi.pelunasan.terima');
    Route::post('/verifikasi/pelunasan/{pembayaran}/tolak', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tolakPelunasan'])->name('verifikasi.pelunasan.tolak');
    Route::post('/verifikasi/pelunasan/{peserta}/upload', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'uploadBuktiPelunasan'])->name('verifikasi.pelunasan.upload');
    
    // Verifikasi Hasil Tes
    Route::get('/verifikasi/hasil-tes', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'hasilTes'])->name('verifikasi.hasil-tes');
    Route::post('/verifikasi/hasil-tes/{sesi}/loloskan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'loloskanHasilTes'])->name('verifikasi.hasil-tes.loloskan');
    Route::post('/verifikasi/hasil-tes/{sesi}/tolak', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tolakHasilTes'])->name('verifikasi.hasil-tes.tolak');
    Route::post('/verifikasi/hasil-tes/{sesi}/setujui-perpanjangan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'setujuiPerpanjanganTimeout'])->name('verifikasi.hasil-tes.setujui-perpanjangan');
    Route::post('/verifikasi/hasil-tes/{sesi}/setujui-ulang-timeout', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'setujuiUlangTimeout'])->name('verifikasi.hasil-tes.setujui-ulang-timeout');
    Route::post('/verifikasi/hasil-tes/{sesi}/tolak-permohonan-timeout', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tolakPermohonanTimeout'])->name('verifikasi.hasil-tes.tolak-permohonan-timeout');
    Route::post('/verifikasi/hasil-tes/loloskan-batch', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'loloskanBatch'])->name('verifikasi.hasil-tes.loloskan-batch');
    Route::post('/verifikasi/hasil-tes/loloskan-semua', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'loloskanSemua'])->name('verifikasi.hasil-tes.loloskan-semua');
    Route::post('/verifikasi/hasil-tes/ulangi-batch', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'ulangiBatch'])->name('verifikasi.hasil-tes.ulangi-batch');
    
    // Verifikasi Wawancara (Tahap 5)
    Route::get('/verifikasi/wawancara', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'wawancara'])->name('verifikasi.wawancara');
    Route::get('/verifikasi/wawancara/{peserta}', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'formWawancara'])->name('verifikasi.wawancara.form');
    Route::post('/verifikasi/wawancara/{peserta}', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'simpanWawancara'])->name('verifikasi.wawancara.simpan');
    Route::get('/verifikasi/wawancara/{peserta}/cetak', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'cetakFormWawancara'])->name('verifikasi.wawancara.cetak');
    Route::get('/verifikasi/wawancara/{peserta}/surat-pernyataan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'cetakSuratPernyataan'])->name('verifikasi.wawancara.surat-pernyataan');
    Route::get('/verifikasi/wawancara/{peserta}/surat-pernyataan/pdf', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'downloadSuratPernyataanPdf'])->name('verifikasi.wawancara.surat-pernyataan.pdf');
    Route::post('/verifikasi/wawancara/{peserta}/loloskan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'loloskanWawancara'])->name('verifikasi.wawancara.loloskan');
    Route::post('/verifikasi/wawancara/{peserta}/tidak-lulus', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tidakLolosWawancara'])->name('verifikasi.wawancara.tidak-lulus');
    
    // Verifikasi Kelulusan (Tahap 7)
    Route::get('/verifikasi/kelulusan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'kelulusan'])->name('verifikasi.kelulusan');
    Route::post('/verifikasi/kelulusan/{peserta}/luluskan', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'luluskanPeserta'])->name('verifikasi.kelulusan.luluskan');
    Route::post('/verifikasi/kelulusan/{peserta}/tidak-lulus', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tidakLuluskanPeserta'])->name('verifikasi.kelulusan.tidak-lulus');
    Route::post('/verifikasi/kelulusan/luluskan-semua', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'luluskanSemua'])->name('verifikasi.kelulusan.luluskan-semua');
    Route::post('/verifikasi/kelulusan/luluskan-batch', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'luluskanBatchKelulusan'])->name('verifikasi.kelulusan.luluskan-batch');
    Route::post('/verifikasi/kelulusan/tidak-lulus-batch', [\App\Http\Controllers\Admin\VerifikasiSpmbController::class, 'tidakLulusBatch'])->name('verifikasi.kelulusan.tidak-lulus-batch');
    
    // Monitoring SPMB (disabled - merged into Alur Peserta)
    // Route::get('/monitoring', [\App\Http\Controllers\Admin\MonitoringSpmbController::class, 'index'])->name('monitoring.index');
    // Route::get('/monitoring/peserta', [\App\Http\Controllers\Admin\MonitoringSpmbController::class, 'daftarPeserta'])->name('monitoring.peserta');
    // Route::post('/monitoring/peserta/{peserta}/update', [\App\Http\Controllers\Admin\MonitoringSpmbController::class, 'updateTahapan'])->name('monitoring.update-tahapan');
    // Route::post('/monitoring/bulk-update', [\App\Http\Controllers\Admin\MonitoringSpmbController::class, 'bulkUpdate'])->name('monitoring.bulk-update');
    // Route::get('/monitoring/log', [\App\Http\Controllers\Admin\MonitoringSpmbController::class, 'logPerubahan'])->name('monitoring.log');
    // Route::get('/monitoring/ekspor', [\App\Http\Controllers\Admin\MonitoringSpmbController::class, 'ekspor'])->name('monitoring.ekspor');
    
    // Bank Soal - Routes statis harus sebelum routes dengan parameter
    Route::get('/soal', [\App\Http\Controllers\Admin\SoalController::class, 'index'])->name('soal.index');
    Route::get('/soal/create', [\App\Http\Controllers\Admin\SoalController::class, 'create'])->name('soal.create');
    Route::get('/soal/impor', [\App\Http\Controllers\Admin\SoalController::class, 'impor'])->name('soal.impor');
    Route::get('/soal/ekspor', [\App\Http\Controllers\Admin\SoalController::class, 'ekspor'])->name('soal.ekspor');
    Route::get('/soal/template', [\App\Http\Controllers\Admin\SoalController::class, 'downloadTemplate'])->name('soal.template');
    Route::get('/soal/preview', [\App\Http\Controllers\Admin\SoalController::class, 'preview'])->name('soal.preview');
    Route::get('/soal/lihat-semua', [\App\Http\Controllers\Admin\SoalController::class, 'lihatSemua'])->name('soal.lihat-semua');
    Route::post('/soal/update-urutan', [\App\Http\Controllers\Admin\SoalController::class, 'updateUrutan'])->name('soal.update-urutan');
    Route::delete('/soal/hapus-massal', [\App\Http\Controllers\Admin\SoalController::class, 'hapusMassal'])->name('soal.hapus-massal');
    Route::post('/soal', [\App\Http\Controllers\Admin\SoalController::class, 'store'])->name('soal.store');
    Route::post('/soal/impor/excel', [\App\Http\Controllers\Admin\SoalController::class, 'prosesImporExcel'])->name('soal.impor.excel');
    Route::post('/soal/impor/word', [\App\Http\Controllers\Admin\SoalController::class, 'prosesImporWord'])->name('soal.impor.word');
    
    // Topik Soal - Routes statis sebelum parameter
    Route::get('/soal/topik', [\App\Http\Controllers\Admin\TopikController::class, 'index'])->name('soal.topik.index');
    Route::get('/soal/topik/create', [\App\Http\Controllers\Admin\TopikController::class, 'create'])->name('soal.topik.create');
    Route::post('/soal/topik', [\App\Http\Controllers\Admin\TopikController::class, 'store'])->name('soal.topik.store');
    Route::get('/soal/topik/{topik}/edit', [\App\Http\Controllers\Admin\TopikController::class, 'edit'])->name('soal.topik.edit');
    Route::put('/soal/topik/{topik}', [\App\Http\Controllers\Admin\TopikController::class, 'update'])->name('soal.topik.update');
    Route::delete('/soal/topik/{topik}', [\App\Http\Controllers\Admin\TopikController::class, 'destroy'])->name('soal.topik.destroy');
    
    // Bank Soal - Routes dengan parameter
    Route::get('/soal/{soal}', [\App\Http\Controllers\Admin\SoalController::class, 'show'])->name('soal.show');
    Route::get('/soal/{soal}/edit', [\App\Http\Controllers\Admin\SoalController::class, 'edit'])->name('soal.edit');
    Route::put('/soal/{soal}', [\App\Http\Controllers\Admin\SoalController::class, 'update'])->name('soal.update');
    Route::delete('/soal/{soal}', [\App\Http\Controllers\Admin\SoalController::class, 'destroy'])->name('soal.destroy');
    Route::post('/soal/{soal}/toggle-aktif', [\App\Http\Controllers\Admin\SoalController::class, 'toggleAktif'])->name('soal.toggle-aktif');
    Route::post('/soal/{soal}/duplikat', [\App\Http\Controllers\Admin\SoalController::class, 'duplikat'])->name('soal.duplikat');
    Route::post('/soal/{soal}/upload-media', [\App\Http\Controllers\Admin\SoalController::class, 'uploadMedia'])->name('soal.upload-media');
    Route::delete('/soal/{soal}/hapus-media', [\App\Http\Controllers\Admin\SoalController::class, 'hapusMedia'])->name('soal.hapus-media');
    
    // Grup Peserta - HARUS sebelum routes dengan parameter {peserta}
    Route::get('/peserta/grup', [\App\Http\Controllers\Admin\GrupController::class, 'index'])->name('peserta.grup.index');
    Route::get('/peserta/grup/create', [\App\Http\Controllers\Admin\GrupController::class, 'create'])->name('peserta.grup.create');
    Route::post('/peserta/grup', [\App\Http\Controllers\Admin\GrupController::class, 'store'])->name('peserta.grup.store');
    Route::get('/peserta/grup/{grup}', [\App\Http\Controllers\Admin\GrupController::class, 'show'])->name('peserta.grup.show');
    Route::get('/peserta/grup/{grup}/edit', [\App\Http\Controllers\Admin\GrupController::class, 'edit'])->name('peserta.grup.edit');
    Route::put('/peserta/grup/{grup}', [\App\Http\Controllers\Admin\GrupController::class, 'update'])->name('peserta.grup.update');
    Route::delete('/peserta/grup/{grup}', [\App\Http\Controllers\Admin\GrupController::class, 'destroy'])->name('peserta.grup.destroy');
    Route::get('/peserta/grup/{grup}/tes', [\App\Http\Controllers\Admin\GrupController::class, 'tesGrup'])->name('peserta.grup.tes');
    Route::post('/peserta/grup/{grup}/tes', [\App\Http\Controllers\Admin\GrupController::class, 'simpanTesGrup'])->name('peserta.grup.simpan-tes');
    
    // Manajemen Peserta
    Route::get('/peserta', [\App\Http\Controllers\Admin\PesertaController::class, 'index'])->name('peserta.index');
    Route::get('/peserta/create', [\App\Http\Controllers\Admin\PesertaController::class, 'create'])->name('peserta.create');
    Route::get('/peserta/impor', [\App\Http\Controllers\Admin\PesertaController::class, 'impor'])->name('peserta.impor');
    Route::get('/peserta/ekspor', [\App\Http\Controllers\Admin\PesertaController::class, 'ekspor'])->name('peserta.ekspor');
    Route::get('/peserta/template', [\App\Http\Controllers\Admin\PesertaController::class, 'downloadTemplate'])->name('peserta.template');
    Route::get('/peserta/template-rekap-seleksi', [\App\Http\Controllers\Admin\PesertaController::class, 'downloadTemplateRekapSeleksi'])->name('peserta.template-rekap-seleksi');
    Route::get('/peserta/download-akun', [\App\Http\Controllers\Admin\PesertaController::class, 'downloadAkun'])->name('peserta.download-akun');
    Route::get('/peserta/download-biodata', [\App\Http\Controllers\Admin\PesertaController::class, 'downloadBiodata'])->name('peserta.download-biodata');
    Route::post('/peserta', [\App\Http\Controllers\Admin\PesertaController::class, 'store'])->name('peserta.store');
    Route::post('/peserta/impor', [\App\Http\Controllers\Admin\PesertaController::class, 'prosesImpor'])->name('peserta.impor.proses');
    Route::post('/peserta/impor-rekap-seleksi', [\App\Http\Controllers\Admin\PesertaController::class, 'prosesImporRekapSeleksi'])->name('peserta.impor-rekap-seleksi.proses');
    Route::post('/peserta/bulk-assign-grup', [\App\Http\Controllers\Admin\PesertaController::class, 'bulkAssignGrup'])->name('peserta.bulk-assign-grup');
    Route::post('/peserta/bulk-update-kategori', [\App\Http\Controllers\Admin\PesertaController::class, 'bulkUpdateKategori'])->name('peserta.bulk-update-kategori');
    Route::post('/peserta/bulk-update-tahap', [\App\Http\Controllers\Admin\PesertaController::class, 'bulkUpdateTahap'])->name('peserta.bulk-update-tahap');
    Route::post('/peserta/{id}/restore', [\App\Http\Controllers\Admin\PesertaController::class, 'restore'])->name('peserta.restore');
    Route::get('/peserta/{peserta}', [\App\Http\Controllers\Admin\PesertaController::class, 'show'])->name('peserta.show');
    Route::get('/peserta/{peserta}/edit', [\App\Http\Controllers\Admin\PesertaController::class, 'edit'])->name('peserta.edit');
    Route::get('/peserta/{peserta}/kartu', [\App\Http\Controllers\Admin\PesertaController::class, 'cetakKartu'])->name('peserta.kartu');
    Route::put('/peserta/{peserta}', [\App\Http\Controllers\Admin\PesertaController::class, 'update'])->name('peserta.update');
    Route::delete('/peserta/{peserta}', [\App\Http\Controllers\Admin\PesertaController::class, 'destroy'])->name('peserta.destroy');
    Route::post('/peserta/{peserta}/reset-password', [\App\Http\Controllers\Admin\PesertaController::class, 'resetPassword'])->name('peserta.reset-password');
    Route::post('/peserta/{peserta}/update-password', [\App\Http\Controllers\Admin\PesertaController::class, 'updatePassword'])->name('peserta.update-password');
    Route::post('/peserta/{peserta}/update-tahap', [\App\Http\Controllers\Admin\PesertaController::class, 'updateTahap'])->name('peserta.update-tahap');
    
    // Monitoring Ujian
    Route::get('/monitoring-ujian/semua-peserta', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'semuaPeserta'])->name('monitoring-ujian.semua-peserta');
    Route::get('/monitoring-ujian', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'index'])->name('monitoring-ujian.index');
    Route::get('/monitoring-ujian/{tes}', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'show'])->name('monitoring-ujian.show');
    Route::get('/monitoring-ujian/{tes}/peserta-online', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'pesertaOnline'])->name('monitoring-ujian.peserta-online');
    Route::get('/monitoring-ujian/{tes}/riwayat', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'riwayat'])->name('monitoring-ujian.riwayat');
    Route::get('/monitoring-ujian/{tes}/statistik-grup', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'statistikGrup'])->name('monitoring-ujian.statistik-grup');
    Route::post('/monitoring-ujian/sesi/{sesi}/perpanjang', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'perpanjangWaktu'])->name('monitoring-ujian.perpanjang');
    Route::post('/monitoring-ujian/sesi/{sesi}/reset', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'resetSesi'])->name('monitoring-ujian.reset');
    Route::post('/monitoring-ujian/sesi/{sesi}/paksa-selesai', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'paksaSelesai'])->name('monitoring-ujian.paksa-selesai');
    Route::post('/monitoring-ujian/sesi/{sesi}/batalkan', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'batalkanSesi'])->name('monitoring-ujian.batalkan');
    Route::post('/monitoring-ujian/paksa-selesai-tanpa-sesi', [\App\Http\Controllers\Admin\MonitoringUjianController::class, 'paksaSelesaiTanpaSesi'])->name('monitoring-ujian.paksa-selesai-tanpa-sesi');
    
    // Hasil Ujian
    Route::get('/hasil', [\App\Http\Controllers\Admin\HasilController::class, 'index'])->name('hasil.index');
    Route::get('/hasil/ekspor-rekap', [\App\Http\Controllers\Admin\HasilController::class, 'eksporRekap'])->name('hasil.ekspor-rekap');
    Route::post('/hasil/hitung-ulang-semua-psikotes', [\App\Http\Controllers\Admin\HasilController::class, 'hitungUlangSemuaPsikotes'])->name('hasil.hitung-ulang-semua-psikotes');
    Route::get('/hasil/peserta/{peserta}', [\App\Http\Controllers\Admin\HasilController::class, 'detailPesertaRekap'])->name('hasil.detail-peserta-rekap');
    Route::get('/hasil/{tes}', [\App\Http\Controllers\Admin\HasilController::class, 'show'])->name('hasil.show');
    Route::get('/hasil/{tes}/peserta/{sesi}', [\App\Http\Controllers\Admin\HasilController::class, 'detailPeserta'])->name('hasil.detail-peserta');
    Route::get('/hasil/{tes}/analisis', [\App\Http\Controllers\Admin\HasilController::class, 'analisisButirSoal'])->name('hasil.analisis');
    Route::get('/hasil/{tes}/penilaian-esai', [\App\Http\Controllers\Admin\HasilController::class, 'penilaianEsai'])->name('hasil.penilaian-esai');
    Route::post('/hasil/{tes}/penilaian-esai/{jawaban}', [\App\Http\Controllers\Admin\HasilController::class, 'simpanPenilaianEsai'])->name('hasil.simpan-penilaian-esai');
    Route::post('/hasil/{tes}/hitung-ulang', [\App\Http\Controllers\Admin\HasilController::class, 'hitungUlang'])->name('hasil.hitung-ulang');
    Route::post('/hasil/{tes}/peserta/{sesi}/hitung-ulang', [\App\Http\Controllers\Admin\HasilController::class, 'hitungUlangSesi'])->name('hasil.hitung-ulang-sesi');
    Route::post('/hasil/{tes}/hitung-ulang-mbti', [\App\Http\Controllers\Admin\HasilController::class, 'hitungUlangMbti'])->name('hasil.hitung-ulang-mbti');
    Route::post('/hasil/{tes}/hitung-ulang-profiling', [\App\Http\Controllers\Admin\HasilController::class, 'hitungUlangProfiling'])->name('hasil.hitung-ulang-profiling');
    Route::post('/hasil/{tes}/hitung-ulang-psikotes', [\App\Http\Controllers\Admin\HasilController::class, 'hitungUlangPsikotes'])->name('hasil.hitung-ulang-psikotes');
    Route::get('/hasil/{tes}/ekspor', [\App\Http\Controllers\Admin\HasilController::class, 'ekspor'])->name('hasil.ekspor');
    Route::post('/hasil/{tes}/peserta/{sesi}/izinkan-ulang', [\App\Http\Controllers\Admin\HasilController::class, 'izinkanUlang'])->name('hasil.izinkan-ulang');
    
    // Manajemen Tes - Routes statis sebelum parameter
    Route::get('/tes', [\App\Http\Controllers\Admin\TesController::class, 'index'])->name('tes.index');
    Route::get('/tes/create', [\App\Http\Controllers\Admin\TesController::class, 'create'])->name('tes.create');
    Route::post('/tes', [\App\Http\Controllers\Admin\TesController::class, 'store'])->name('tes.store');
    Route::get('/tes/{tes}', [\App\Http\Controllers\Admin\TesController::class, 'show'])->name('tes.show');
    Route::get('/tes/{tes}/edit', [\App\Http\Controllers\Admin\TesController::class, 'edit'])->name('tes.edit');
    Route::put('/tes/{tes}', [\App\Http\Controllers\Admin\TesController::class, 'update'])->name('tes.update');
    Route::delete('/tes/{tes}', [\App\Http\Controllers\Admin\TesController::class, 'destroy'])->name('tes.destroy');
    Route::post('/tes/{tes}/ubah-status', [\App\Http\Controllers\Admin\TesController::class, 'ubahStatus'])->name('tes.ubah-status');
    Route::post('/tes/{tes}/duplikat', [\App\Http\Controllers\Admin\TesController::class, 'duplikat'])->name('tes.duplikat');
    Route::post('/tes/bulk-status', [\App\Http\Controllers\Admin\TesController::class, 'bulkStatus'])->name('tes.bulk-status');
    Route::post('/tes/bulk-durasi', [\App\Http\Controllers\Admin\TesController::class, 'bulkDurasi'])->name('tes.bulk-durasi');
    Route::post('/tes/bulk-durasi-jadwal', [\App\Http\Controllers\Admin\TesController::class, 'bulkDurasiJadwal'])->name('tes.bulk-durasi-jadwal');
    Route::post('/tes/bulk-assign-grup', [\App\Http\Controllers\Admin\TesController::class, 'bulkAssignGrup'])->name('tes.bulk-assign-grup');
    Route::get('/tes/{tes}/sesi', [\App\Http\Controllers\Admin\TesController::class, 'daftarSesi'])->name('tes.sesi');
    Route::get('/tes/{tes}/grup', [\App\Http\Controllers\Admin\TesController::class, 'grupTes'])->name('tes.grup');
    Route::post('/tes/{tes}/grup', [\App\Http\Controllers\Admin\TesController::class, 'simpanGrupTes'])->name('tes.simpan-grup');
    
    // Pengaturan Soal Tes
    Route::get('/tes/{tes}/soal', [\App\Http\Controllers\Admin\TesController::class, 'soal'])->name('tes.soal');
    Route::post('/tes/{tes}/soal', [\App\Http\Controllers\Admin\TesController::class, 'tambahSoal'])->name('tes.tambah-soal');
    Route::post('/tes/{tes}/soal/batch', [\App\Http\Controllers\Admin\TesController::class, 'tambahSoalBatch'])->name('tes.tambah-soal-batch');
    Route::delete('/tes/{tes}/soal/batch', [\App\Http\Controllers\Admin\TesController::class, 'hapusSoalBatch'])->name('tes.hapus-soal-batch');
    Route::delete('/tes/{tes}/soal/{soalId}', [\App\Http\Controllers\Admin\TesController::class, 'hapusSoal'])->name('tes.hapus-soal');
    Route::post('/tes/{tes}/soal/urutan', [\App\Http\Controllers\Admin\TesController::class, 'updateUrutanSoal'])->name('tes.update-urutan-soal');
    Route::post('/tes/{tes}/soal/{soalId}/bobot', [\App\Http\Controllers\Admin\TesController::class, 'updateBobotSoal'])->name('tes.update-bobot-soal');
    
    // Psikotes Kepribadian
    Route::get('/tes/{tes}/psikotes-kepribadian', [\App\Http\Controllers\Admin\PsikotesKepribadianController::class, 'pengaturan'])->name('tes.psikotes-kepribadian');
    Route::post('/tes/{tes}/psikotes-kepribadian', [\App\Http\Controllers\Admin\PsikotesKepribadianController::class, 'simpan'])->name('tes.psikotes-kepribadian.simpan');
    Route::post('/tes/{tes}/psikotes-kepribadian/init-default', [\App\Http\Controllers\Admin\PsikotesKepribadianController::class, 'initDefault'])->name('tes.psikotes-kepribadian.init-default');
    Route::post('/tes/{tes}/psikotes-kepribadian/preview', [\App\Http\Controllers\Admin\PsikotesKepribadianController::class, 'preview'])->name('tes.psikotes-kepribadian.preview');
    
    // Gaya Belajar (Visual/Auditori/Kinestetik)
    Route::get('/tes/{tes}/gaya-belajar', [\App\Http\Controllers\Admin\GayaBelajarController::class, 'pengaturan'])->name('tes.gaya-belajar');
    Route::post('/tes/{tes}/gaya-belajar', [\App\Http\Controllers\Admin\GayaBelajarController::class, 'simpan'])->name('tes.gaya-belajar.simpan');
    Route::post('/tes/{tes}/gaya-belajar/init-default', [\App\Http\Controllers\Admin\GayaBelajarController::class, 'initDefault'])->name('tes.gaya-belajar.init-default');
    Route::post('/tes/{tes}/gaya-belajar/preview', [\App\Http\Controllers\Admin\GayaBelajarController::class, 'preview'])->name('tes.gaya-belajar.preview');
    
    // MBTI (Myers-Briggs Type Indicator)
    Route::get('/tes/{tes}/mbti', [\App\Http\Controllers\Admin\MbtiController::class, 'pengaturan'])->name('tes.mbti');
    Route::post('/tes/{tes}/mbti', [\App\Http\Controllers\Admin\MbtiController::class, 'simpan'])->name('tes.mbti.simpan');
    Route::post('/tes/{tes}/mbti/init-default', [\App\Http\Controllers\Admin\MbtiController::class, 'initDefault'])->name('tes.mbti.init-default');
    Route::post('/tes/{tes}/mbti/preview', [\App\Http\Controllers\Admin\MbtiController::class, 'preview'])->name('tes.mbti.preview');
    
    // Profiling (PiES Self Potency Method)
    Route::get('/tes/{tes}/profiling', [\App\Http\Controllers\Admin\ProfilingController::class, 'pengaturan'])->name('tes.profiling');
    Route::post('/tes/{tes}/profiling', [\App\Http\Controllers\Admin\ProfilingController::class, 'simpan'])->name('tes.profiling.simpan');
    Route::post('/tes/{tes}/profiling/init-default', [\App\Http\Controllers\Admin\ProfilingController::class, 'initDefault'])->name('tes.profiling.init-default');
    Route::post('/tes/{tes}/profiling/preview', [\App\Http\Controllers\Admin\ProfilingController::class, 'preview'])->name('tes.profiling.preview');
    Route::delete('/tes/{tes}/profiling', [\App\Http\Controllers\Admin\ProfilingController::class, 'hapus'])->name('tes.profiling.hapus');
    
    // Manajemen Token
    Route::get('/tes/{tes}/token', [\App\Http\Controllers\Admin\TokenController::class, 'index'])->name('tes.token.index');
    Route::get('/tes/{tes}/token/create', [\App\Http\Controllers\Admin\TokenController::class, 'create'])->name('tes.token.create');
    Route::post('/tes/{tes}/token', [\App\Http\Controllers\Admin\TokenController::class, 'store'])->name('tes.token.store');
    Route::delete('/tes/{tes}/token/{token}', [\App\Http\Controllers\Admin\TokenController::class, 'destroy'])->name('tes.token.destroy');
    Route::post('/tes/{tes}/token/{token}/reset', [\App\Http\Controllers\Admin\TokenController::class, 'reset'])->name('tes.token.reset');
    Route::post('/tes/{tes}/token/{token}/kedaluwarsa', [\App\Http\Controllers\Admin\TokenController::class, 'updateKedaluwarsa'])->name('tes.token.update-kedaluwarsa');
    Route::delete('/tes/{tes}/token', [\App\Http\Controllers\Admin\TokenController::class, 'hapusSemuaBelumTerpakai'])->name('tes.token.hapus-semua');
    Route::post('/tes/{tes}/token/hapus-batch', [\App\Http\Controllers\Admin\TokenController::class, 'hapusBatch'])->name('tes.token.hapus-batch');
    Route::post('/tes/{tes}/token/kedaluwarsa-batch', [\App\Http\Controllers\Admin\TokenController::class, 'updateKedaluwarsaBatch'])->name('tes.token.kedaluwarsa-batch');
    Route::get('/tes/{tes}/token/ekspor', [\App\Http\Controllers\Admin\TokenController::class, 'ekspor'])->name('tes.token.ekspor');
    
    // Pengaturan Sistem
    Route::get('/pengaturan', [\App\Http\Controllers\Admin\PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::get('/pengaturan/branding', [\App\Http\Controllers\Admin\PengaturanController::class, 'branding'])->name('pengaturan.branding');
    Route::post('/pengaturan/branding', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanBranding'])->name('pengaturan.branding.simpan');
    Route::get('/pengaturan/email', [\App\Http\Controllers\Admin\PengaturanController::class, 'email'])->name('pengaturan.email');
    Route::post('/pengaturan/email', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanEmail'])->name('pengaturan.email.simpan');
    Route::post('/pengaturan/email/test', [\App\Http\Controllers\Admin\PengaturanController::class, 'testEmail'])->name('pengaturan.email.test');
    Route::get('/pengaturan/spmb', [\App\Http\Controllers\Admin\PengaturanController::class, 'spmb'])->name('pengaturan.spmb');
    Route::post('/pengaturan/spmb', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanSpmb'])->name('pengaturan.spmb.simpan');
    Route::post('/pengaturan/spmb/toggle-pendaftaran', [\App\Http\Controllers\Admin\PengaturanController::class, 'togglePendaftaran'])->name('pengaturan.spmb.toggle-pendaftaran');
    Route::get('/pengaturan/spmb/periode', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'index'])->name('pengaturan.spmb.periode');
    Route::post('/pengaturan/spmb/periode/tahun', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'storeTahun'])->name('pengaturan.spmb.periode.tahun.store');
    Route::put('/pengaturan/spmb/periode/tahun/{tahunAjaran}', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'updateTahun'])->name('pengaturan.spmb.periode.tahun.update');
    Route::delete('/pengaturan/spmb/periode/tahun/{tahunAjaran}', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'destroyTahun'])->name('pengaturan.spmb.periode.tahun.destroy');
    Route::post('/pengaturan/spmb/periode/tahun/{tahunAjaran}/gelombang', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'storeGelombang'])->name('pengaturan.spmb.periode.gelombang.store');
    Route::put('/pengaturan/spmb/periode/tahun/{tahunAjaran}/gelombang/{gelombang}', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'updateGelombang'])->name('pengaturan.spmb.periode.gelombang.update');
    Route::delete('/pengaturan/spmb/periode/tahun/{tahunAjaran}/gelombang/{gelombang}', [\App\Http\Controllers\Admin\PeriodePendaftaranController::class, 'destroyGelombang'])->name('pengaturan.spmb.periode.gelombang.destroy');
    Route::get('/pengaturan/ujian', [\App\Http\Controllers\Admin\PengaturanController::class, 'ujian'])->name('pengaturan.ujian');
    Route::post('/pengaturan/ujian', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanUjian'])->name('pengaturan.ujian.simpan');
    Route::get('/pengaturan/ekspor', [\App\Http\Controllers\Admin\PengaturanController::class, 'ekspor'])->name('pengaturan.ekspor');
    Route::post('/pengaturan/impor', [\App\Http\Controllers\Admin\PengaturanController::class, 'impor'])->name('pengaturan.impor');
    
    // Pengaturan Syarat & Ketentuan
    Route::get('/pengaturan/syarat-ketentuan', [\App\Http\Controllers\Admin\PengaturanController::class, 'syaratKetentuan'])->name('pengaturan.syarat-ketentuan');
    Route::post('/pengaturan/syarat-ketentuan', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanSyaratKetentuan'])->name('pengaturan.syarat-ketentuan.simpan');
    Route::get('/pengaturan/syarat-ketentuan/reset', [\App\Http\Controllers\Admin\PengaturanController::class, 'resetSyaratKetentuan'])->name('pengaturan.syarat-ketentuan.reset');
    
    // Pengaturan Template Kwitansi
    Route::get('/pengaturan/template-kwitansi', [\App\Http\Controllers\Admin\PengaturanController::class, 'templateKwitansi'])->name('pengaturan.template-kwitansi');
    Route::post('/pengaturan/template-kwitansi', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanTemplateKwitansi'])->name('pengaturan.template-kwitansi.simpan');
    Route::get('/pengaturan/template-kwitansi/reset', [\App\Http\Controllers\Admin\PengaturanController::class, 'resetTemplateKwitansi'])->name('pengaturan.template-kwitansi.reset');
    
    // Download Project untuk Deployment
    Route::get('/pengaturan/download-project', [\App\Http\Controllers\Admin\DeploymentController::class, 'downloadProject'])->name('pengaturan.download-project');
    Route::get('/pengaturan/download-database', [\App\Http\Controllers\Admin\DeploymentController::class, 'downloadDatabase'])->name('pengaturan.download-database');
    
    // Import/Restore untuk Update
    Route::post('/pengaturan/import-database', [\App\Http\Controllers\Admin\DeploymentController::class, 'importDatabase'])->name('pengaturan.import-database');
    Route::post('/pengaturan/update-project', [\App\Http\Controllers\Admin\DeploymentController::class, 'updateProject'])->name('pengaturan.update-project');
    Route::post('/pengaturan/clear-cache', [\App\Http\Controllers\Admin\DeploymentController::class, 'clearCache'])->name('pengaturan.clear-cache');
    
    // Sinkronisasi Data Local ↔ Online
    Route::post('/pengaturan/sync/tarik', [\App\Http\Controllers\Admin\SyncController::class, 'tarikDariOnline'])->name('pengaturan.sync.tarik');
    Route::post('/pengaturan/sync/push', [\App\Http\Controllers\Admin\SyncController::class, 'pushKeOnline'])->name('pengaturan.sync.push');
    Route::get('/pengaturan/sync/riwayat', [\App\Http\Controllers\Admin\SyncController::class, 'riwayat'])->name('pengaturan.sync.riwayat');
    Route::post('/pengaturan/sync/resolve-konflik', [\App\Http\Controllers\Admin\SyncController::class, 'resolveKonflik'])->name('pengaturan.sync.resolve-konflik');
    Route::post('/pengaturan/sync/simpan-konfig', [\App\Http\Controllers\Admin\SyncController::class, 'simpanKonfigSync'])->name('pengaturan.sync.simpan-konfig');
    Route::post('/pengaturan/sync/tes-koneksi', [\App\Http\Controllers\Admin\SyncController::class, 'tesKoneksi'])->name('pengaturan.sync.tes-koneksi');
    
    // Pengaturan Alur SPMB
    Route::get('/pengaturan/alur-spmb', [\App\Http\Controllers\Admin\PengaturanController::class, 'alurSpmb'])->name('pengaturan.alur-spmb');
    Route::post('/pengaturan/alur-spmb', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanAlurSpmb'])->name('pengaturan.alur-spmb.simpan');
    Route::get('/pengaturan/alur-spmb/reset', [\App\Http\Controllers\Admin\PengaturanController::class, 'resetAlurSpmb'])->name('pengaturan.alur-spmb.reset');
    
    // Pengaturan Jadwal SPMB
    Route::get('/pengaturan/jadwal', [\App\Http\Controllers\Admin\PengaturanController::class, 'jadwal'])->name('pengaturan.jadwal');
    Route::post('/pengaturan/jadwal', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanJadwal'])->name('pengaturan.jadwal.simpan');
    Route::get('/pengaturan/jadwal/reset', [\App\Http\Controllers\Admin\PengaturanController::class, 'resetJadwal'])->name('pengaturan.jadwal.reset');

    // Pengaturan Wawancara
    Route::get('/pengaturan/wawancara', [\App\Http\Controllers\Admin\PengaturanController::class, 'wawancara'])->name('pengaturan.wawancara');
    Route::post('/pengaturan/wawancara', [\App\Http\Controllers\Admin\PengaturanController::class, 'simpanWawancara'])->name('pengaturan.wawancara.simpan');
    
    // Reset Data Peserta
    Route::get('/pengaturan/reset-data', [\App\Http\Controllers\Admin\PengaturanController::class, 'resetData'])->name('pengaturan.reset-data');
    Route::post('/pengaturan/reset-data', [\App\Http\Controllers\Admin\PengaturanController::class, 'prosesResetData'])->name('pengaturan.proses-reset-data');
    
    // Manajemen Pengguna
    Route::get('/pengguna', [\App\Http\Controllers\Admin\PenggunaController::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/create', [\App\Http\Controllers\Admin\PenggunaController::class, 'create'])->name('pengguna.create');
    Route::post('/pengguna', [\App\Http\Controllers\Admin\PenggunaController::class, 'store'])->name('pengguna.store');
    Route::get('/pengguna/{pengguna}/edit', [\App\Http\Controllers\Admin\PenggunaController::class, 'edit'])->name('pengguna.edit');
    Route::put('/pengguna/{pengguna}', [\App\Http\Controllers\Admin\PenggunaController::class, 'update'])->name('pengguna.update');
    Route::delete('/pengguna/{pengguna}', [\App\Http\Controllers\Admin\PenggunaController::class, 'destroy'])->name('pengguna.destroy');
    Route::post('/pengguna/{pengguna}/toggle-aktif', [\App\Http\Controllers\Admin\PenggunaController::class, 'toggleAktif'])->name('pengguna.toggle-aktif');
    
    // Token Global (untuk semua tes)
    Route::get('/token-global', [\App\Http\Controllers\Admin\TokenGlobalController::class, 'index'])->name('token-global.index');
    Route::post('/token-global', [\App\Http\Controllers\Admin\TokenGlobalController::class, 'store'])->name('token-global.store');
    Route::put('/token-global/{tokenGlobal}', [\App\Http\Controllers\Admin\TokenGlobalController::class, 'update'])->name('token-global.update');
    Route::delete('/token-global/{tokenGlobal}', [\App\Http\Controllers\Admin\TokenGlobalController::class, 'destroy'])->name('token-global.destroy');
    Route::post('/token-global/{tokenGlobal}/toggle', [\App\Http\Controllers\Admin\TokenGlobalController::class, 'toggleAktif'])->name('token-global.toggle');
    Route::get('/token-global/{tokenGlobal}/logs', [\App\Http\Controllers\Admin\TokenGlobalController::class, 'logs'])->name('token-global.logs');
});

/*
|--------------------------------------------------------------------------
| Redirect Operator ke Admin Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('operator')->name('operator.')->middleware(['auth:pengguna'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Redirect Tim SPMB ke Admin Dashboard
|--------------------------------------------------------------------------
*/
Route::prefix('tim-spmb')->name('tim-spmb.')->middleware(['auth:pengguna'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin.dashboard');
    })->name('dashboard');
});
