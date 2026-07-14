<?php

use App\Enums\LevelAkun;
use App\Models\Akun;
use Database\Seeders\CoaSeeder;

/*
 * Invariant COA per Permendagri 113/2014 & 20/2018 —
 * lihat .claude/skills/coa-desa/SKILL.md.
 */

beforeEach(fn () => $this->seed(CoaSeeder::class));

it('men-seed tepat 5 kategori akun level 1 sesuai skill coa-desa', function () {
    expect(Akun::count())->toBe(5);

    expect(Akun::orderBy('kode')->pluck('nama', 'kode')->all())->toBe([
        '1' => 'Aset',
        '2' => 'Kewajiban',
        '3' => 'Kekayaan Bersih',
        '4' => 'Pendapatan',
        '5' => 'Belanja',
    ]);

    Akun::all()->each(function (Akun $akun) {
        expect($akun->level)->toBe(LevelAkun::Akun)
            ->and($akun->is_locked)->toBeTrue()
            ->and($akun->parent_id)->toBeNull();
    });
});

it('seeder idempoten — dijalankan dua kali tidak menggandakan akun', function () {
    $this->seed(CoaSeeder::class);

    expect(Akun::count())->toBe(5);
});

it('menolak perubahan akun resmi yang terkunci', function () {
    $aset = Akun::where('kode', '1')->firstOrFail();

    expect(fn () => $aset->update(['nama' => 'Diubah']))
        ->toThrow(LogicException::class, 'tidak boleh diubah');
});

it('menolak penghapusan akun resmi yang terkunci', function () {
    $aset = Akun::where('kode', '1')->firstOrFail();

    expect(fn () => $aset->delete())
        ->toThrow(LogicException::class, 'tidak boleh dihapus');
});

it('menolak akun tanpa induk yang bukan level 1', function () {
    expect(fn () => Akun::create([
        'kode' => '9.9', 'nama' => 'Liar', 'level' => LevelAkun::Kelompok, 'is_locked' => false,
    ]))->toThrow(LogicException::class, 'level 1');
});

it('menolak level yang melompati struktur 5 level', function () {
    $pendapatan = Akun::where('kode', '4')->firstOrFail();

    expect(fn () => Akun::create([
        'parent_id' => $pendapatan->id,
        'kode' => '4.1.1',
        'nama' => 'Langsung Jenis',
        'level' => LevelAkun::Jenis, // melompati Kelompok
        'is_locked' => false,
    ]))->toThrow(LogicException::class, 'tidak boleh dilompati');
});

it('menolak kode anak yang tidak berprefiks kode induk', function () {
    $pendapatan = Akun::where('kode', '4')->firstOrFail();

    expect(fn () => Akun::create([
        'parent_id' => $pendapatan->id,
        'kode' => '5.1',
        'nama' => 'Prefiks Salah',
        'level' => LevelAkun::Kelompok,
        'is_locked' => false,
    ]))->toThrow(LogicException::class, 'berprefiks');
});

it('menerima anak dengan level dan prefiks kode yang benar', function () {
    $pendapatan = Akun::where('kode', '4')->firstOrFail();

    $kelompok = Akun::create([
        'parent_id' => $pendapatan->id,
        'kode' => '4.1',
        'nama' => 'Pendapatan Asli Desa',
        'level' => LevelAkun::Kelompok,
        'is_locked' => false,
    ]);

    expect($kelompok->exists)->toBeTrue()
        ->and($kelompok->parent->kode)->toBe('4');
});

it('menolak level di bawah Rincian Objek (maksimal 5 level)', function () {
    $parent = Akun::where('kode', '4')->firstOrFail();

    foreach ([
        ['4.1', LevelAkun::Kelompok],
        ['4.1.1', LevelAkun::Jenis],
        ['4.1.1.01', LevelAkun::Objek],
        ['4.1.1.01.01', LevelAkun::RincianObjek],
    ] as [$kode, $level]) {
        $parent = Akun::create([
            'parent_id' => $parent->id, 'kode' => $kode, 'nama' => $kode,
            'level' => $level, 'is_locked' => false,
        ]);
    }

    expect(fn () => Akun::create([
        'parent_id' => $parent->id,
        'kode' => '4.1.1.01.01.01',
        'nama' => 'Level 6 Ilegal',
        'level' => 6,
        'is_locked' => false,
    ]))->toThrow(ValueError::class); // level 6 tidak ada di enum LevelAkun
});
