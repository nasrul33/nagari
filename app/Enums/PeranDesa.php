<?php

namespace App\Enums;

/**
 * Peran perangkat desa (spatie/laravel-permission role names).
 * Wewenang per peran: lihat .claude/skills/spp-spm-workflow/SKILL.md.
 */
enum PeranDesa: string
{
    case KepalaDesa = 'kepala_desa';
    case SekretarisDesa = 'sekretaris_desa';
    case KaurKeuangan = 'kaur_keuangan';
    case Bpd = 'bpd';

    public function label(): string
    {
        return match ($this) {
            self::KepalaDesa => 'Kepala Desa',
            self::SekretarisDesa => 'Sekretaris Desa',
            self::KaurKeuangan => 'Kaur Keuangan',
            self::Bpd => 'BPD',
        };
    }
}
