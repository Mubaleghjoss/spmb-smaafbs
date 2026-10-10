<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaCacheStrategyTest extends TestCase
{
    public function test_service_worker_is_release_scoped_and_error_safe(): void
    {
        $worker = file_get_contents(resource_path('pwa/sw.js'));

        $this->assertIsString($worker);
        $this->assertStringContainsString("__SPMB_RELEASE_SHA__", $worker);
        $this->assertStringContainsString('spmb-cache-v${RELEASE_ID}', $worker);
        $this->assertStringContainsString('self.skipWaiting()', $worker);
        $this->assertStringContainsString('self.clients.claim()', $worker);
        $this->assertStringContainsString("key.startsWith('spmb-cache-')", $worker);
        $this->assertStringContainsString('!response.ok', $worker);
        $this->assertStringContainsString("url.pathname === '/manifest.webmanifest'", $worker);
        $this->assertStringContainsString('fetch(req)', $worker);
        $this->assertStringNotContainsString("caches.match(req).then((cached) => cached || fetch(req)", $worker);
    }

    public function test_service_worker_and_manifest_are_revalidated(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));
        $scripts = file_get_contents(resource_path('views/partials/pwa-scripts.blade.php'));

        $this->assertIsString($htaccess);
        $this->assertIsString($scripts);
        $this->assertStringContainsString('^(sw\\.js|manifest\\.webmanifest)$', $htaccess);
        $this->assertStringContainsString('Cache-Control "no-cache, must-revalidate"', $htaccess);
        $this->assertStringContainsString("updateViaCache: 'none'", $scripts);
        $this->assertStringContainsString('return registration.update()', $scripts);
    }

    public function test_pwa_routes_return_fresh_release_aware_responses(): void
    {
        $sw = $this->get('/sw.js');
        $sw->assertOk()
            ->assertHeader('Content-Type', 'application/javascript')
            ->assertSee("const RELEASE_ID = 'dev';", false);
        $this->assertStringContainsString('no-cache', (string) $sw->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $sw->headers->get('Cache-Control'));

        $manifest = $this->get('/manifest.webmanifest');
        $manifest->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertSee('SPMB SMA Al Furqon Boarding School', false);
        $this->assertStringContainsString('no-cache', (string) $manifest->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $manifest->headers->get('Cache-Control'));
    }
}
