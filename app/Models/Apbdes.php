<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apbdes extends Model
{
    use HasFactory;

    protected $table = 'apbdes';

    protected $fillable = ['tahun_anggaran_id', 'akun_id', 'uraian', 'jumlah_anggaran'];

    protected function casts(): array
    {
        return ['jumlah_anggaran' => 'decimal:2'];
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
