<?php

namespace App\Actions\Workflow;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Exceptions\TransisiDitolakException;
use App\Models\Transaksi;
use App\Models\TransaksiLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Kerangka transisi state alur SPP → SPM → pencairan.
 *
 * Invariant (lihat .claude/skills/spp-spm-workflow/SKILL.md):
 * - State tidak boleh dilompati — dijaga di sini, bukan di UI.
 * - Peran yang salah ditolak DAN tercatat di transaksi_logs.
 */
abstract class TransisiWorkflow
{
    /** Status transaksi saat handle() dipanggil — sumber kebenaran kolom dari_status di log. */
    private StatusTransaksi $statusAsal;

    abstract protected function dari(): StatusTransaksi;

    abstract protected function ke(): StatusTransaksi;

    abstract protected function peranBerwenang(): PeranDesa;

    /** Validasi tambahan spesifik transisi. Panggil $this->tolak() jika tidak sah. */
    protected function validasi(Transaksi $transaksi, User $pelaku, array $atribut): void {}

    /** Atribut transaksi yang ikut disimpan saat transisi berhasil. */
    protected function atributTersimpan(array $atribut): array
    {
        return [];
    }

    public function handle(Transaksi $transaksi, User $pelaku, array $atribut = []): Transaksi
    {
        $this->statusAsal = $transaksi->status;

        if ($transaksi->status !== $this->dari()) {
            $this->tolak($transaksi, $pelaku, sprintf(
                'State tidak boleh dilompati: transisi %s → %s tidak sah dari state %s.',
                $this->dari()->value, $this->ke()->value, $transaksi->status->value,
            ));
        }

        if (! $pelaku->hasRole($this->peranBerwenang()->value)) {
            $this->tolak($transaksi, $pelaku, sprintf(
                'Peran tidak berwenang: transisi %s → %s hanya boleh dilakukan %s.',
                $this->dari()->value, $this->ke()->value, $this->peranBerwenang()->label(),
            ));
        }

        $this->validasi($transaksi, $pelaku, $atribut);

        return DB::transaction(function () use ($transaksi, $pelaku, $atribut) {
            Transaksi::denganTransisiDiizinkan(function () use ($transaksi, $atribut) {
                $transaksi
                    ->fill($this->atributTersimpan($atribut))
                    ->forceFill(['status' => $this->ke()])
                    ->save();
            });

            $this->catat($transaksi, $pelaku, berhasil: true);

            return $transaksi->refresh();
        });
    }

    protected function tolak(Transaksi $transaksi, User $pelaku, string $alasan): never
    {
        $this->catat($transaksi, $pelaku, berhasil: false, alasan: $alasan);

        throw new TransisiDitolakException($alasan);
    }

    private function catat(Transaksi $transaksi, User $pelaku, bool $berhasil, ?string $alasan = null): void
    {
        TransaksiLog::create([
            'transaksi_id' => $transaksi->id,
            'user_id' => $pelaku->id,
            'dari_status' => $this->statusAsal->value,
            'ke_status' => $this->ke()->value,
            'berhasil' => $berhasil,
            'alasan' => $alasan,
            'created_at' => now(),
        ]);
    }
}
