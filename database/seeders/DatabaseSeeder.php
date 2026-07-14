<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed dasar yang WAJIB ada di setiap lingkungan: peran RBAC + COA resmi.
     * Data demo pengembangan lokal: jalankan `php artisan db:seed --class=DemoSeeder`.
     */
    public function run(): void
    {
        $this->call([
            PeranSeeder::class,
            CoaSeeder::class,
        ]);
    }
}
