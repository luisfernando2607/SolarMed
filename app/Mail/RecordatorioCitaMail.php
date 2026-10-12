<?php

namespace App\Mail;

use App\Models\Cita;
use App\Services\SriConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecordatorioCitaMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Cita $cita)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recordatorio de su cita — ' . $this->cita->fecha_hora->translatedFormat('l d/m/Y H:i'),
        );
    }

    public function content(): Content
    {
        $config = app(SriConfigService::class);

        return new Content(markdown: 'mail.recordatorio-cita', with: [
            'cita' => $this->cita->loadMissing('paciente', 'medico.especialidad'),
            'direccion' => $this->cita->medico?->direccion_matriz ?: $config->get('sri_direccion'),
            'telefono' => $this->cita->medico?->telefono ?: $config->get('sri_telefono'),
        ]);
    }
}
