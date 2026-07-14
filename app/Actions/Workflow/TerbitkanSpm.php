<?php

namespace App\Actions\Workflow;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Models\Transaksi;
use App\Models\User;

/**
 * Sekretaris Desa menerbitkan SPM; SPM WAJIB ditandatangani Kepala Desa.
 * Dua wewenang direkam sekaligus: pelaku transisi = Sekdes (penerbit),
 * atribut 'penandatangan' = user ber-role Kepala Desa.
 */
class TerbitkanSpm extends TransisiWorkflow
{
    protected function dari(): StatusTransaksi
    {
        return StatusTransaksi::Diverifikasi;
    }

    protected function ke(): StatusTransaksi
    {
        return StatusTransaksi::SpmDiterbitkan;
    }

    protected function peranBerwenang(): PeranDesa
    {
        return PeranDesa::SekretarisDesa;
    }

    protected function validasi(Transaksi $transaksi, User $pelaku, array $atribut): void
    {
        if (blank($atribut['nomor_spm'] ?? null)) {
            $this->tolak($transaksi, $pelaku, 'Penerbitan SPM wajib menyertakan nomor SPM.');
        }

        $penandatangan = $atribut['penandatangan'] ?? null;

        if (! $penandatangan instanceof User || ! $penandatangan->hasRole(PeranDesa::KepalaDesa->value)) {
            $this->tolak($transaksi, $pelaku, 'SPM wajib ditandatangani user ber-peran Kepala Desa.');
        }
    }

    protected function atributTersimpan(array $atribut): array
    {
        return [
            'nomor_spm' => $atribut['nomor_spm'],
            'spm_ditandatangani_oleh' => $atribut['penandatangan']->id,
        ];
    }
}
