/* Service Worker SPMB — release-scoped caches and fresh-first delivery. */
const RELEASE_ID = '__SPMB_RELEASE_SHA__';
const CACHE_VERSION = `spmb-cache-v${RELEASE_ID}`;
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';
const PRECACHE = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
];
const BYPASS_PREFIXES = ['/admin', '/peserta', '/ujian', '/login', '/logout', '/daftar', '/cek-status'];
const ERROR_STATUSES = new Set([403, 404, 500]);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(PRECACHE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('spmb-cache-') && key !== STATIC_CACHE)
                    .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return /\.(css|js|png|jpg|jpeg|svg|webp|ico|woff2?|ttf)$/i.test(url.pathname) ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/');
}

function isPopupLogo(url) {
    return url.pathname.startsWith('/images/logo-commitment-') ||
        url.pathname === '/images/logo-yayasan-dar-al-furqon-al-hakim.jpg';
}

function isCacheableResponse(response, url) {
    if (!response || !response.ok || ERROR_STATUSES.has(response.status)) return false;
    if (isPopupLogo(url)) {
        const type = (response.headers.get('content-type') || '').toLowerCase();
        return type === 'image/png' || type === 'image/jpeg';
    }
    return true;
}

function isBypassed(url) {
    return BYPASS_PREFIXES.some((prefix) => url.pathname === prefix || url.pathname.startsWith(`${prefix}/`));
}

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    const url = new URL(req.url);
    if (url.origin !== self.location.origin || isBypassed(url)) return;

    if (url.pathname === '/manifest.webmanifest' || req.mode === 'navigate') {
        event.respondWith(
            fetch(req)
                .then((response) => response)
                .catch(() => (req.mode === 'navigate' ? caches.match(OFFLINE_URL) : Response.error()))
        );
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(
            fetch(req)
                .then((response) => {
                    if (isCacheableResponse(response, url)) {
                        return caches.open(STATIC_CACHE).then((cache) => {
                            cache.put(req, response.clone());
                            return response;
                        });
                    }
                    return response;
                })
                .catch(() => caches.match(req))
        );
    }
});
