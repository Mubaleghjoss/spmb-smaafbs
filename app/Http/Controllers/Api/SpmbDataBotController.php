<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SpmbReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpmbDataBotController extends Controller
{
    private const ACTIONS = ['get_quota', 'get_statistics', 'search_applicant', 'get_applicant_detail', 'list_applicants', 'list_by_city', 'list_by_district', 'list_by_school', 'list_by_document_status', 'list_by_verification_status', 'list_registered_today', 'gender_summary'];

    public function __invoke(Request $request, SpmbReadService $service): JsonResponse
    {
        $action = $request->input('action');
        $filters = $request->input('filters', []);
        if (! is_string($action) || ! in_array($action, self::ACTIONS, true)) return response()->json(['status' => 'error', 'message' => 'Unsupported action'], 400);
        if (! is_array($filters) || $this->hasSqlInjectionPattern($filters)) return response()->json(['status' => 'error', 'message' => 'Invalid filter value'], 422);

        $limit = $this->limit($filters['limit'] ?? 20);
        $year = null;

        if (array_key_exists('tahun_ajaran_id', $filters)) {
            if (filter_var($filters['tahun_ajaran_id'], FILTER_VALIDATE_INT) === false) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid tahun_ajaran_id',
                ], 422);
            }

            $year = (int) $filters['tahun_ajaran_id'];
        } elseif (array_key_exists('tahun_ajaran', $filters)) {
            if (! is_string($filters['tahun_ajaran'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid tahun_ajaran format',
                ], 422);
            }

            $normalizedYear = $service->normalizeAcademicYear($filters['tahun_ajaran']);

            if ($normalizedYear === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid tahun_ajaran format',
                ], 422);
            }

            $year = $service->resolveAcademicYearId($normalizedYear);

            if ($year === null) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tahun ajaran '.str_replace('/', '-', $normalizedYear).' belum tersedia',
                ], 404);
            }
        }

        $data = match ($action) {
            'get_quota' => $service->getQuota($year),
            'get_statistics' => $service->getStatistics($year),
            'search_applicant' => $service->searchApplicant((string) ($filters['query'] ?? ''), $limit),
            'get_applicant_detail' => $service->getApplicantDetail((string) ($filters['identifier'] ?? '')),
            'list_applicants' => $service->listApplicants($filters, $limit, max(1, (int) ($filters['page'] ?? 1))),
            'list_by_city' => $service->listByCity((string) ($filters['city'] ?? $filters['kota'] ?? ''), $limit),
            'list_by_district' => $service->listByDistrict((string) ($filters['district'] ?? $filters['kecamatan'] ?? ''), $limit),
            'list_by_school' => $service->listBySchool((string) ($filters['school'] ?? $filters['asal_sekolah'] ?? ''), $limit),
            'list_by_document_status' => $service->listByDocumentStatus((string) ($filters['status'] ?? ''), $limit),
            'list_by_verification_status' => $service->listByVerificationStatus((string) ($filters['status'] ?? ''), $limit),
            'list_registered_today' => $service->listRegisteredToday($limit),
            'gender_summary' => $service->genderSummary($year),
        };
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    private function limit(mixed $value): int { return max(1, min(100, filter_var($value, FILTER_VALIDATE_INT) ?: 20)); }
    private function hasSqlInjectionPattern(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value) && $this->hasSqlInjectionPattern($value)) return true;
            if (is_string($value) && preg_match('/(;|--|\/\*|\*\/|\bunion\s+(all\s+)?select\b|\bdrop\s+(table|database)\b|\binsert\s+into\b|\bdelete\s+from\b)/i', $value)) return true;
        }
        return false;
    }
}
