<?php

namespace App\Models;

use App\Enums\StatusTransaksi;
use App\Models\Concerns\MilikDesa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Transaksi extends Model implements AuditableContract
{
    use Auditable, HasFactory, MilikDesa;

    /** 'status' sengaja TIDAK fillable — perubahan state hanya lewat Action TransisiWorkflow. */
    protected $fillable = [
        'desa_id', 'tahun_anggaran_id', 'akun_id', 'apbdes_id',
        'tanggal', 'uraian', 'jumlah',
        'nomor_spp', 'nomor_spm', 'spm_ditandatangani_oleh', 'nomor_rekomendasi_camat',
    ];

    /** Hanya true selama TransisiWorkflow::handle() menyimpan transisi yang sah. */
    private static bool $transisiDiizinkan = false;

    protected static function booted(): void
    {
        static::updating(function (Transaksi $transaksi) {
            if ($transaksi->isDirty('status') && ! self::$transisiDiizinkan) {
                throw new \LogicException(
                    'Status transaksi hanya boleh diubah melalui Action alur SPP/SPM (TransisiWorkflow) — state machine tidak boleh dilewati.'
                );
            }
        });

        // Audit trail Inspektorat/BPKP: transaksi beserta jejaknya tidak boleh
        // dihapus. Pembatalan kelak dimodelkan sebagai state di workflow.
        static::deleting(function () {
            throw new \LogicException(
                'Transaksi tidak boleh dihapus — jejak transisi dan audit wajib dipertahankan.'
            );
        });
    }

    /** Dipakai TransisiWorkflow untuk menyimpan transisi state yang sudah tervalidasi. */
    public static function denganTransisiDiizinkan(callable $callback): mixed
    {
        self::$transisiDiizinkan = true;

        try {
            return $callback();
        } finally {
            self::$transisiDiizinkan = false;
        }
    }

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
            'status' => StatusTransaksi::class,
        ];
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }

    public function apbdes(): BelongsTo
    {
        return $this->belongsTo(Apbdes::class);
    }

    public function penandatanganSpm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spm_ditandatangani_oleh');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransaksiLog::class)->orderBy('created_at');
    }
}
