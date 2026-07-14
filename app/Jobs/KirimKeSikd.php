<?php

namespace App\Jobs;

use App\Enums\StatusPengirimanSikd;
use App\Exceptions\SkemaSikdBelumTersediaException;
use App\Models\PengirimanSikd;
use App\Services\Sikd\PenyusunPayloadSikd;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Job pengiriman data ke SIKD Teman Desa (fondasi M4).
 *
 * POLA TENANT CONTEXT (temuan T-9 audit M2): global scope MilikDesa MATI di
 * queue worker (tidak ada auth). Karena itu job ini:
 * - menerima ID pengiriman + desa_id EKSPLISIT saat dispatch,
 * - memuat ulang record dengan constraint desa_id manual — job dengan
 *   pasangan id/desa yang tidak cocok gagal keras, tidak diam-diam
 *   memproses data tenant lain.
 * Semua query tambahan di job ini WAJIB di-constrain desa_id yang sama.
 */
class KirimKeSikd implements ShouldQueue
{
    use Queueable;

    public int $tries;

    /** @var list<int> */
    public array $backoff;

    public function __construct(
        public readonly int $pengirimanId,
        public readonly int $desaId,
    ) {
        $this->tries = (int) config('sikd.retry.tries');
        $this->backoff = config('sikd.retry.backoff');
    }

    public function handle(PenyusunPayloadSikd $penyusun): void
    {
        $pengiriman = PengirimanSikd::withoutGlobalScope('desa')
            ->where('desa_id', $this->desaId)
            ->findOrFail($this->pengirimanId);

        $pengiriman->update([
            'status' => StatusPengirimanSikd::Diproses,
            'percobaan' => $pengiriman->percobaan + 1,
        ]);

        try {
            $payload = $penyusun->susun($pengiriman);
        } catch (SkemaSikdBelumTersediaException $e) {
            // Gagal permanen — retry tidak akan memunculkan skema resmi.
            $this->fail($e);

            return;
        }

        // Pengiriman HTTP/ZIP menyusul setelah skema resmi tersedia —
        // baris di bawah tidak akan tercapai selama binding penjaga aktif.
        $pengiriman->update([
            'status' => StatusPengirimanSikd::Terkirim,
            'terkirim_pada' => now(),
            'pesan_gagal' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        PengirimanSikd::withoutGlobalScope('desa')
            ->where('desa_id', $this->desaId)
            ->where('id', $this->pengirimanId)
            ->update([
                'status' => StatusPengirimanSikd::Gagal->value,
                'pesan_gagal' => mb_strimwidth($exception?->getMessage() ?? 'Tidak diketahui', 0, 500),
            ]);
    }
}
