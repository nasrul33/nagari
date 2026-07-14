<?php

namespace App\Actions\Workflow;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Models\Transaksi;
use App\Models\User;

/**
 * Kaur Keuangan mencairkan dana — hanya setelah SPM terbit DAN
 * surat rekomendasi Camat diterima.
 */
class CairkanDana extends TransisiWorkflow
{
    protected function dari(): StatusTransaksi
    {
        return StatusTransaksi::SpmDiterbitkan;
    }

    protected function ke(): StatusTransaksi
    {
        return StatusTransaksi::Dicairkan;
    }

    protected function peranBerwenang(): PeranDesa
    {
        return PeranDesa::KaurKeuangan;
    }

    protected function validasi(Transaksi $transaksi, User $pelaku, array $atribut): void
    {
        if (blank($atribut['nomor_rekomendasi_camat'] ?? null)) {
            $this->tolak($transaksi, $pelaku, 'Pencairan wajib menyertakan nomor surat rekomendasi Camat.');
        }
    }

    protected function atributTersimpan(array $atribut): array
    {
        return ['nomor_rekomendasi_camat' => $atribut['nomor_rekomendasi_camat']];
    }
}
