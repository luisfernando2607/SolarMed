<?php

namespace App\Services;

use App\Models\RegistroAcceso;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Registra accesos a datos clínicos, documentos y eventos de sesión. Un fallo al registrar nunca debe
 * impedir la atención: se reporta y la aplicación continúa.
 */
class AuditoriaService
{
    public const CLAVE_INTEGRIDAD = 'auditoria.integridad';

    /** $userId permite registrar eventos sin sesión iniciada (p. ej. un intento de ingreso fallido) */
    public function registrar(string $accion, string $recurso, ?int $recursoId = null, ?int $pacienteId = null, ?string $detalle = null, ?int $userId = null): void
    {
        try {
            $datos = [
                'user_id' => $userId ?? Auth::id(),
                'accion' => $accion,
                'recurso' => $recurso,
                'recurso_id' => $recursoId,
                'paciente_id' => $pacienteId,
                'detalle' => $detalle ? mb_substr($detalle, 0, 255) : null,
                'ip' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
                'created_at' => now()->startOfSecond(),
            ];

            // Un registro a la vez, para que cada uno selle exactamente al anterior
            Cache::lock('auditoria-cadena', 10)->block(5, function () use ($datos) {
                $anterior = RegistroAcceso::orderByDesc('id')->value('hash');
                RegistroAcceso::create($datos + ['hash' => RegistroAcceso::calcularHash($anterior, $datos)]);
            });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Recalcula la cadena completa. Devuelve el resultado y lo guarda para mostrarlo en la pantalla de auditoría.
     *
     * @return array{integro: bool, total: int, primer_error: ?int, verificado_at: string}
     */
    public function verificarIntegridad(): array
    {
        $anterior = null;
        $total = 0;
        $primerError = null;

        RegistroAcceso::query()->orderBy('id')->chunk(1000, function ($registros) use (&$anterior, &$total, &$primerError) {
            foreach ($registros as $r) {
                $total++;
                $esperado = RegistroAcceso::calcularHash($anterior, $r->getAttributes());
                if (!hash_equals($esperado, (string) $r->hash)) {
                    $primerError = $r->id;
                    return false;
                }
                $anterior = $r->hash;
            }
        });

        $resultado = [
            'integro' => $primerError === null,
            'total' => $total,
            'primer_error' => $primerError,
            'verificado_at' => now()->toIso8601String(),
        ];
        Cache::forever(self::CLAVE_INTEGRIDAD, $resultado);

        return $resultado;
    }
}
