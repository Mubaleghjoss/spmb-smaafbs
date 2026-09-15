<?php

namespace App\Services;

use App\Models\Peserta;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Read-only, whitelist-based access to admissions data for the data bot. */
class SpmbReadService
{
    private const REQUIRED_DOCUMENTS = ['file_kk', 'file_akta', 'file_ijazah', 'file_bpjs', 'file_ktp_ibu', 'file_ktp_ayah'];


    public function getQuota(?int $tahunAjaranId = null): array
    {
        $year = $tahunAjaranId === null
            ? TahunAjaran::query()->where('aktif', true)->first()
            : TahunAjaran::query()->find($tahunAjaranId);
        if ($year === null) return ['tahun_ajaran' => null, 'quota' => null, 'registered' => 0, 'within_quota' => 0, 'waiting_list' => 0];

        $query = $this->base()->where('peserta.tahun_ajaran_id', $year->id);
        return [
            'tahun_ajaran' => ['id' => $year->id, 'nama' => $year->nama], 'quota' => $year->kuota_peserta,
            'quota_laki_laki' => $year->kuota_laki_laki, 'quota_perempuan' => $year->kuota_perempuan,
            'registered' => $query->count(),
            'within_quota' => (clone $query)->where('peserta.status_kuota', 'dalam_kuota')->count(),
            'waiting_list' => (clone $query)->where('peserta.status_kuota', 'waiting_list')->count(),
            'belum_lengkap' => (clone $query)->where('peserta.status_kuota', 'belum_lengkap')->count(),
        ];
    }

    public function getStatistics(?int $tahunAjaranId = null): array
    {
        $query = $this->forYear($tahunAjaranId);
        return [
            'total' => $query->count(),
            'verified' => (clone $query)->where('formulir_spmb.status_verifikasi', 'terverifikasi')->count(),
            'pending' => (clone $query)->whereIn('formulir_spmb.status_verifikasi', ['draft', 'menunggu', 'terkirim'])->count(),
            'rejected' => (clone $query)->where('formulir_spmb.status_verifikasi', 'ditolak')->count(),
            'complete_documents' => (clone $query)->where($this->documentsCompleteQuery())->count(),
            'incomplete_documents' => (clone $query)->whereNot($this->documentsCompleteQuery())->count(),
        ];
    }

    public function searchApplicant(string $query, int $limit = 20): array
    {
        $value = trim($query); if ($value === '') return [];
        return $this->records($this->base()->where(function (Builder $q) use ($value) {
            $q->where('peserta.nomor_pendaftaran', 'like', "%{$value}%")->orWhere('peserta.nama', 'like', "%{$value}%")->orWhere('peserta.telepon', 'like', "%{$value}%");
        }), $limit);
    }

    public function getApplicantDetail(string $identifier): ?array
    {
        $query = $this->base();
        $query->where(function (Builder $q) use ($identifier) {
            if (ctype_digit($identifier)) $q->orWhere('peserta.id', (int) $identifier);
            $q->orWhere('peserta.nomor_pendaftaran', $identifier);
        });
        $model = $query->with('formulirSpmb')->first();
        return $model === null ? null : $this->serialize($model, true);
    }

    public function listRegisteredToday(int $limit = 20): array
    {
        return $this->records($this->base()->whereDate('peserta.created_at', Carbon::today()), $limit);
    }

    public function genderSummary(?int $tahunAjaranId = null): array
    {
        $query = $this->forYear($tahunAjaranId);
        return ['L' => (clone $query)->where('formulir_spmb.jenis_kelamin', 'L')->count(), 'P' => (clone $query)->where('formulir_spmb.jenis_kelamin', 'P')->count(), 'unknown' => (clone $query)->whereNull('formulir_spmb.jenis_kelamin')->count()];
    }

    private function base(): Builder { return Peserta::withoutGlobalScopes()->with('formulirSpmb')->leftJoin('formulir_spmb', 'formulir_spmb.peserta_id', '=', 'peserta.id')->select('peserta.*'); }
    private function forYear(?int $year): Builder { $q = $this->base(); return $year === null ? $q : $q->where('peserta.tahun_ajaran_id', $year); }
    private function documentsCompleteQuery(): \Closure { return function (Builder $q): void { foreach (self::REQUIRED_DOCUMENTS as $document) $q->whereNotNull("formulir_spmb.{$document}")->where("formulir_spmb.{$document}", '!=', ''); }; }
    private function records(Builder $query, int $limit): array { return $query->limit(max(1, min($limit, 100)))->get()->map(fn (Peserta $p) => $this->serialize($p))->all(); }


    private function serialize(Peserta $p, bool $detail = false): array
    {
        $f = $p->formulirSpmb;
        $data = ['id' => $p->id, 'nomor_pendaftaran' => $p->nomor_pendaftaran, 'nama' => $p->nama, 'telepon' => $p->telepon, 'asal_sekolah' => $f?->asal_sekolah ?? $p->asal_sekolah, 'city' => $f?->alamat_kota, 'district' => $f?->alamat_kecamatan, 'gender' => $f?->jenis_kelamin, 'verification_status' => $f?->status_verifikasi, 'document_status' => $f !== null && $this->formDocumentsComplete($f) ? 'complete' : 'incomplete', 'status_kuota' => $p->status_kuota, 'registered_at' => $p->created_at?->toDateString()];
        if ($detail) $data['tahun_ajaran_id'] = $p->tahun_ajaran_id;
        return $data;
    }
    private function formDocumentsComplete(object $form): bool { foreach (self::REQUIRED_DOCUMENTS as $document) if (empty($form->{$document})) return false; return true; }
}
