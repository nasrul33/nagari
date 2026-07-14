<?php

namespace App\Actions\Sync;

use App\Enums\HasilSinkronisasi;
use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Models\SinkronisasiLog;
use App\Models\TahunAnggaran;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Sinkronisasi antrian draft transaksi dari klien offline (M5).
 *
 * Aturan resolusi konflik = locking berbasis state approval (CLAUDE.md #1):
 * - Offline hanya boleh MEMBUAT draft baru atau MENGEDIT draft yang masih
 *   berstatus Draft. Transaksi yang sudah masuk alur SPP/SPM TERKUNCI.
 * - uuid di-generate klien → sync idempoten (kirim ulang tidak menggandakan).
 * - Konflik dua perangkat atas draft yang sama: versi dengan client_updated_at
 *   terbaru menang; yang kalah dicatat di sinkronisasi_logs.
 *
 * Tiap item diproses independen — satu item gagal tidak membatalkan batch.
 *
 * CATATAN audit: pada hasil Diperbarui (klien menang), diff isi lama→baru
 * terekam di tabel audit owen-it (trait Auditable pada Transaksi); log
 * sinkronisasi mencatat "siapa/kapan/hasil". Pada KonflikDitolak, isi versi
 * yang kalah diringkas ke keterangan log.
 */
class SinkronkanDraftOffline
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{uuid: string|null, hasil: string, transaksi_id: int|null, keterangan: string|null}>
     */
    public function handle(User $pelaku, array $items): array
    {
        if (! $pelaku->hasRole(PeranDesa::KaurKeuangan->value)) {
            throw new RuntimeException('Hanya Kaur Keuangan yang boleh menyinkronkan draft transaksi.');
        }

        return array_map(fn (array $item) => $this->proses($pelaku, $item), array_values($items));
    }

    /**
     * @return array{uuid: string|null, hasil: string, transaksi_id: int|null, keterangan: string|null}
     */
    private function proses(User $pelaku, array $item): array
    {
        $validator = Validator::make($item, [
            'uuid' => ['required', 'uuid'],
            'tahun_anggaran_id' => ['required', 'integer'],
            'akun_id' => ['required', 'exists:akuns,id'],
            'tanggal' => ['required', 'date'],
            'uraian' => ['required', 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'client_updated_at' => ['required', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->hasil(
                $pelaku, $item['uuid'] ?? null, null,
                HasilSinkronisasi::Ditolak,
                $validator->errors()->first(),
            );
        }

        $data = $validator->validated();
        // Bandingkan lewat Carbon, tapi SIMPAN string ISO mentah (lihat catatan
        // presisi di Transaksi::casts()).
        $clientUpdatedAt = CarbonImmutable::parse($data['client_updated_at']);

        // Tahun anggaran wajib milik desa pelaku (tanpa scope agar terdeteksi
        // walau lintas desa — lalu ditolak eksplisit).
        $tahunAnggaran = TahunAnggaran::withoutGlobalScopes()->find($data['tahun_anggaran_id']);
        if ($tahunAnggaran === null || $tahunAnggaran->desa_id !== $pelaku->desa_id) {
            return $this->hasil(
                $pelaku, $data['uuid'], null,
                HasilSinkronisasi::Ditolak,
                'Tahun anggaran tidak ditemukan di desa Anda.',
            );
        }

        // Deteksi tabrakan uuid lintas tenant sebelum menyentuh scope.
        $existing = Transaksi::withoutGlobalScopes()->where('uuid', $data['uuid'])->first();

        if ($existing !== null && $existing->desa_id !== $pelaku->desa_id) {
            // Pesan digeneralkan — jangan jadi oracle keberadaan lintas tenant
            // (temuan T-6 audit).
            return $this->hasil(
                $pelaku, $data['uuid'], null,
                HasilSinkronisasi::Ditolak,
                'UUID sudah dipakai.',
            );
        }

        return $existing === null
            ? $this->buatBaru($pelaku, $data, $clientUpdatedAt)
            : $this->perbaruiAtauTolak($pelaku, $existing, $data, $clientUpdatedAt);
    }

    private function buatBaru(User $pelaku, array $data, CarbonImmutable $clientUpdatedAt): array
    {
        try {
            $transaksi = DB::transaction(fn () => Transaksi::create([
                // desa_id di-set eksplisit dari pelaku (bukan mengandalkan auth
                // ambient di trait MilikDesa) supaya action tetap benar bila kelak
                // dipindah ke queue tanpa Auth::login — pola tenant-context T-9.
                'desa_id' => $pelaku->desa_id,
                'uuid' => $data['uuid'],
                'tahun_anggaran_id' => $data['tahun_anggaran_id'],
                'akun_id' => $data['akun_id'],
                'tanggal' => $data['tanggal'],
                'uraian' => $data['uraian'],
                'jumlah' => $data['jumlah'],
                'client_updated_at' => $data['client_updated_at'],
            ]));
        } catch (UniqueConstraintViolationException) {
            // Race: request lain membuat baris uuid ini setelah cek keberadaan
            // (temuan T-2 audit). Muat ulang dan proses sebagai re-sync idempoten
            // alih-alih menjatuhkan seluruh batch dengan 500.
            $baris = Transaksi::withoutGlobalScopes()->where('uuid', $data['uuid'])->firstOrFail();

            return $baris->desa_id === $pelaku->desa_id
                ? $this->perbaruiAtauTolak($pelaku, $baris, $data, $clientUpdatedAt)
                : $this->hasil($pelaku, $data['uuid'], null, HasilSinkronisasi::Ditolak, 'UUID sudah dipakai.');
        }

        return $this->hasil($pelaku, $data['uuid'], $transaksi->id, HasilSinkronisasi::Dibuat);
    }

    private function perbaruiAtauTolak(User $pelaku, Transaksi $existing, array $data, CarbonImmutable $clientUpdatedAt): array
    {
        // Locking: draft yang sudah masuk alur approval tidak boleh diedit offline.
        if ($existing->status !== StatusTransaksi::Draft) {
            return $this->hasil(
                $pelaku, $data['uuid'], $existing->id,
                HasilSinkronisasi::Terkunci,
                'Transaksi sudah '.$existing->status->label().' — perubahan offline diabaikan.',
            );
        }

        $tersimpan = $existing->client_updated_at !== null
            ? CarbonImmutable::parse($existing->client_updated_at)
            : null;

        // Idempoten: versi identik yang dikirim ulang.
        if ($tersimpan !== null && $clientUpdatedAt->equalTo($tersimpan)) {
            return $this->hasil($pelaku, $data['uuid'], $existing->id, HasilSinkronisasi::SudahTersinkron);
        }

        // Konflik: versi server lebih baru — kiriman lama ditolak dan dicatat.
        // Simpan ringkasan isi versi yang KALAH agar jejak audit lengkap
        // (siapa/kapan + apa yang ditolak), bukan hanya timestamp.
        if ($tersimpan !== null && $clientUpdatedAt->lessThan($tersimpan)) {
            $ditolak = sprintf(
                'uraian=%s; jumlah=%s; akun_id=%s',
                $data['uraian'], $data['jumlah'], $data['akun_id'],
            );

            return $this->hasil(
                $pelaku, $data['uuid'], $existing->id,
                HasilSinkronisasi::KonflikDitolak,
                'Versi server ('.$tersimpan->toIso8601String().') lebih baru dari kiriman ('
                    .$clientUpdatedAt->toIso8601String().'). Isi yang ditolak: '.$ditolak,
            );
        }

        // Versi klien lebih baru → menang.
        DB::transaction(fn () => $existing->update([
            'akun_id' => $data['akun_id'],
            'tanggal' => $data['tanggal'],
            'uraian' => $data['uraian'],
            'jumlah' => $data['jumlah'],
            'client_updated_at' => $data['client_updated_at'],
        ]));

        return $this->hasil($pelaku, $data['uuid'], $existing->id, HasilSinkronisasi::Diperbarui);
    }

    private function hasil(User $pelaku, ?string $uuid, ?int $transaksiId, HasilSinkronisasi $hasil, ?string $keterangan = null): array
    {
        // Selalu catat — termasuk item tak valid tanpa uuid (temuan T-5 audit):
        // setiap upaya sync meninggalkan jejak untuk pemeriksaan Inspektorat/BPKP.
        SinkronisasiLog::create([
            'desa_id' => $pelaku->desa_id,
            'user_id' => $pelaku->id,
            'transaksi_id' => $transaksiId,
            'uuid' => $uuid,
            'hasil' => $hasil,
            'keterangan' => $keterangan,
            'created_at' => now(),
        ]);

        return [
            'uuid' => $uuid,
            'hasil' => $hasil->value,
            'transaksi_id' => $transaksiId,
            'keterangan' => $keterangan,
        ];
    }
}
