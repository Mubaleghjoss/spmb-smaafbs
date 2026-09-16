<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\JalurContextService;
use App\Services\SpmbReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpmbDataBotController extends Controller
{
    private const ACTION_FILTERS = [
        'get_quota' => ['tahun_ajaran', 'tahun_ajaran_id'],
        'get_statistics' => ['tahun_ajaran', 'tahun_ajaran_id', 'jenis_pendaftaran', 'kelas_tujuan', 'gender', 'tahapan', 'nama', 'nomor_pendaftaran', 'asal_sekolah', 'city', 'district', 'school', 'verification_status', 'document_status', 'status_kuota', 'registered_today', 'registered_date'],
        'gender_summary' => ['tahun_ajaran', 'tahun_ajaran_id', 'jenis_pendaftaran', 'kelas_tujuan', 'tahapan'],
        'search_applicant' => ['query', 'limit'],
        'get_applicant_detail' => ['identifier'],
        'list_applicants' => ['tahun_ajaran', 'tahun_ajaran_id', 'jenis_pendaftaran', 'kelas_tujuan', 'gender', 'tahapan', 'nama', 'nomor_pendaftaran', 'asal_sekolah', 'city', 'district', 'school', 'verification_status', 'document_status', 'status_kuota', 'registered_today', 'registered_date', 'page', 'limit'],
        'list_by_city' => ['city', 'kota', 'tahun_ajaran', 'tahun_ajaran_id', 'limit'],
        'list_by_district' => ['district', 'kecamatan', 'tahun_ajaran', 'tahun_ajaran_id', 'limit'],
        'list_by_school' => ['school', 'asal_sekolah', 'tahun_ajaran', 'tahun_ajaran_id', 'limit'],
        'list_by_document_status' => ['status', 'tahun_ajaran', 'tahun_ajaran_id', 'limit'],
        'list_by_verification_status' => ['status', 'tahun_ajaran', 'tahun_ajaran_id', 'limit'],
        'list_registered_today' => ['tahun_ajaran', 'tahun_ajaran_id', 'limit'],
    ];

    public function __invoke(Request $request, SpmbReadService $service): JsonResponse
    {
        $action = $request->input('action');
        if (! is_string($action) || ! array_key_exists($action, self::ACTION_FILTERS)) {
            return response()->json(['status' => 'error', 'message' => 'Unsupported action'], 400);
        }
        $filters = $request->input('filters', []);
        if (! is_array($filters)) return $this->invalid('filters must be an object');
        $unknown = array_diff(array_keys($filters), self::ACTION_FILTERS[$action]);
        if ($unknown) return $this->invalid('Unknown filter: '.(string) reset($unknown));
        if ($this->hasSqlInjectionPattern($filters)) return $this->invalid('Invalid filter value');
        $error = $this->validateFilters($action, $filters);
        if ($error !== null) return $this->invalid($error);

        $year = null;
        if (array_key_exists('tahun_ajaran_id', $filters)) {
            $year = (int) $filters['tahun_ajaran_id'];
        } elseif (array_key_exists('tahun_ajaran', $filters)) {
            $normalized = $service->normalizeAcademicYear($filters['tahun_ajaran']);
            if ($normalized === null) return $this->invalid('Invalid tahun_ajaran format');
            $year = $service->resolveAcademicYearId($normalized);
            if ($year === null) return response()->json(['status' => 'error', 'message' => 'Tahun ajaran '.str_replace('/', '-', $normalized).' belum tersedia'], 404);
        }
        $queryFilters = $filters;
        if ($year !== null) $queryFilters['tahun_ajaran_id'] = $year;
        $limit = $filters['limit'] ?? 20;
        $data = match ($action) {
            'get_quota' => $service->getQuota($year),
            'get_statistics' => $service->getStatistics($year, $queryFilters),
            'gender_summary' => $service->genderSummary($year, $queryFilters),
            'search_applicant' => $service->searchApplicant($filters['query'], $limit),
            'get_applicant_detail' => $service->getApplicantDetail($filters['identifier']),
            'list_applicants' => $service->listApplicants($queryFilters, $limit, $filters['page'] ?? 1),
            'list_by_city' => $service->listByCity($filters['city'] ?? $filters['kota'], $limit, $year),
            'list_by_district' => $service->listByDistrict($filters['district'] ?? $filters['kecamatan'], $limit, $year),
            'list_by_school' => $service->listBySchool($filters['school'] ?? $filters['asal_sekolah'], $limit, $year),
            'list_by_document_status' => $service->listByDocumentStatus($filters['status'], $limit, $year),
            'list_by_verification_status' => $service->listByVerificationStatus($filters['status'], $limit, $year),
            'list_registered_today' => $service->listRegisteredToday($limit, $year),
        };
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    private function validateFilters(string $action, array $filters): ?string
    {
        foreach (['limit', 'page', 'tahun_ajaran_id', 'kelas_tujuan', 'tahapan'] as $key) {
            if (array_key_exists($key, $filters) && (filter_var($filters[$key], FILTER_VALIDATE_INT) === false || (int) $filters[$key] < 1)) return "Invalid {$key}";
        }
        if (isset($filters['limit']) && (int) $filters['limit'] > 100) return 'Invalid limit';
        if (isset($filters['page']) && (int) $filters['page'] > 1000000) return 'Invalid page';
        if (array_key_exists('tahun_ajaran', $filters) && ! is_string($filters['tahun_ajaran'])) return 'Invalid tahun_ajaran format';
        if (isset($filters['jenis_pendaftaran']) && ! in_array($filters['jenis_pendaftaran'], [JalurContextService::SISWA_BARU, JalurContextService::PINDAHAN], true)) return 'Invalid jenis_pendaftaran';
        if (isset($filters['kelas_tujuan'])) {
            $jenis = $filters['jenis_pendaftaran'] ?? null;
            if (! in_array((int) $filters['kelas_tujuan'], JalurContextService::kelasDiizinkan($jenis), true)) return 'Invalid kelas_tujuan';
        }
        if (isset($filters['gender']) && ! in_array($filters['gender'], ['L', 'P'], true)) return 'Invalid gender';
        if (isset($filters['verification_status']) && ! in_array($filters['verification_status'], ['draft', 'menunggu', 'terverifikasi', 'ditolak', 'terkirim'], true)) return 'Invalid verification_status';
        if (($action === 'list_by_verification_status') && isset($filters['status']) && ! in_array($filters['status'], ['draft', 'menunggu', 'terverifikasi', 'ditolak', 'terkirim'], true)) return 'Invalid status';
        if (isset($filters['document_status']) && ! in_array($filters['document_status'], ['complete', 'incomplete'], true)) return 'Invalid document_status';
        if (($action === 'list_by_document_status') && isset($filters['status']) && ! in_array($filters['status'], ['complete', 'incomplete'], true)) return 'Invalid status';
        if (isset($filters['status_kuota']) && ! in_array($filters['status_kuota'], ['dalam_kuota', 'waiting_list', 'belum_lengkap'], true)) return 'Invalid status_kuota';
        if (isset($filters['tahapan']) && (int) $filters['tahapan'] > 7) return 'Invalid tahapan';
        foreach (['query', 'identifier', 'nama', 'nomor_pendaftaran', 'asal_sekolah', 'city', 'kota', 'district', 'kecamatan', 'school', 'status', 'verification_status', 'document_status', 'status_kuota', 'registered_date'] as $key) if (array_key_exists($key, $filters) && ! is_string($filters[$key])) return "Invalid {$key}";
        if (isset($filters['registered_today']) && ! is_bool($filters['registered_today']) && ! in_array($filters['registered_today'], [0, 1, '0', '1', 'true', 'false'], true)) return 'Invalid registered_today';
        if (isset($filters['registered_date']) && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters['registered_date'])) return 'Invalid registered_date';
        foreach (['search_applicant' => 'query', 'get_applicant_detail' => 'identifier', 'list_by_city' => ['city', 'kota'], 'list_by_district' => ['district', 'kecamatan'], 'list_by_school' => ['school', 'asal_sekolah'], 'list_by_document_status' => 'status', 'list_by_verification_status' => 'status'] as $requiredAction => $requiredKeys) {
            if ($action !== $requiredAction) continue;
            $keys = (array) $requiredKeys;
            if (! array_filter($keys, fn (string $key): bool => array_key_exists($key, $filters) && trim((string) $filters[$key]) !== '')) return 'Missing required filter';
        }
        if ($action === 'search_applicant' && trim($filters['query']) === '') return 'Invalid query';
        if ($action === 'get_applicant_detail' && trim($filters['identifier']) === '') return 'Invalid identifier';
        return null;
    }

    private function invalid(string $message): JsonResponse { return response()->json(['status' => 'error', 'message' => $message], 422); }
    private function hasSqlInjectionPattern(array $values): bool { foreach ($values as $value) { if (is_array($value) && $this->hasSqlInjectionPattern($value)) return true; if (is_string($value) && preg_match('/(;|--|\/\*|\*\/|\bunion\s+(all\s+)?select\b|\bdrop\s+(table|database)\b|\binsert\s+into\b|\bdelete\s+from\b)/i', $value)) return true; } return false; }
}
