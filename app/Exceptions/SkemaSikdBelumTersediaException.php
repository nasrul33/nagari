<?php

namespace App\Exceptions;

use RuntimeException;

class SkemaSikdBelumTersediaException extends RuntimeException
{
    public static function buat(): self
    {
        return new self(
            'Skema API/format data SIKD Teman Desa belum tersedia dari Kemenkeu/DJPK — '
            .'payload tidak boleh ditebak. Lihat .claude/skills/sikd-teman-desa-integration/SKILL.md.'
        );
    }
}
