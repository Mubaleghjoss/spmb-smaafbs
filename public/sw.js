/* Service Worker SPMB — cache aset statis, network-first utk navigasi.
 * Versi cache dinaikkan setiap ada perubahan agar klien memperbarui.
 */
const CACHE_VERSION = 'spmb-v1';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => !k.startsWith(CACHE_VERSION)).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

function isStaticAsset(url) {
    return /\.(css|js|png|jpg|jpeg|svg|webp|ico|woff2?|ttf)$/i.test(url.pathname) ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/icons/');
}

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Hanya tangani GET. POST/PUT (login, submit form) selalu ke jaringan langsung.
    if (req.method !== 'GET') return;

    const url = new URL(req.url);

    // Jangan cache lintas-origin
    if (url.origin !== self.location.origin) return;

    // Jangan cache area sensitif/dinamis (dashboard, ujian, admin, storage privat)
    const bypass = ['/admin', '/peserta', '/ujian', '/login', '/logout', '/daftar', '/cek-status'];
    if (bypass.some((p) => url.pathname === p || url.pathname.startsWith(p + '/'))) {
        return; // biarkan default (network) — data selalu segar
    }

    // Aset statis: cache-first
    if (isStaticAsset(url)) {
        event.respondWith(
            caches.match(req).then((cached) =>
                cached || fetch(req).then((res) => {
                    const copy = res.clone();
                    caches.open(STATIC_CACHE).then((c) => c.put(req, copy));
                    return res;
                }).catch(() => cached)
            )
        );
        return;
    }

    // Navigasi halaman publik: network-first, fallback offline
    if (req.mode === 'navigate') {
        event.respondWith(
            fetch(req).catch(() => caches.match(OFFLINE_URL))
        );
        return;
    }
});
