<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Si el rol del usuario exige verificación en dos pasos y aún no la activó, solo le permite ir a su perfil
 * para configurarla (o cerrar sesión).
 */
class ExigirDosFactores
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->requiereDosFactores() && !$user->tieneDosFactores()
            && !$request->routeIs('profile', 'logout', 'cuenta.*', '*livewire.*', 'verification.*', 'password.*')) {
            return redirect()->route('cuenta.dos-factores');
        }

        return $next($request);
    }
}
