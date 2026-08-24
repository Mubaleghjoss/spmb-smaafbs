<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\Pengguna;
use App\Services\LogAktivitasService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LogAktivitasController extends Controller
{
    public function __construct(
        private LogAktivitasService $logAktivitas,
    ) {}

    public function index(Request $request): View
    {
        $filter = [
            'kategori' => $request->input('kategori'),
            'pengguna_id' => $request->input('pengguna_id'),
            'tanggal_dari' => $request->input('tanggal_dari'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
            'cari' => $request->input('cari'),
        ];

        $log = $this->logAktivitas->daftar($filter, 30);
        $statistik = $this->logAktivitas->statistik();
        $daftarKategori = LogAktivitas::daftarKategori();

        // Pelaku yang pernah tercatat (termasuk akun yang sudah dihapus)
        $daftarPengguna = Pengguna::orderBy('nama')->get(['id', 'nama', 'peran']);

        return view('admin.log-aktivitas.index', compact(
            'log', 'statistik', 'daftarKategori', 'daftarPengguna', 'filter'
        ));
    }

    /**
     * Ekspor log sesuai filter ke CSV (aksi baca — tidak dicatat ke log).
     */
    public function eksporCsv(Request $request): StreamedResponse
    {
        $filter = [
            'kategori' => $request->input('kategori'),
            'pengguna_id' => $request->input('pengguna_id'),
            'tanggal_dari' => $request->input('tanggal_dari'),
            'tanggal_sampai' => $request->input('tanggal_sampai'),
            'cari' => $request->input('cari'),
        ];

        $query = $this->logAktivitas->query($filter);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="log-aktivitas-' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'Waktu', 'Pengguna', 'Peran', 'Kategori', 'Aksi',
                'Subjek', 'Keterangan', 'Detail', 'IP',
            ], ';');

            $query->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $l) {
                    fputcsv($handle, [
                        $l->created_at?->format('d/m/Y H:i:s'),
                        $l->nama_pengguna,
                        $l->peran,
                        $l->kategori_label,
                        $l->aksi,
                        $l->subjek_label,
                        $l->keterangan,
                        $l->data ? json_encode($l->data, JSON_UNESCAPED_UNICODE) : '',
                        $l->ip,
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
