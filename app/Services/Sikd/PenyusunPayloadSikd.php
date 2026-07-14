<?php

namespace App\Services\Sikd;

use App\Exceptions\SkemaSikdBelumTersediaException;
use App\Models\PengirimanSikd;

/**
 * Boundary penyusunan payload SIKD Teman Desa.
 *
 * Implementasi nyata per kategori (Data Umum Desa, APBDES, LRA, DTH/RTH)
 * baru boleh ditulis SETELAH skema resmi Kemenkeu/DJPK masuk ke skill
 * sikd-teman-desa-integration. Sampai saat itu, binding default melempar
 * SkemaSikdBelumTersediaException.
 */
interface PenyusunPayloadSikd
{
    /**
     * Susun payload siap kirim untuk satu pengiriman.
     *
     * @throws SkemaSikdBelumTersediaException selama skema resmi belum tersedia
     */
    public function susun(PengirimanSikd $pengiriman): mixed;
}
