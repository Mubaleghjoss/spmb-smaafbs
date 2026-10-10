<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class PwaAssetController extends Controller
{
    public function serviceWorker(): Response
    {
        $body = $this->readAsset('sw.js');
        $releaseFile = base_path('bootstrap/cache/spmb-release-sha');
        $release = is_readable($releaseFile) ? trim((string) file_get_contents($releaseFile)) : 'dev';
        $release = preg_match('/^[0-9a-f]{40}$/', $release) === 1 ? $release : 'dev';
        $body = str_replace('__SPMB_RELEASE_SHA__', $release, $body);

        return $this->assetResponse($body, 'application/javascript');
    }

    public function manifest(): Response
    {
        return $this->assetResponse($this->readAsset('manifest.webmanifest'), 'application/manifest+json');
    }

    private function readAsset(string $name): string
    {
        $path = resource_path("pwa/{$name}");
        abort_unless(is_readable($path), 404);

        return (string) file_get_contents($path);
    }

    private function assetResponse(string $body, string $contentType): Response
    {
        return response($body, 200, [
            'Cache-Control' => 'no-cache, must-revalidate',
            'Content-Type' => $contentType,
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
