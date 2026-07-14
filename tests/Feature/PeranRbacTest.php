<?php

use App\Enums\PeranDesa;
use Database\Seeders\PeranSeeder;
use Spatie\Permission\Models\Role;

it('men-seed keempat peran perangkat desa', function () {
    $this->seed(PeranSeeder::class);

    expect(Role::pluck('name')->sort()->values()->all())->toBe([
        'bpd', 'kaur_keuangan', 'kepala_desa', 'sekretaris_desa',
    ]);
});

it('seeder peran idempoten', function () {
    $this->seed(PeranSeeder::class);
    $this->seed(PeranSeeder::class);

    expect(Role::count())->toBe(count(PeranDesa::cases()));
});

it('user hanya memegang peran yang di-assign', function () {
    $this->seed(PeranSeeder::class);

    $kaur = userDenganPeran(PeranDesa::KaurKeuangan);

    expect($kaur->hasRole(PeranDesa::KaurKeuangan->value))->toBeTrue()
        ->and($kaur->hasRole(PeranDesa::KepalaDesa->value))->toBeFalse()
        ->and($kaur->hasRole(PeranDesa::SekretarisDesa->value))->toBeFalse()
        ->and($kaur->hasRole(PeranDesa::Bpd->value))->toBeFalse();
});
