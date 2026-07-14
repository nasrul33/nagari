<?php

namespace App\Models;

use App\Enums\LevelAkun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Bagan akun (COA) — struktur 5 level baku per Permendagri 20/2018.
 * Baris seeded resmi (is_locked) tidak boleh diubah/dihapus dari aplikasi;
 * invariant dijaga di model, bukan hanya di UI.
 */
class Akun extends Model
{
    protected $fillable = ['parent_id', 'kode', 'nama', 'level', 'is_locked'];

    /**
     * Hanya true selama seeder resmi menambahkan kodefikasi (lihat CoaSeeder).
     * CATATAN deployment (temuan B-5 audit): flag statis proses-wide — aman
     * untuk PHP-FPM/queue worker sekuensial, TIDAK aman untuk Octane+Swoole
     * mode coroutine konkuren.
     */
    private static bool $penambahanDiizinkan = false;

    /** Dipakai seeder resmi COA untuk membuka penambahan kodefikasi sementara. */
    public static function denganPenambahanDiizinkan(callable $callback): mixed
    {
        self::$penambahanDiizinkan = true;

        try {
            return $callback();
        } finally {
            self::$penambahanDiizinkan = false;
        }
    }

    protected function casts(): array
    {
        return [
            'level' => LevelAkun::class,
            'is_locked' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Akun $akun) {
            // Kodefikasi COA di-seed, bukan dibuat custom per tenant (CLAUDE.md).
            // Flag eksplisit — bukan runningInConsole(), yang juga true di queue
            // worker (temuan DC-1 audit M2).
            if (! self::$penambahanDiizinkan) {
                throw new LogicException(
                    'Kodefikasi COA hanya boleh ditambahkan melalui seeder resmi, bukan dari aplikasi.'
                );
            }

            $akun->validasiStruktur();
        });

        static::updating(function (Akun $akun) {
            if ($akun->getOriginal('is_locked')) {
                throw new LogicException(
                    "Akun [{$akun->getOriginal('kode')}] adalah kodefikasi resmi Permendagri dan tidak boleh diubah."
                );
            }
            $akun->validasiStruktur();
        });

        static::deleting(function (Akun $akun) {
            if ($akun->is_locked) {
                throw new LogicException(
                    "Akun [{$akun->kode}] adalah kodefikasi resmi Permendagri dan tidak boleh dihapus."
                );
            }
        });
    }

    protected function validasiStruktur(): void
    {
        $level = $this->level instanceof LevelAkun ? $this->level : LevelAkun::from((int) $this->level);

        if ($this->parent_id === null) {
            if ($level !== LevelAkun::Akun) {
                throw new LogicException('Akun tanpa induk harus level 1 (Akun).');
            }

            return;
        }

        $parent = $this->parent()->firstOrFail();

        if ($parent->level->anak() !== $level) {
            throw new LogicException(
                "Level [{$level->label()}] tidak sah di bawah [{$parent->level->label()}] — struktur 5 level Permendagri 20/2018 tidak boleh dilompati."
            );
        }

        if (! str_starts_with($this->kode, $parent->kode.'.')) {
            throw new LogicException(
                "Kode [{$this->kode}] harus berprefiks kode induk [{$parent->kode}]."
            );
        }
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
