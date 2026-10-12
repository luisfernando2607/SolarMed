<?php

namespace App\Jobs;

use App\Mail\ComprobanteElectronicoMail;
use App\Models\Factura;
use App\Models\NotaCredito;
use App\Services\AuditoriaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/** Envía al paciente su comprobante autorizado y deja constancia de a qué correo y cuándo. */
class EnviarComprobantePorCorreo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function backoff(): array
    {
        return [60, 300, 1800];
    }

    public function __construct(public Factura|NotaCredito $comprobante, public ?string $correo = null)
    {
    }

    public function handle(): void
    {
        $comprobante = $this->comprobante->refresh();
        if ($comprobante->estado_sri !== 'autorizado') {
            return;
        }

        $paciente = $comprobante instanceof NotaCredito ? $comprobante->factura->paciente : $comprobante->paciente;
        $correo = $this->correo ?: $paciente?->email;
        if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Mail::to($correo)->send(new ComprobanteElectronicoMail($comprobante));

        $comprobante->update(['email_enviado_at' => now(), 'email_enviado_a' => $correo]);
        app(AuditoriaService::class)->registrar(
            'enviar',
            $comprobante instanceof NotaCredito ? 'nota_credito' : 'factura',
            $comprobante->id,
            $paciente?->id,
            "Comprobante enviado por correo a {$correo}"
        );
    }
}
