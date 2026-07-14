<?php

namespace App\Enums;

/**
 * State machine alur approval SPP → SPM → pencairan.
 * Urutan state TIDAK BOLEH dilompati — invariant dijaga di level Action,
 * bukan hanya validasi UI (lihat .claude/skills/spp-spm-workflow/SKILL.md).
 */
enum StatusTransaksi: string
{
    case Draft = 'draft';
    case SppDiajukan = 'spp_diajukan';
    case Diverifikasi = 'diverifikasi';
    case SpmDiterbitkan = 'spm_diterbitkan';
    case Dicairkan = 'dicairkan';
    case Selesai = 'selesai';

    /** Satu-satunya state tujuan yang sah dari state ini. */
    public function berikutnya(): ?self
    {
        return match ($this) {
            self::Draft => self::SppDiajukan,
            self::SppDiajukan => self::Diverifikasi,
            self::Diverifikasi => self::SpmDiterbitkan,
            self::SpmDiterbitkan => self::Dicairkan,
            self::Dicairkan => self::Selesai,
            self::Selesai => null,
        };
    }

    public function bolehTransisiKe(self $tujuan): bool
    {
        return $this->berikutnya() === $tujuan;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::SppDiajukan => 'SPP Diajukan',
            self::Diverifikasi => 'Diverifikasi Sekdes',
            self::SpmDiterbitkan => 'SPM Diterbitkan',
            self::Dicairkan => 'Dicairkan',
            self::Selesai => 'Selesai',
        };
    }
}
