<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SpmbDataBotAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array(app()->environment(), ['production', 'staging'], true)
            && ! $request->isSecure() && strtolower((string) $request->header('X-Forwarded-Proto')) !== 'https') {
            return $this->unauthorized($request, Response::HTTP_UPGRADE_REQUIRED, 'HTTPS required');
        }

        $expected = (string) config('services.spmb_data_bot.token', '');
        $provided = (string) $request->bearerToken();
        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return $this->unauthorized($request, Response::HTTP_UNAUTHORIZED, 'Unauthorized');
        }

        $response = $next($request);
        $payload = json_decode($response->getContent(), true);
        $result = is_array($payload) ? ($payload['data'] ?? null) : null;
        $count = is_array($result) ? (array_is_list($result) ? count($result) : (isset($result['data']) && is_array($result['data']) ? count($result['data']) : 1)) : 0;
        $this->audit($request, $response->getStatusCode(), $count);
        return $response;
    }

    private function unauthorized(Request $request, int $status, string $message): JsonResponse
    {
        $this->audit($request, $status, 0);
        return response()->json(['status' => 'error', 'message' => $message], $status);
    }

    private function audit(Request $request, int $status, int $resultCount): void
    {
        Log::build(['driver' => 'single', 'path' => storage_path('logs/data-bot-audit.log')])->info('SPMB data bot request', [
            'timestamp' => now()->toIso8601String(),
            'telegram_user_id' => $request->header('X-Telegram-User-Id'),
            'action' => $request->input('action'),
            'filters' => $request->input('filters', []),
            'result_count' => $resultCount,
            'status' => $status,
        ]);
    }
}
