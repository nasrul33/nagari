<?php

namespace App\Actions\Workflow;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;
use App\Models\Transaksi;
use App\Models\User;

/** Kaur Keuangan mengajukan SPP (dilampiri RKD & bukti pengeluaran). */
class AjukanSpp extends TransisiWorkflow
{
    protected function dari(): StatusTransaksi
    {
        return StatusTransaksi::Draft;
    }

    protected function ke(): StatusTransaksi
    {
        return StatusTransaksi::SppDiajukan;
    }

    protected function peranBerwenang(): PeranDesa
    {
        return PeranDesa::KaurKeuangan;
    }

    protected function validasi(Transaksi $transaksi, User $pelaku, array $atribut): void
    {
        if (blank($atribut['nomor_spp'] ?? null)) {
            $this->tolak($transaksi, $pelaku, 'Pengajuan SPP wajib menyertakan nomor SPP.');
        }
    }

    protected function atributTersimpan(array $atribut): array
    {
        return ['nomor_spp' => $atribut['nomor_spp']];
    }
}
