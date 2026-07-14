<?php

namespace App\Actions\Workflow;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;

/** Kaur Keuangan menutup transaksi setelah seluruh dokumen pendukung lengkap. */
class SelesaikanTransaksi extends TransisiWorkflow
{
    protected function dari(): StatusTransaksi
    {
        return StatusTransaksi::Dicairkan;
    }

    protected function ke(): StatusTransaksi
    {
        return StatusTransaksi::Selesai;
    }

    protected function peranBerwenang(): PeranDesa
    {
        return PeranDesa::KaurKeuangan;
    }
}
