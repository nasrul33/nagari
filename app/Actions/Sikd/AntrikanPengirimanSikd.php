<?php

namespace App\Actions\Sikd;

use App\Enums\KategoriDataSikd;
use App\Enums\StatusPengirimanSikd;
use App\Jobs\KirimKeSikd;
use App\Models\PengirimanSikd;
use App\Models\TahunAnggaran;
use RuntimeException;

/**
 * Mengantrekan satu pengiriman data ke SIKD Teman Desa.
 * Sinkronisasi berjalan async via queue (keputusan arsitektur CLAUDE.md:
 * internet desa tidak stabil — jangan sinkron di request-response).
 */
class AntrikanPengirimanSikd
{
    public function handle(TahunAnggaran $tahunAnggaran, KategoriDataSikd $kategori, string $jalur = 'api'): PengirimanSikd
    {
        if (! config('sikd.enabled')) {
            throw new RuntimeException(
                'Integrasi SIKD Teman Desa belum diaktifkan (skema API resmi belum tersedia — lihat config/sikd.php).'
            );
        }

        $pengiriman = PengirimanSikd::create([
            'desa_id' => $tahunAnggaran->desa_id,
            'tahun_anggaran_id' => $tahunAnggaran->id,
            'kategori' => $kategori,
            'jalur' => $jalur,
            'status' => StatusPengirimanSikd::Antri,
        ]);

        // Tenant context eksplisit ikut ke queue (pola T-9)
        KirimKeSikd::dispatch($pengiriman->id, $pengiriman->desa_id);

        return $pengiriman;
    }
}
