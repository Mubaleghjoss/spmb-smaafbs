<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GraduatedStudentResource;
use App\Models\Peserta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GraduatedStudentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            // Filter opsional — semuanya nullable agar pemanggil lama
            // (app.smaafbs.sch.id yang sudah berjalan) tidak terpengaruh.
            'jenis' => ['nullable', 'in:siswa_baru,pindahan'],
            'kelas_tujuan' => ['nullable', 'integer', 'in:10,11'],
            'tahun_ajaran' => ['nullable', 'string', 'max:20'],
            'sejak' => ['nullable', 'date'],
        ]);

        $peserta = Peserta::query()
            // API integrasi harus lintas periode & lintas jalur: konteks admin
            // (PeriodeScope/JalurScope) tidak boleh memengaruhi hasil sinkron.
            ->withoutGlobalScopes()
            ->whereHas('tahapanSpmb', fn ($query) => $query
                ->where('status_kelulusan', 'lulus')
                ->where('tahap_7_selesai', true))
            ->when($validated['jenis'] ?? null, fn ($q, $jenis) => $q
                ->where('jenis_pendaftaran', $jenis))
            ->when($validated['kelas_tujuan'] ?? null, fn ($q, $kelas) => $q
                ->where('kelas_tujuan', $kelas))
            ->when($validated['tahun_ajaran'] ?? null, fn ($q, $nama) => $q
                ->whereHas('tahunAjaran', fn ($sub) => $sub->where('nama', $nama)))
            ->when($validated['sejak'] ?? null, fn ($q, $sejak) => $q
                ->where('updated_at', '>=', $sejak))
            ->with([
                'formulirSpmb',
                'tahapanSpmb',
                'tahunAjaran',
                'gelombangPendaftaran',
                'sesiTes' => fn ($query) => $query
                    ->whereIn('status', ['selesai', 'timeout'])
                    ->with([
                        'hasilGayaBelajar',
                        'hasilPsikotesKepribadian',
                        'hasilMbti',
                        'hasilProfiling',
                    ]),
            ])
            ->orderBy('id')
            ->paginate((int) ($validated['per_page'] ?? 50));

        return GraduatedStudentResource::collection($peserta)
            ->additional([
                'api_version' => '1.2',
                'generated_at' => now()->toIso8601String(),
                'filters' => [
                    'jenis' => $validated['jenis'] ?? null,
                    'kelas_tujuan' => $validated['kelas_tujuan'] ?? null,
                    'tahun_ajaran' => $validated['tahun_ajaran'] ?? null,
                    'sejak' => $validated['sejak'] ?? null,
                ],
            ])
            ->response()
            ->header('Cache-Control', 'no-store, private');
    }
}
