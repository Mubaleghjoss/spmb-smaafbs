<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Peserta;
use App\Services\FormulirSpmbService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FormulirController extends Controller
{
    public function __construct(private FormulirSpmbService $formulirService) {}

    /**
     * Halaman isi formulir
     */
    public function isi(): View|RedirectResponse
    {
        $peserta = Peserta::find(session('peserta_id'));
        $formulir = $this->formulirService->ambilFormulir($peserta);
        
        // Cek apakah tahap 1 (Buat Akun) sudah selesai
        // Tahap 2 adalah Isi Formulir, jadi peserta bisa langsung isi setelah daftar
        if (!$peserta->tahapanSelesai(1)) {
            return redirect()->route('peserta.dashboard')
                ->with('error', 'Selesaikan pendaftaran akun terlebih dahulu');
        }
        
        // Jika sudah submit dan menunggu/terverifikasi, redirect ke review
        if ($formulir && in_array($formulir->status_verifikasi, ['menunggu', 'terverifikasi'])) {
            return redirect()->route('peserta.formulir.review');
        }
        
        return view('peserta.formulir.isi', compact('peserta', 'formulir'));
    }

    /**
     * Simpan formulir dan langsung submit untuk verifikasi
     */
    public function simpan(Request $request): RedirectResponse
    {
        $peserta = Peserta::find(session('peserta_id'));
        
        $validated = $request->validate(
            $this->formulirService->validasi($request->all()),
            $this->pesanValidasi()
        );

        foreach (['hobi', 'cita_cita'] as $field) {
            $validated[$field] = $this->normalisasiDaftarKoma($validated[$field] ?? null);
        }
        
        // Handle file uploads
        $fileFields = ['file_kk', 'file_akta', 'file_ijazah', 'file_bpjs', 'file_ktp_ibu', 'file_ktp_ayah', 'foto'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $request->file($field)->store("formulir/{$peserta->id}", 'public');
            } else {
                unset($validated[$field]);
            }
        }
        
        // Simpan formulir
        $formulir = $this->formulirService->simpan($peserta, $validated);
        
        // Cek kelengkapan - hanya field yang benar-benar wajib
        $cek = $this->formulirService->cekKelengkapan($formulir);
        if (!$cek['lengkap']) {
            $fieldKosong = array_slice($cek['kosong'], 0, 5);
            $sisanya = count($cek['kosong']) - 5;
            $pesan = 'Data belum lengkap: ' . implode(', ', $fieldKosong);
            if ($sisanya > 0) {
                $pesan .= " dan {$sisanya} field lainnya";
            }
            return redirect()->route('peserta.formulir.isi')
                ->with('error', $pesan);
        }
        
        // Langsung submit untuk verifikasi
        $this->formulirService->submit($peserta);
        
        return redirect()->route('peserta.formulir.review')
            ->with('success', 'Formulir berhasil disimpan dan disubmit. Tunggu verifikasi dari admin.');
    }

    /**
     * Submit formulir untuk verifikasi (legacy - redirect ke simpan)
     */
    public function submit(Request $request): RedirectResponse
    {
        return $this->simpan($request);
    }

    /**
     * Halaman review formulir
     */
    public function review(): View|RedirectResponse
    {
        $peserta = Peserta::find(session('peserta_id'));
        $formulir = $this->formulirService->ambilFormulir($peserta);
        
        if (!$formulir) {
            return redirect()->route('peserta.formulir.isi');
        }
        
        // Ambil nomor WhatsApp SPMB dari pengaturan
        $pengaturanService = app(\App\Services\PengaturanService::class);
        $spmb = $pengaturanService->ambilSpmb();
        $whatsappSpmb = $spmb['whatsapp_spmb'] ?? '';
        
        return view('peserta.formulir.review', compact('peserta', 'formulir', 'whatsappSpmb'));
    }

    /**
     * Upload berkas tambahan (untuk melengkapi berkas yang belum ada)
     */
    public function uploadBerkas(Request $request): RedirectResponse
    {
        $peserta = Peserta::find(session('peserta_id'));
        $formulir = $this->formulirService->ambilFormulir($peserta);
        
        if (!$formulir) {
            return redirect()->route('peserta.formulir.isi')
                ->with('error', 'Formulir tidak ditemukan');
        }
        
        // Validasi field yang diizinkan
        $allowedFields = ['file_kk', 'file_akta', 'file_ijazah', 'file_bpjs', 'file_ktp_ibu', 'file_ktp_ayah'];
        $field = $request->input('field');
        
        if (!in_array($field, $allowedFields)) {
            return back()->with('error', 'Field tidak valid');
        }
        
        // Validasi file
        $request->validate([
            'berkas' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [
            'berkas.required' => 'File wajib dipilih',
            'berkas.mimes' => 'Format file harus JPG, JPEG, PNG, atau PDF',
            'berkas.max' => 'Ukuran file maksimal 2MB',
        ]);
        
        // Upload file
        $path = $request->file('berkas')->store("formulir/{$peserta->id}", 'public');
        
        // Update formulir
        $formulir->update([$field => $path]);
        
        $fieldLabels = [
            'file_kk' => 'Kartu Keluarga',
            'file_akta' => 'Akta Lahir',
            'file_ijazah' => 'Ijazah SMP',
            'file_bpjs' => 'Kartu BPJS',
            'file_ktp_ibu' => 'KTP Ibu',
            'file_ktp_ayah' => 'KTP Ayah',
        ];
        
        return redirect()->route('peserta.formulir.review')
            ->with('sukses', "Berkas {$fieldLabels[$field]} berhasil diunggah");
    }

    /**
     * Update data fisik (boleh diupdate meskipun formulir sudah terverifikasi)
     */
    public function updateDataFisik(Request $request): RedirectResponse
    {
        $peserta = Peserta::find(session('peserta_id'));
        $formulir = $this->formulirService->ambilFormulir($peserta);
        
        if (!$formulir) {
            return redirect()->route('peserta.formulir.isi')
                ->with('error', 'Formulir tidak ditemukan');
        }
        
        // Validasi hanya field data fisik
        $validated = $request->validate([
            'tinggi_badan' => 'nullable|numeric|min:50|max:250',
            'berat_badan' => 'nullable|numeric|min:10|max:200',
            'lingkar_kepala' => 'nullable|numeric|min:30|max:80',
            'lingkar_dada' => 'nullable|numeric|min:30|max:200',
            'lingkar_pinggang' => 'nullable|numeric|min:30|max:200',
            'panjang_celana' => 'nullable|numeric|min:30|max:200',
        ], [
            'tinggi_badan.numeric' => 'Tinggi badan harus berupa angka',
            'tinggi_badan.min' => 'Tinggi badan minimal 50 cm',
            'tinggi_badan.max' => 'Tinggi badan maksimal 250 cm',
            'berat_badan.numeric' => 'Berat badan harus berupa angka',
            'berat_badan.min' => 'Berat badan minimal 10 kg',
            'berat_badan.max' => 'Berat badan maksimal 200 kg',
            'lingkar_kepala.numeric' => 'Lingkar kepala harus berupa angka',
            'lingkar_dada.numeric' => 'Lingkar dada harus berupa angka',
            'lingkar_pinggang.numeric' => 'Lingkar pinggang harus berupa angka',
            'panjang_celana.numeric' => 'Panjang celana harus berupa angka',
        ]);
        
        // Update hanya field data fisik
        $formulir->update($validated);
        
        return redirect()->route('peserta.formulir.review')
            ->with('sukses', 'Data fisik berhasil diperbarui');
    }

    /**
     * Pesan validasi dalam bahasa Indonesia
     */
    private function pesanValidasi(): array
    {
        return [
            'nama_lengkap.required' => 'Nama lengkap wajib diisi',
            'tempat_lahir.required' => 'Kota kelahiran wajib diisi',
            'provinsi_lahir.required' => 'Provinsi kelahiran wajib diisi',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid',
            'tinggi_badan.required' => 'Tinggi badan wajib diisi',
            'tinggi_badan.numeric' => 'Tinggi badan harus berupa angka',
            'berat_badan.required' => 'Berat badan wajib diisi',
            'berat_badan.numeric' => 'Berat badan harus berupa angka',
            'lingkar_kepala.required' => 'Lingkar kepala wajib diisi',
            'lingkar_kepala.numeric' => 'Lingkar kepala harus berupa angka',
            'hobi.required' => 'Hobi wajib diisi',
            'cita_cita.required' => 'Cita-cita wajib diisi',
            'jumlah_saudara.required' => 'Jumlah saudara wajib diisi',
            'alamat_kelurahan.required' => 'Kelurahan wajib diisi',
            'alamat_kecamatan.required' => 'Kecamatan wajib diisi',
            'alamat_kota.required' => 'Kota/Kabupaten wajib diisi',
            'alamat_provinsi.required' => 'Provinsi wajib diisi',
            'desa.required' => 'Desa wajib diisi',
            'daerah.required' => 'Daerah wajib diisi',
            'kelompok.required' => 'Kelompok wajib diisi',
            'telp_rumah.required' => 'Telepon rumah wajib diisi',
            'telepon.required' => 'No HP/WA siswa wajib diisi',
            'email.email' => 'Format email tidak valid',
            'nama_ayah.required' => 'Nama ayah wajib diisi',
            'pekerjaan_ayah.required' => 'Pekerjaan ayah wajib diisi',
            'telepon_ayah.required' => 'No HP/WA ayah wajib diisi',
            'nama_ibu.required' => 'Nama ibu wajib diisi',
            'pekerjaan_ibu.required' => 'Pekerjaan ibu wajib diisi',
            'telepon_ibu.required' => 'No HP/WA ibu wajib diisi',
            'asal_sekolah.required' => 'Asal sekolah SMP wajib diisi',
            'nisn.required' => 'NISN wajib diisi',
            'tanggal_daftar.required' => 'Tanggal daftar wajib diisi',
            'file_kk.mimes' => 'File KK harus berformat jpg, jpeg, png, atau pdf',
            'file_kk.max' => 'Ukuran file KK maksimal 2MB',
            'file_akta.mimes' => 'File Akta harus berformat jpg, jpeg, png, atau pdf',
            'file_akta.max' => 'Ukuran file Akta maksimal 2MB',
        ];
    }

    private function normalisasiDaftarKoma(?string $nilai): ?string
    {
        if ($nilai === null || trim($nilai) === '') {
            return null;
        }

        $items = preg_split('/[\r\n,]+/', $nilai) ?: [];
        $items = array_filter(array_map('trim', $items));

        return empty($items) ? null : implode(', ', array_values(array_unique($items)));
    }
}
