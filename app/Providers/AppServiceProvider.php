<?php

namespace App\Providers;

use App\Services\AuditoriaService;
use App\Services\MarcaService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rules\Password;
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

        // Claves personales robustas (AM 5216-A Art. 15)
        Password::defaults(fn () => Password::min(10)->letters()->numbers());

        // Trazabilidad de sesiones en el registro de auditoría
        $auditoria = fn () => $this->app->make(AuditoriaService::class);
        Event::listen(Login::class, fn (Login $e) => $auditoria()->registrar('login', 'sesion', null, null, $e->remember ? 'Con "recordarme"' : null, $e->user->getAuthIdentifier()));
        Event::listen(Logout::class, fn (Logout $e) => $e->user && $auditoria()->registrar('logout', 'sesion', null, null, null, $e->user->getAuthIdentifier()));
    }
}
