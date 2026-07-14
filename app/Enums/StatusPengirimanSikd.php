<?php

namespace App\Enums;

enum StatusPengirimanSikd: string
{
    case Antri = 'antri';
    case Diproses = 'diproses';
    case Terkirim = 'terkirim';
    case Gagal = 'gagal';

    public function label(): string
    {
        return match ($this) {
            self::Antri => 'Antri',
            self::Diproses => 'Diproses',
            self::Terkirim => 'Terkirim',
            self::Gagal => 'Gagal',
        };
    }
}
