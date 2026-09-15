<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SpmbReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpmbDataBotController extends Controller
{
    private const ACTIONS = ['get_quota', 'get_statistics', 'gender_summary', 'list_registered_today', 'search_applicant', 'get_applicant_detail'];

    public function __invoke(Request $request, SpmbReadService $service): JsonResponse
    {
        $action = (string) $request->input('action');
        $filters = $request->input('filters', []);
        if (! in_array($action, self::ACTIONS, true)) return response()->json(['status' => 'error', 'message' => 'Unsupported action'], 400);
        if (! is_array($filters) || $this->hasSqlInjectionPattern($filters)) return response()->json(['status' => 'error', 'message' => 'Invalid filter value'], 422);

        $limit = $this->limit($filters['limit'] ?? 20);
        $year = isset($filters['tahun_ajaran_id']) && filter_var($filters['tahun_ajaran_id'], FILTER_VALIDATE_INT) !== false ? (int) $filters['tahun_ajaran_id'] : null;
        $data = match ($action) {
            'get_quota' => $service->getQuota($year),
            'get_statistics' => $service->getStatistics($year),
            'search_applicant' => $service->searchApplicant((string) ($filters['query'] ?? ''), $limit),
            'get_applicant_detail' => $service->getApplicantDetail((string) ($filters['identifier'] ?? '')),
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
