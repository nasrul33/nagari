<?php

/**
 * Integrasi SIKD Teman Desa (Kemenkeu/DJPK) — M4.
 *
 * STATUS: skema API resmi BELUM didapat (lihat
 * .claude/skills/sikd-teman-desa-integration/SKILL.md). Nilai endpoint &
 * kredensial di bawah adalah placeholder; 'enabled' WAJIB false sampai
 * skema resmi masuk repo dan payload builder diimplementasikan.
 */
return [

    // Master switch — jangan dinyalakan sebelum skema resmi tersedia.
    'enabled' => env('SIKD_ENABLED', false),

    // Endpoint POST API resmi (belum diketahui — diisi setelah dokumen DJPK didapat).
    'endpoint' => env('SIKD_ENDPOINT'),

    // "Service Password" dari profil akun SIKD Teman Desa (mekanisme autentikasi
    // yang terkonfirmasi dari dokumentasi publik Siskeudes 2.0.9).
    'service_password' => env('SIKD_SERVICE_PASSWORD'),

    // Kebijakan retry job pengiriman — internet desa sering tidak stabil
    // (keputusan arsitektur CLAUDE.md: job/queue terpisah + retry).
    'retry' => [
        'tries' => 5,
        // backoff bertingkat (detik): 1m, 5m, 15m, 1j
        'backoff' => [60, 300, 900, 3600],
    ],
];
