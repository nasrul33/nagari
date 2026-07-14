<?php

namespace App\Services\Sikd;

use App\Exceptions\SkemaSikdBelumTersediaException;
use App\Models\PengirimanSikd;

/** Implementasi penjaga — aktif sampai skema resmi Kemenkeu/DJPK didapat. */
class PenyusunPayloadBelumTersedia implements PenyusunPayloadSikd
{
    public function susun(PengirimanSikd $pengiriman): mixed
    {
        throw SkemaSikdBelumTersediaException::buat();
    }
}
