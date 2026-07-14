<?php

namespace App\Models;

use App\Models\Concerns\MilikDesa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apbdes extends Model
{
    use HasFactory, MilikDesa;

    protected $table = 'apbdes';

    protected $fillable = ['desa_id', 'tahun_anggaran_id', 'akun_id', 'uraian', 'jumlah_anggaran'];

    protected function casts(): array
    {
        return ['jumlah_anggaran' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        // desa_id wajib konsisten dengan tahun anggaran induknya — mismatch
        // ditolak keras, bukan dibetulkan diam-diam (temuan T-3 audit M2).
        static::creating(function (Apbdes $apbdes) {
            $desaInduk = TahunAnggaran::withoutGlobalScopes()
                ->findOrFail($apbdes->tahun_anggaran_id)
                ->desa_id;

            if ($apbdes->desa_id === null) {
                $apbdes->desa_id = $desaInduk;

                return;
            }

            if ((int) $apbdes->desa_id !== (int) $desaInduk) {
                throw new \LogicException(
                    'Isolasi desa dilanggar: desa_id APBDes tidak sama dengan desa tahun anggaran induknya.'
                );
            }
        });
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function akun(): BelongsTo
    {
        return $this->belongsTo(Akun::class);
    }
}
