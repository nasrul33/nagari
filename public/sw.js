/**
 * Service worker offline-first (M5).
 *
 * KEAMANAN (temuan T-1 audit): halaman ter-autentikasi berisi data keuangan &
 * data pribadi warga TIDAK BOLEH di-cache — di perangkat bersama, salinan cache
 * bisa tersaji ke user/tenant lain. Karena itu SW ini HANYA meng-cache halaman
 * entri offline (/transaksi-offline, shell yang datanya diisi dari IndexedDB
 * per-user) dan aset build statis. Halaman lain selalu jaringan; saat offline,
 * navigasi dialihkan ke shell entri offline, bukan salinan halaman berdata.
 */

const CACHE = 'keuangan-desa-v2';
const SHELL_OFFLINE = '/transaksi-offline';

self.addEventListener('install', () => {
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

// Pesan dari klien saat logout: buang seluruh cache (temuan T-1).
self.addEventListener('message', (event) => {
    if (event.data === 'bersihkan-cache') {
        event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((k) => caches.delete(k)))));
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return; // POST/sync selalu ke jaringan, tidak pernah di-cache
    }

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        const halamanEntri = url.pathname === SHELL_OFFLINE;

        event.respondWith(
            fetch(request)
                .then((res) => {
                    // HANYA halaman entri offline yang di-cache; halaman berdata
                    // tenant (dashboard/transaksi) tidak pernah disimpan.
                    if (halamanEntri) {
                        const salinan = res.clone();
                        caches.open(CACHE).then((c) => c.put(SHELL_OFFLINE, salinan));
                    }
                    return res;
                })
                .catch(() =>
                    // Offline: sajikan shell entri offline (bukan salinan halaman
                    // berdata), atau pesan sederhana bila belum pernah di-cache.
                    caches.match(SHELL_OFFLINE).then((cached) =>
                        cached ||
                        new Response(
                            '<!doctype html><meta charset="utf-8"><title>Offline</title>'
                            + '<p style="font-family:sans-serif;padding:2rem">Anda sedang offline. '
                            + 'Buka halaman <a href="/transaksi-offline">Draft Offline</a> untuk input tanpa koneksi.</p>',
                            { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                        )
                    )
                )
        );
        return;
    }

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
