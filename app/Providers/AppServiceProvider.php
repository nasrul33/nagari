<?php

namespace App\Providers;

use App\Services\Sikd\PenyusunPayloadBelumTersedia;
use App\Services\Sikd\PenyusunPayloadSikd;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding penjaga: diganti implementasi nyata SETELAH skema resmi
        // SIKD Teman Desa masuk repo (skill sikd-teman-desa-integration).
        $this->app->bind(
            PenyusunPayloadSikd::class,
            PenyusunPayloadBelumTersedia::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
