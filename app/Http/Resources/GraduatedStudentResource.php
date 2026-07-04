<?php

namespace App\Http\Resources;

use App\Models\ProfilingConfig;
use App\Models\SesiTes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GraduatedStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $formulir = $this->formulirSpmb;
        $gayaBelajar = $this->latestResult('hasilGayaBelajar');
        $kepribadian = $this->latestResult('hasilPsikotesKepribadian');
        $mbti = $this->latestResult('hasilMbti');
        $profiling = $this->latestResult('hasilProfiling');

        $payload = [
            'pendaftaran' => [
                'tahun_ajaran' => $this->tahunAjaran?->nama,
                'gelombang' => $this->gelombangPendaftaran?->nama,
                'jenis_pendaftaran' => $this->jenis_pendaftaran,
                'kelas_tujuan' => $this->kelas_tujuan,
            ],
            'biodata' => [
                'nama' => $formulir?->nama_lengkap ?: $this->nama,
                'email' => $formulir?->email ?: $this->email,
                'telepon' => $formulir?->telepon ?: $this->telepon,
                'nisn' => $formulir?->nisn,
                'jenis_kelamin' => $formulir?->jenis_kelamin,
                'tempat_lahir' => $formulir?->tempat_lahir,
                'tanggal_lahir' => $formulir?->tanggal_lahir?->format('Y-m-d'),
                'agama' => $formulir?->agama,
                'alamat' => $formulir?->alamat ?: $this->alamat,
                'kelurahan' => $formulir?->alamat_kelurahan,
                'kecamatan' => $formulir?->alamat_kecamatan,
                'kota' => $formulir?->alamat_kota,
                'provinsi' => $formulir?->alamat_provinsi,
            ],
            'orang_tua' => [
                'nama_ayah' => $formulir?->nama_ayah,
                'pendidikan_ayah' => $formulir?->pendidikan_ayah,
                'pekerjaan_ayah' => $formulir?->pekerjaan_ayah,
                'telepon_ayah' => $formulir?->telepon_ayah,
                'nama_ibu' => $formulir?->nama_ibu,
                'pendidikan_ibu' => $formulir?->pendidikan_ibu,
                'pekerjaan_ibu' => $formulir?->pekerjaan_ibu,
                'telepon_ibu' => $formulir?->telepon_ibu,
            ],
            'sekolah_asal' => [
                'nama' => $formulir?->asal_sekolah ?: $this->asal_sekolah,
                'alamat' => $formulir?->alamat_sekolah,
            ],
            'fisik' => [
                'tinggi_badan' => $this->numericValue($formulir?->tinggi_badan),
                'berat_badan' => $this->numericValue($formulir?->berat_badan),
                'lingkar_kepala' => $this->numericValue($formulir?->lingkar_kepala),
            ],
            'hasil_tes' => [
                'kepribadian' => $kepribadian?->label,
                'gaya_belajar' => $gayaBelajar?->hasil_format,
                'profiling' => $this->profilingLabel($profiling?->pilar_dominan),
                'mbti' => filled($mbti?->tipe_mbti) ? Str::upper($mbti->tipe_mbti) : null,
            ],
        ];

        return [
            'source_id' => (string) $this->getKey(),
            'nomor_pendaftaran' => $this->nomor_pendaftaran,
            'source_updated_at' => $this->sourceUpdatedAt()?->toIso8601String(),
            'checksum' => hash('sha256', json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            )),
            ...$payload,
        ];
    }

    private function latestResult(string $relation): mixed
    {
        return $this->sesiTes
            ->filter(fn (SesiTes $sesi) => $sesi->getRelation($relation) !== null)
            ->sortByDesc(fn (SesiTes $sesi) => $sesi->getRelation($relation)->updated_at?->getTimestamp() ?? $sesi->id)
            ->first()
            ?->getRelation($relation);
    }

    private function profilingLabel(?string $pilar): ?string
    {
        if (blank($pilar)) {
            return null;
        }

        $config = ProfilingConfig::pilarList()[strtolower($pilar)] ?? null;
        if (! $config) {
            return Str::title($pilar);
        }

        return sprintf('%s (%s)', $config['nama_qx'], $config['kode_qx']);
    }

    private function numericValue(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }

    private function sourceUpdatedAt(): ?Carbon
    {
        $timestamps = collect([
            $this->updated_at,
            $this->formulirSpmb?->updated_at,
            $this->tahapanSpmb?->updated_at,
            $this->tahunAjaran?->updated_at,
            $this->gelombangPendaftaran?->updated_at,
        ]);

        $this->sesiTes->each(function (SesiTes $sesi) use ($timestamps): void {
            $timestamps->push($sesi->updated_at);

            foreach ([
                'hasilGayaBelajar',
                'hasilPsikotesKepribadian',
                'hasilMbti',
                'hasilProfiling',
            ] as $relation) {
                $timestamps->push($sesi->getRelation($relation)?->updated_at);
            }
        });

        return $timestamps
            ->filter()
            ->sortByDesc(fn ($timestamp) => $timestamp->getTimestamp())
            ->first();
    }
}
