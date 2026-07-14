/**
 * Service worker offline-first (M5).
 *
 * Strategi:
 * - Navigasi (buka halaman): network-first, fallback ke salinan cache — supaya
 *   halaman entri draft offline tetap terbuka tanpa koneksi setelah dikunjungi.
 * - Aset statis (/build/*): cache-first.
 * - Non-GET (mis. POST /sync/transaksi): selalu jaringan, tidak pernah di-cache.
 */

const CACHE = 'keuangan-desa-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return; // biarkan POST/sync lewat langsung ke jaringan
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    // Navigasi halaman: network-first dengan fallback cache.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((res) => {
                    const salinan = res.clone();
                    caches.open(CACHE).then((c) => c.put(request, salinan));
                    return res;
                })
                .catch(() => caches.match(request).then((cached) => cached || caches.match('/transaksi-offline')))
        );
        return;
    }

    // Aset build: cache-first.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.match(request).then((cached) =>
                cached ||
                fetch(request).then((res) => {
                    const salinan = res.clone();
                    caches.open(CACHE).then((c) => c.put(request, salinan));
                    return res;
                })
            )
        );
    }
});
