<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Factura;
use App\Models\NotaCredito;
use App\Services\AuditoriaService;
use App\Services\ComprobanteSriService;
use App\Services\NotaCreditoService;
use Illuminate\Support\Facades\Log;

class VerFactura extends Component
{
    public Factura $factura;
    public string $motivo_nota_credito = '';

    /** Estados en los que el comprobante todavía no fue recibido por el SRI */
    public const ESTADOS_SIN_ENVIAR = [null, 'pendiente', 'xml_generado', 'firmado', 'rechazado'];

    public function mount(Factura $factura): void
    {
        $this->factura = $factura->loadMissing('paciente', 'medico.user', 'user', 'items.servicio', 'especialidad', 'notaCredito');
        app(AuditoriaService::class)->registrar('ver', 'factura', $factura->id, $factura->paciente_id, $factura->numero_factura);
    }

    public function anular(): void
    {
        $this->authorize('facturas.anular');

        if (!in_array($this->factura->estado_sri, self::ESTADOS_SIN_ENVIAR, true)) {
            $this->dispatch('notify', message: 'No se puede anular una factura ya enviada al SRI.', type: 'error');
            return;
        }

        $this->factura->update(['estado' => 'anulada']);
        $this->recargar();
        $this->dispatch('notify', message: 'Factura anulada.', type: 'success');
    }

    /** Genera, firma y envía la factura; la autorización se consulta sola en segundo plano. */
    public function emitirSri(): void
    {
        $this->authorize('facturas.crear');
        $this->procesar($this->factura, 'Factura');
    }

    /** Consulta manual de autorización (normalmente lo hace el proceso en segundo plano). */
    public function autorizarSri(): void
    {
        $this->authorize('facturas.crear');
        $this->consultarAutorizacion($this->factura, 'Factura');
    }

    public function emitirNotaCredito(): void
    {
        $this->authorize('facturas.anular');
        $this->validate(
            ['motivo_nota_credito' => 'required|string|min:5|max:300'],
            ['motivo_nota_credito.required' => 'Indique el motivo de la nota de crédito.']
        );

        app(NotaCreditoService::class)->crear($this->factura, $this->motivo_nota_credito, auth()->id());
        $this->motivo_nota_credito = '';
        $this->recargar();
        $this->procesarNotaCredito();
    }

    public function procesarNotaCredito(): void
    {
        $this->authorize('facturas.anular');
        if ($nota = $this->factura->notaCredito) {
            $this->procesar($nota, 'Nota de crédito');
        }
    }

    public function autorizarNotaCredito(): void
    {
        $this->authorize('facturas.anular');
        if ($nota = $this->factura->notaCredito) {
            $this->consultarAutorizacion($nota, 'Nota de crédito');
        }
    }

    /** Llamado por wire:poll mientras el SRI procesa el comprobante */
    public function actualizarEstado(): void
    {
        $this->recargar();
    }

    private function procesar(Factura|NotaCredito $comprobante, string $nombre): void
    {
        try {
            $result = app(ComprobanteSriService::class)->procesar($comprobante);
            if ($result['estado'] === 'RECIBIDA') {
                $this->dispatch('notify', message: "{$nombre} recibida por el SRI. La autorización se consultará automáticamente.", type: 'success');
            } else {
                $this->dispatch('notify', message: "El SRI devolvió el comprobante: " . $this->resumenMensajes($result['mensajes']), type: 'warning');
            }
        } catch (\Exception $e) {
            Log::error("Error emitiendo {$nombre} al SRI: " . $e->getMessage(), ['id' => $comprobante->id]);
            $this->dispatch('notify', message: "{$nombre}: " . $e->getMessage(), type: 'error');
        }

        $this->recargar();
    }

    private function consultarAutorizacion(Factura|NotaCredito $comprobante, string $nombre): void
    {
        try {
            $result = app(ComprobanteSriService::class)->autorizar($comprobante);
            [$msg, $tipo] = match ($result['estado']) {
                'AUTORIZADO' => ["{$nombre} autorizada por el SRI.", 'success'],
                'EN PROCESO' => ['El SRI aún está procesando el comprobante. Se seguirá consultando automáticamente.', 'info'],
                default => ["{$nombre} no autorizada: " . $this->resumenMensajes($result['mensajes']), 'warning'],
            };
            $this->dispatch('notify', message: $msg, type: $tipo);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: "{$nombre}: " . $e->getMessage(), type: 'error');
        }

        $this->recargar();
    }

    private function recargar(): void
    {
        $this->factura->refresh()->load('notaCredito');
    }

    private function resumenMensajes(array $mensajes): string
    {
        return collect($mensajes)->map(fn ($m) => trim("{$m['mensaje']} {$m['informacionAdicional']}"))->implode(' | ') ?: 'sin detalle';
    }

    public function render()
    {
        return view('livewire.ver-factura')
            ->layout('layouts.app');
    }
}
