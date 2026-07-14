<?php

namespace App\Enums;

/**
 * Struktur 5 level bagan akun per Permendagri 20/2018.
 * Struktur ini baku secara nasional dan TIDAK BOLEH diubah aplikasi
 * (lihat .claude/skills/coa-desa/SKILL.md).
 */
enum LevelAkun: int
{
    case Akun = 1;
    case Kelompok = 2;
    case Jenis = 3;
    case Objek = 4;
    case RincianObjek = 5;

    public function label(): string
    {
        return match ($this) {
            self::Akun => 'Akun',
            self::Kelompok => 'Kelompok',
            self::Jenis => 'Jenis',
            self::Objek => 'Objek',
            self::RincianObjek => 'Rincian Objek',
        };
    }

    public function anak(): ?self
    {
        return self::tryFrom($this->value + 1);
    }
}
