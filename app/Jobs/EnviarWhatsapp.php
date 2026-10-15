<?php

namespace App\Jobs;

use App\Models\WhatsappMensaje;
use App\Services\WhatsappCloudService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envía un mensaje de WhatsApp ya registrado. Reintenta solo ante límites de envío, errores temporales de Meta
 * o fallas de conexión; cualquier otro error (plantilla, número, token) queda como fallido de inmediato.
 */
class EnviarWhatsapp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $mensajeId) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(WhatsappCloudService $wa): void
    {
        $m = WhatsappMensaje::find($this->mensajeId);
        if (!$m || $m->estado !== 'pendiente') {
            return;
        }

        if (!$wa->activo() || !$wa->configurado()) {
            $m->update(['estado' => 'fallido', 'error_codigo' => 'apagado', 'error_mensaje' => 'El envío por WhatsApp está desactivado o sin configurar.']);
            return;
        }

        $p = $m->parametros ?? [];
        $r = $wa->enviarPlantilla($m->telefono, $m->plantilla, (string) app(\App\Services\AjustesService::class)->get('whatsapp.idioma'),
            $p['cuerpo'] ?? [], $p['botones'] ?? []);
        $m->increment('intentos');

        if ($r['ok']) {
            $m->update(['wamid' => $r['wamid'], 'error_codigo' => null, 'error_mensaje' => null]);
            $m->avanzarEstado('enviado');
            return;
        }

        $m->update(['error_codigo' => $r['codigo'], 'error_mensaje' => mb_substr((string) $r['error'], 0, 255)]);

        if ($r['reintentar'] && $this->attempts() < $this->tries) {
            $this->release($this->backoff()[$this->attempts() - 1] ?? 900);
            return;
        }

        $m->avanzarEstado('fallido', null, $r['codigo'], $r['error']);
    }

    public function failed(?\Throwable $e): void
    {
        WhatsappMensaje::whereKey($this->mensajeId)->where('estado', 'pendiente')
            ->update(['estado' => 'fallido', 'error_mensaje' => 'No se pudo enviar el mensaje.']);
    }
}
