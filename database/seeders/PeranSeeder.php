<?php

namespace Database\Seeders;

use App\Enums\PeranDesa;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PeranSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PeranDesa::cases() as $peran) {
            Role::findOrCreate($peran->value);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
