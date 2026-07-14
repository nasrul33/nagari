<?php

namespace App\Enums;

/**
 * 4 kategori data SIKD Teman Desa (lihat .claude/skills/sikd-teman-desa-integration/SKILL.md).
 * Daftar kategori terkonfirmasi dari dokumentasi publik Siskeudes 2.0.9;
 * FORMAT payload per kategori BELUM tersedia — jangan ditebak.
 */
enum KategoriDataSikd: string
{
    case DataUmumDesa = 'data_umum_desa';
    case Apbdes = 'apbdes';
    case Lra = 'lra';
    case DthRth = 'dth_rth';

    public function label(): string
    {
        return match ($this) {
            self::DataUmumDesa => 'Data Umum Desa',
            self::Apbdes => 'APBDES',
            self::Lra => 'LRA',
            self::DthRth => 'DTH/RTH',
        };
    }
}
