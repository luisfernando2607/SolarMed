<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Models\NotaCredito;
use App\Services\ComprobanteSriService;
use App\Services\SriWebService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Lleva un comprobante ya firmado hasta su autorización: reenvía si el envío falló y consulta la
 * autorización hasta obtener respuesta. Si el SRI está caído o "EN PROCESO", reintenta con espera creciente.
 */
class AutorizarComprobanteSri implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 8;

    /** Unos 2 h 15 min en total; después lo retoma la tarea programada sri:reintentar */
    public function backoff(): array
    {
        return [30, 60, 120, 300, 600, 1800, 3600];
    }

    public function __construct(public Factura|NotaCredito $comprobante)
    {
    }

    public function uniqueId(): string
    {
        return class_basename($this->comprobante) . '-' . $this->comprobante->id;
    }

    public function handle(ComprobanteSriService $servicio): void
    {
        $comprobante = $this->comprobante->refresh();

        if ($comprobante instanceof Factura && $comprobante->estado === 'anulada') {
            return;
        }

        if (in_array($comprobante->estado_sri, ['firmado', 'error_envio'], true)) {
            $envio = app(SriWebService::class)->enviar($comprobante);
            if ($envio['estado'] !== 'RECIBIDA') {
                return; // Devuelto por el SRI: requiere corrección manual, se muestra en la factura
            }
            $this->release(30);
            return;
        }

        if (!in_array($comprobante->estado_sri, ['enviado', 'error_autorizacion'], true)) {
            return; // Ya autorizado, no autorizado o fuera del flujo
        }

        $resultado = $servicio->autorizar($comprobante);

        if ($resultado['estado'] === 'EN PROCESO') {
            throw new \RuntimeException('El SRI aún procesa el comprobante ' . $this->uniqueId());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('No se obtuvo la autorización del SRI tras varios intentos; se reintentará más tarde.', [
            'comprobante' => $this->uniqueId(),
            'error' => $e->getMessage(),
        ]);
    }
}
