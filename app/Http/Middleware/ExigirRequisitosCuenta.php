<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Antes de usar la aplicación cada persona debe tener una clave propia (AM 5216-A Art. 15) y haber aceptado
 * el acuerdo de confidencialidad (Disposición General Segunda).
 */
class ExigirRequisitosCuenta
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $request->routeIs('cuenta.*', 'logout', '*livewire.*')) {
            return $next($request);
        }

        if ($user->debe_cambiar_password) {
            return redirect()->route('cuenta.password');
        }

        if (!$user->confidencialidad_aceptada_at) {
            return redirect()->route('cuenta.confidencialidad');
        }

        return $next($request);
    }
}
