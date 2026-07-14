<?php

namespace App\Actions\Workflow;

use App\Enums\PeranDesa;
use App\Enums\StatusTransaksi;

/** Sekretaris Desa memverifikasi kelengkapan berkas SPP. */
class VerifikasiSpp extends TransisiWorkflow
{
    protected function dari(): StatusTransaksi
    {
        return StatusTransaksi::SppDiajukan;
    }

    protected function ke(): StatusTransaksi
    {
        return StatusTransaksi::Diverifikasi;
    }

    protected function peranBerwenang(): PeranDesa
    {
        return PeranDesa::SekretarisDesa;
    }
}
