<?php

namespace App\Services;

use App\Models\RegistroAcceso;
use Illuminate\Support\Facades\Auth;

/**
 * Registra accesos a datos clínicos y documentos. Un fallo al registrar nunca debe impedir la atención:
 * se reporta y la aplicación continúa.
 */
class AuditoriaService
{
    public function registrar(string $accion, string $recurso, ?int $recursoId = null, ?int $pacienteId = null, ?string $detalle = null): void
    {
        try {
            RegistroAcceso::create([
                'user_id' => Auth::id(),
                'accion' => $accion,
                'recurso' => $recurso,
                'recurso_id' => $recursoId,
                'paciente_id' => $pacienteId,
                'detalle' => $detalle ? mb_substr($detalle, 0, 255) : null,
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
