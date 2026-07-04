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
        ]);

        $peserta = Peserta::query()
            ->whereHas('tahapanSpmb', fn ($query) => $query
                ->where('status_kelulusan', 'lulus')
                ->where('tahap_7_selesai', true))
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
                'api_version' => '1.0',
                'generated_at' => now()->toIso8601String(),
            ])
            ->response()
            ->header('Cache-Control', 'no-store, private');
    }
}
