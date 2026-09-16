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
    private const VERIFICATION_STATUSES = ['draft', 'menunggu', 'terverifikasi', 'ditolak', 'terkirim'];
    private const QUOTA_STATUSES = ['dalam_kuota', 'waiting_list', 'belum_lengkap'];

    public function normalizeAcademicYear(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('/^(\d{2})(?:\s*\/\s*|\s+)(\d{2})$/', $value, $matches)) {
            $start = 2000 + (int) $matches[1];
            $end = 2000 + (int) $matches[2];
        } elseif (preg_match('/^(\d{4})(?:\s*[\/-]\s*|\s+)(\d{4})$/', $value, $matches)) {
            $start = (int) $matches[1];
            $end = (int) $matches[2];
        } else {
            return null;
        }

        if ($end !== $start + 1) {
            return null;
        }

        return sprintf('%04d/%04d', $start, $end);
    }

    public function resolveAcademicYearId(string $value): ?int
    {
        $normalized = $this->normalizeAcademicYear($value);

        if ($normalized === null) {
            return null;
        }

        $year = TahunAjaran::query()
            ->get(['id', 'nama'])
            ->first(function (TahunAjaran $year) use ($normalized): bool {
                return $this->normalizeAcademicYear((string) $year->nama) === $normalized;
            });

        return $year?->id;
    }

    public function getQuota(?int $tahunAjaranId = null): array
    {
        $year = $tahunAjaranId === null
            ? $this->activeAcademicYear()
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

    public function getStatistics(?int $tahunAjaranId = null, array $filters = []): array
    {
        $query = $this->applyFilters($this->forYear($tahunAjaranId), $filters);
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

    public function listApplicants(array $filters = [], int $perPage = 20, int $page = 1): array
    {
        if (! array_key_exists('tahun_ajaran_id', $filters)) {
            $activeYear = $this->activeAcademicYear();
            $filters['tahun_ajaran_id'] = $activeYear?->id;
        }

        $query = $this->applyFilters($this->base(), $filters);
        $perPage = max(1, min($perPage, 100)); $page = max(1, $page);
        $total = $query->count();
        $items = $this->records($query->orderByDesc('peserta.created_at')->forPage($page, $perPage), $perPage);
        return ['data' => $items, 'meta' => ['total' => $total, 'per_page' => $perPage, 'current_page' => $page, 'last_page' => max(1, (int) ceil($total / $perPage))]];
    }

    public function listByCity(string $city, int $limit = 20, ?int $year = null): array { return $this->records($this->forYear($year)->where('formulir_spmb.alamat_kota', 'like', "%{$city}%"), $limit); }
    public function listByDistrict(string $district, int $limit = 20, ?int $year = null): array { return $this->records($this->forYear($year)->where('formulir_spmb.alamat_kecamatan', 'like', "%{$district}%"), $limit); }
    public function listBySchool(string $school, int $limit = 20, ?int $year = null): array { return $this->records($this->forYear($year)->where(function (Builder $q) use ($school) { $q->where('formulir_spmb.asal_sekolah', 'like', "%{$school}%")->orWhere('peserta.asal_sekolah', 'like', "%{$school}%"); }), $limit); }
    public function listByDocumentStatus(string $status, int $limit = 20, ?int $year = null): array { return $this->records($this->applyFilters($this->forYear($year), ['document_status' => $status]), $limit); }
    public function listByVerificationStatus(string $status, int $limit = 20, ?int $year = null): array { return $this->records($this->applyFilters($this->forYear($year), ['verification_status' => $status]), $limit); }
    public function listRegisteredToday(int $limit = 20, ?int $year = null): array { return $this->records($this->applyFilters($this->forYear($year), ['registered_today' => true]), $limit); }

    public function genderSummary(?int $tahunAjaranId = null, array $filters = []): array
    {
        $query = $this->applyFilters($this->forYear($tahunAjaranId), $filters);
        return ['L' => (clone $query)->where('formulir_spmb.jenis_kelamin', 'L')->count(), 'P' => (clone $query)->where('formulir_spmb.jenis_kelamin', 'P')->count(), 'unknown' => (clone $query)->whereNull('formulir_spmb.jenis_kelamin')->count()];
    }

    private function activeAcademicYear(): ?TahunAjaran
    {
        return TahunAjaran::query()
            ->where('aktif', true)
            ->orderByDesc('nama')
            ->first();
    }

    private function base(): Builder { return Peserta::withoutGlobalScopes()->with(['formulirSpmb', 'tahunAjaran', 'tahapanSpmb'])->leftJoin('formulir_spmb', 'formulir_spmb.peserta_id', '=', 'peserta.id')->leftJoin('tahapan_spmb', 'tahapan_spmb.peserta_id', '=', 'peserta.id')->select('peserta.*'); }
    private function forYear(?int $year): Builder
    {
        $query = $this->base();

        if ($year !== null) {
            return $query->where('peserta.tahun_ajaran_id', $year);
        }

        $activeYear = $this->activeAcademicYear();

        if ($activeYear === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('peserta.tahun_ajaran_id', $activeYear->id);
    }
    private function documentsCompleteQuery(): \Closure { return function (Builder $q): void { foreach (self::REQUIRED_DOCUMENTS as $document) $q->whereNotNull("formulir_spmb.{$document}")->where("formulir_spmb.{$document}", '!=', ''); }; }
    private function records(Builder $query, int $limit): array { return $query->limit(max(1, min($limit, 100)))->get()->map(fn (Peserta $p) => $this->serialize($p))->all(); }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === null || $value === '') continue;
            switch ($key) {
                case 'nama': $query->where('peserta.nama', 'like', "%{$value}%"); break;
                case 'nomor_pendaftaran': $query->where('peserta.nomor_pendaftaran', 'like', "%{$value}%"); break;
                case 'asal_sekolah': $query->where(function (Builder $q) use ($value) { $q->where('formulir_spmb.asal_sekolah', 'like', "%{$value}%")->orWhere('peserta.asal_sekolah', 'like', "%{$value}%"); }); break;
                case 'jenis_pendaftaran': $query->where('peserta.jenis_pendaftaran', $value); break;
                case 'kelas_tujuan': $query->where('peserta.kelas_tujuan', (int) $value); break;
                case 'tahapan': $query->where('tahapan_spmb.tahap_saat_ini', (int) $value); break;
                case 'city': case 'kota': $query->where('formulir_spmb.alamat_kota', 'like', "%{$value}%"); break;
                case 'district': case 'kecamatan': $query->where('formulir_spmb.alamat_kecamatan', 'like', "%{$value}%"); break;
                case 'school': $query->where(function (Builder $q) use ($value) { $q->where('formulir_spmb.asal_sekolah', 'like', "%{$value}%")->orWhere('peserta.asal_sekolah', 'like', "%{$value}%"); }); break;
                case 'verification_status': if (in_array($value, self::VERIFICATION_STATUSES, true)) $query->where('formulir_spmb.status_verifikasi', $value); break;
                case 'document_status': if ($value === 'complete') $query->where($this->documentsCompleteQuery()); elseif ($value === 'incomplete') $query->whereNot($this->documentsCompleteQuery()); break;
                case 'gender': case 'jenis_kelamin': if (in_array($value, ['L', 'P'], true)) $query->where('formulir_spmb.jenis_kelamin', $value); break;
                case 'status_kuota': if (in_array($value, self::QUOTA_STATUSES, true)) $query->where('peserta.status_kuota', $value); break;
                case 'registered_today': if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) $query->whereDate('peserta.created_at', Carbon::today()); break;
                case 'registered_date': if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) $query->whereDate('peserta.created_at', $value); break;
                case 'tahun_ajaran_id': if (filter_var($value, FILTER_VALIDATE_INT) !== false) $query->where('peserta.tahun_ajaran_id', (int) $value); break;
            }
        } return $query;
    }

    private function serialize(Peserta $p, bool $detail = false): array
    {
        $f = $p->formulirSpmb;
        $data = ['id' => $p->id, 'nomor_pendaftaran' => $p->nomor_pendaftaran, 'nama' => $p->nama, 'telepon' => $p->telepon, 'asal_sekolah' => $f?->asal_sekolah ?? $p->asal_sekolah, 'city' => $f?->alamat_kota, 'district' => $f?->alamat_kecamatan, 'gender' => $f?->jenis_kelamin, 'jenis_pendaftaran' => $p->jenis_pendaftaran, 'kelas_tujuan' => $p->kelas_tujuan, 'verification_status' => $f?->status_verifikasi, 'document_status' => $f !== null && $this->formDocumentsComplete($f) ? 'complete' : 'incomplete', 'status_kuota' => $p->status_kuota, 'tahap_saat_ini' => $p->tahapanSpmb?->tahap_saat_ini, 'registered_at' => $p->created_at?->toDateString()];
        if ($detail) {
            $data['tahun_ajaran_id'] = $p->tahun_ajaran_id;
            $data['tahun_ajaran'] = $p->tahunAjaran?->nama;
            $data['registration_type'] = $p->jenis_pendaftaran;
            $data['target_class'] = $p->kelas_tujuan;
            $data['school'] = $data['asal_sekolah'];
            $data['stage'] = $data['tahap_saat_ini'];
            $data['quota_status'] = $p->status_kuota;
        }
        return $data;
    }
    private function formDocumentsComplete(object $form): bool { foreach (self::REQUIRED_DOCUMENTS as $document) if (empty($form->{$document})) return false; return true; }
}
