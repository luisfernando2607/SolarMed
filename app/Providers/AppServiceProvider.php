<?php

namespace App\Providers;

use App\Services\MarcaService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MarcaService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Marca de la clínica disponible como $marca en todas las vistas (incluye PDF y correos)
        View::share('marca', $this->app->make(MarcaService::class));
    }
}
