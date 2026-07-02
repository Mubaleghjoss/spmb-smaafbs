<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAksesSync
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) config('services.akses_sync.require_https', true) && ! $request->isSecure()) {
            return new JsonResponse([
                'message' => 'HTTPS wajib digunakan.',
            ], Response::HTTP_UPGRADE_REQUIRED);
        }

        $configuredToken = (string) config('services.akses_sync.token', '');
        if ($configuredToken === '') {
            return new JsonResponse([
                'message' => 'Integrasi Akses belum dikonfigurasi.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $requestToken = (string) $request->bearerToken();
        if ($requestToken === '' || ! hash_equals($configuredToken, $requestToken)) {
            return new JsonResponse([
                'message' => 'Token integrasi tidak valid.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
