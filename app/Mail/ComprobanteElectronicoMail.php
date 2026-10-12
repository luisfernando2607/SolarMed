<?php

namespace App\Mail;

use App\Models\Factura;
use App\Models\NotaCredito;
use App\Services\RidePdfService;
use App\Services\SriConfigService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/** Entrega al paciente su comprobante electrónico autorizado: RIDE en PDF y XML autorizado. */
class ComprobanteElectronicoMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Factura|NotaCredito $comprobante)
    {
    }

    public function envelope(): Envelope
    {
        $emisor = app(SriConfigService::class)->emisor($this->comprobante->medico);

        return new Envelope(
            from: new Address(config('mail.from.address'), $emisor['nombre_comercial'] ?: ($emisor['razon_social'] ?: config('mail.from.name'))),
            subject: "{$this->tipo()} electrónica {$this->numero()} — " . ($emisor['nombre_comercial'] ?: $emisor['razon_social'] ?: config('app.name')),
        );
    }

    public function content(): Content
    {
        $factura = $this->comprobante instanceof NotaCredito ? $this->comprobante->factura : $this->comprobante;

        return new Content(markdown: 'mail.comprobante-electronico', with: [
            'tipo' => $this->tipo(),
            'numero' => $this->numero(),
            'paciente' => $factura->paciente,
            'total' => $this->comprobante instanceof NotaCredito ? $this->comprobante->valor_modificacion : $factura->total,
            'claveAcceso' => $this->comprobante->clave_acceso,
            'fechaAutorizacion' => $this->comprobante->fecha_autorizacion,
            'facturaModificada' => $this->comprobante instanceof NotaCredito ? $factura->numero_factura : null,
        ]);
    }

    public function attachments(): array
    {
        $pdf = $this->comprobante instanceof NotaCredito
            ? app(RidePdfService::class)->notaCredito($this->comprobante)
            : app(RidePdfService::class)->factura($this->comprobante);

        $base = strtolower(str_replace(' ', '_', $this->tipo())) . '_' . $this->numero();
        $adjuntos = [Attachment::fromData(fn () => $pdf->output(), "{$base}.pdf")->withMime('application/pdf')];

        if ($this->comprobante->xml_autorizado_path && Storage::disk('private')->exists($this->comprobante->xml_autorizado_path)) {
            $adjuntos[] = Attachment::fromStorageDisk('private', $this->comprobante->xml_autorizado_path)
                ->as("{$base}.xml")
                ->withMime('application/xml');
        }

        return $adjuntos;
    }

    private function tipo(): string
    {
        return $this->comprobante instanceof NotaCredito ? 'Nota de crédito' : 'Factura';
    }

    private function numero(): string
    {
        return $this->comprobante instanceof NotaCredito ? $this->comprobante->numero : $this->comprobante->numero_factura;
    }
}
