<?php

namespace App\Enums;

/**
 * Hasil per item saat sinkronisasi draft offline (M5).
 * Aturan resolusi konflik: locking berbasis state approval (CLAUDE.md #1).
 */
enum HasilSinkronisasi: string
{
    /** Draft baru dibuat di server. */
    case Dibuat = 'dibuat';

    /** Draft yang ada diperbarui (versi klien lebih baru). */
    case Diperbarui = 'diperbarui';

    /** UUID sudah pernah tersinkron dengan versi identik — no-op idempoten. */
    case SudahTersinkron = 'sudah_tersinkron';

    /** Konflik: versi server lebih baru, kiriman yang lebih lama ditolak. */
    case KonflikDitolak = 'konflik_ditolak';

    /** Draft sudah masuk alur SPP/SPM di server — terkunci dari edit offline. */
    case Terkunci = 'terkunci';

    /** Item tidak lolos validasi. */
    case Ditolak = 'ditolak';

    public function label(): string
    {
        return match ($this) {
            self::Dibuat => 'Dibuat',
            self::Diperbarui => 'Diperbarui',
            self::SudahTersinkron => 'Sudah tersinkron',
            self::KonflikDitolak => 'Konflik — versi server dipertahankan',
            self::Terkunci => 'Terkunci (sudah diajukan)',
            self::Ditolak => 'Ditolak (tidak valid)',
        };
    }

    /** True jika item boleh dihapus dari antrian klien (sudah selesai diproses). */
    public function selesai(): bool
    {
        return $this !== self::Ditolak;
    }
}
