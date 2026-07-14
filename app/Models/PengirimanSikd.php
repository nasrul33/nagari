<?php

namespace App\Models;

use App\Enums\KategoriDataSikd;
use App\Enums\StatusPengirimanSikd;
use App\Models\Concerns\MilikDesa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengirimanSikd extends Model
{
    use MilikDesa;

    protected $fillable = [
        'desa_id', 'tahun_anggaran_id', 'kategori', 'jalur', 'status',
        'percobaan', 'pesan_gagal', 'terkirim_pada',
    ];

    protected function casts(): array
    {
        return [
            'kategori' => KategoriDataSikd::class,
            'status' => StatusPengirimanSikd::class,
            'terkirim_pada' => 'datetime',
        ];
    }

    public function tahunAnggaran(): BelongsTo
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
}
