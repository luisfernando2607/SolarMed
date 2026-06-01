<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

// Al final del método boot():
// RateLimiter::for('turno', function (Request $request) {
//     return Limit::perDay(config('clinica.turno_rate_limit'))->by($request->input('cedula'));
// });
