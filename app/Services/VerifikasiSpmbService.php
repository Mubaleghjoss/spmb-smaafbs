<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\Pengguna;
use App\Models\Pembayaran;
use App\Models\FormulirSpmb;
use App\Models\TahapanSpmb;
use App\Models\LogTahapanSpmb;
use App\Enums\StatusPembayaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class VerifikasiSpmbService
{
    public function __construct(
        private SpmbService $spmbService,
        private PembayaranService $pembayaranService,
        private FormulirSpmbService $formulirService
    ) {}

    /**
     * Ambil daftar peserta untuk verifikasi dengan filter
     */
    public function ambilDaftarVerifikasi(array $filter, int $perHalaman = 15): LengthAwarePaginator
    {
        $query = Peserta::with(['tahapanSpmb', 'formulirSpmb', 'pembayaran'])
            ->whereHas('tahapanSpmb');

        if (!empty($filter['tahap'])) {
            $query->whereHas('tahapanSpmb', function ($q) use ($filter) {
                $q->where('tahap_saat_ini', $filter['tahap']);
            });
        }

        if (!empty($filter['status'])) {
            // Filter berdasarkan status verifikasi
            if ($filter['status'] === 'menunggu') {
                $query->where(function ($q) {
                    $q->whereHas('pembayaran', fn($p) => $p->where('status', 'menunggu'))
                      ->orWhereHas('formulirSpmb', fn($f) => $f->where('status_verifikasi', 'menunggu'));
                });
            }
        }

        if (!empty($filter['cari'])) {
            $query->where(function ($q) use ($filter) {
                $q->where('nama', 'like', "%{$filter['cari']}%")
                  ->orWhere('nomor_pendaftaran', 'like', "%{$filter['cari']}%");
            });
        }

        return $query->latest()->paginate($perHalaman);
    }

    /**
     * Verifikasi pembayaran formulir
     */
    public function verifikasiPembayaranFormulir(Pembayaran $pembayaran, Pengguna $admin): void
    {
        $this->pembayaranService->verifikasi($pembayaran, $admin);
    }

    /**
     * Tolak pembayaran formulir
     */
    public function tolakPembayaranFormulir(Pembayaran $pembayaran, string $alasan, Pengguna $admin): void
    {
        $this->pembayaranService->tolak($pembayaran, $alasan, $admin);
        $this->kirimNotifikasiPenolakan($pembayaran->peserta, 'pembayaran_formulir', $alasan);
    }

    /**
     * Verifikasi formulir SPMB
     */
    public function verifikasiFormulir(FormulirSpmb $formulir, Pengguna $admin): void
    {
        $this->formulirService->verifikasi($formulir, $admin);
    }

    /**
     * Tolak formulir SPMB
     */
    public function tolakFormulir(FormulirSpmb $formulir, string $alasan, Pengguna $admin): void
    {
        $this->formulirService->tolak($formulir, $alasan, $admin);
        $this->kirimNotifikasiPenolakan($formulir->peserta, 'formulir', $alasan);
    }

    /**
     * Verifikasi wawancara
     */
    public function verifikasiWawancara(Peserta $peserta, array $data, Pengguna $admin): void
    {
        DB::transaction(function () use ($peserta, $data, $admin) {
            // Simpan hasil wawancara jika ada model khusus
            // Untuk saat ini, langsung update tahapan
            if ($data['lulus'] ?? false) {
                $this->spmbService->selesaikanTahapan($peserta, 5, $admin->id);
            }
        });
    }

    /**
     * Verifikasi berkas
     */
    public function verifikasiBerkas(Peserta $peserta, array $dokumen, Pengguna $admin): void
    {
        DB::transaction(function () use ($peserta, $dokumen, $admin) {
            // Simpan checklist dokumen
            // Untuk saat ini, langsung update tahapan jika semua dokumen lengkap
            $semuaLengkap = collect($dokumen)->every(fn($v) => $v === true || $v === '1');
            
            if ($semuaLengkap) {
                // Berkas adalah bagian dari tahap 5 (wawancara)
                // Jadi tidak perlu update tahapan terpisah
            }
        });
    }

    /**
     * Verifikasi pelunasan
     */
    public function verifikasiPelunasan(Pembayaran $pembayaran, Pengguna $admin): void
    {
        DB::transaction(function () use ($pembayaran, $admin) {
            $this->pembayaranService->verifikasi($pembayaran, $admin);
            
            // Setelah pelunasan terverifikasi, peserta resmi diterima (tahap 7)
            $this->spmbService->selesaikanTahapan($pembayaran->peserta, 7, $admin->id);
        });
    }

    /**
     * Tolak pelunasan
     */
    public function tolakPelunasan(Pembayaran $pembayaran, string $alasan, Pengguna $admin): void
    {
        $this->pembayaranService->tolak($pembayaran, $alasan, $admin);
        $this->kirimNotifikasiPenolakan($pembayaran->peserta, 'pelunasan', $alasan);
    }

    /**
     * Kirim notifikasi penolakan ke peserta
     */
    public function kirimNotifikasiPenolakan(Peserta $peserta, string $jenis, string $alasan): void
    {
        // Implementasi notifikasi (email/SMS/in-app)
        // Untuk saat ini, log saja
        \Log::info("Notifikasi penolakan {$jenis} untuk peserta {$peserta->nomor_pendaftaran}: {$alasan}");
        
        // TODO: Implementasi email notification
        // if ($peserta->email) {
        //     Mail::to($peserta->email)->send(new PenolakanNotification($peserta, $jenis, $alasan));
        // }
    }

    /**
     * Ambil statistik verifikasi
     */
    public function ambilStatistik(): array
    {
        return [
            'pembayaran_menunggu' => Pembayaran::where('status', 'menunggu')
                ->where('jenis', 'formulir')
                ->count(),
            'pelunasan_menunggu' => Pembayaran::where('status', 'menunggu')
                ->where('jenis', 'pertama')
                ->count(),
            'formulir_menunggu' => FormulirSpmb::where('status_verifikasi', 'menunggu')->count(),
            'hasil_tes_menunggu' => \App\Models\SesiTes::whereIn('status', ['selesai', 'timeout'])
                ->where(function ($q) {
                    $q->where('status_verifikasi_tes', 'menunggu')
                      ->orWhereNull('status_verifikasi_tes');
                })
                ->whereHas('tes', function ($q) {
                    $q->whereColumn('sesi_tes.nilai', '<', 'tes.nilai_lulus');
                })
                ->count(),
            'total_peserta' => Peserta::count(),
            'peserta_per_tahap' => $this->hitungPesertaPerTahap(),
        ];
    }

    /**
     * Hitung peserta per tahap
     */
    private function hitungPesertaPerTahap(): array
    {
        $hasil = [];
        for ($i = 1; $i <= 7; $i++) {
            $hasil[$i] = TahapanSpmb::where('tahap_saat_ini', $i)->count();
        }
        return $hasil;
    }

    /**
     * Ambil pembayaran yang menunggu verifikasi
     */
    public function ambilPembayaranMenunggu(string $jenis, int $perHalaman = 15): LengthAwarePaginator
    {
        return Pembayaran::with('peserta')
            ->where('jenis', $jenis)
            ->where('status', 'menunggu')
            ->whereHas('peserta') // Hanya ambil yang punya peserta
            ->latest()
            ->paginate($perHalaman);
    }

    /**
     * Ambil formulir yang menunggu verifikasi
     */
    public function ambilFormulirMenunggu(int $perHalaman = 15): LengthAwarePaginator
    {
        return FormulirSpmb::with('peserta')
            ->where('status_verifikasi', 'menunggu')
            ->latest()
            ->paginate($perHalaman);
    }
}
