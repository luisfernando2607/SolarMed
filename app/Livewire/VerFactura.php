<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Factura;
use App\Services\SriXmlService;
use App\Services\SriSignService;
use App\Services\SriWebService;
use App\Services\NotaCreditoService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VerFactura extends Component
{
    public Factura $factura;
    public bool $enviando = false;
    public bool $autorizando = false;
    public string $motivo_nota_credito = '';

    public function mount(Factura $factura): void
    {
        $this->factura = $factura->loadMissing('paciente', 'medico.user', 'user', 'items.servicio', 'especialidad', 'notaCredito');
    }

    /** Estados en los que el comprobante todavía no fue recibido por el SRI */
    public const ESTADOS_SIN_ENVIAR = [null, 'pendiente', 'xml_generado', 'firmado', 'rechazado', 'error_envio'];

    public function anular(): void
    {
        $this->authorize('facturas.anular');

        if (!in_array($this->factura->estado_sri, self::ESTADOS_SIN_ENVIAR, true)) {
            $this->dispatch('notify', message: 'No se puede anular una factura ya enviada al SRI.', type: 'error');
            return;
        }

        $this->factura->update(['estado' => 'anulada']);
        $this->factura->refresh();
        $this->dispatch('notify', message: 'Factura anulada.', type: 'success');
    }

    public function generarXml(): void
    {
        $this->authorize('facturas.crear');

        if (!in_array($this->factura->estado_sri, [null, 'pendiente', 'xml_generado', 'firmado', 'rechazado', 'no_autorizado'], true)) {
            $this->dispatch('notify', message: 'El comprobante ya fue recibido por el SRI; no se puede regenerar.', type: 'error');
            return;
        }

        try {
            if (!$this->factura->items || $this->factura->items->isEmpty()) {
                $this->dispatch('notify', message: 'La factura no tiene items. No se puede generar el XML.', type: 'error');
                return;
            }

            $result = app(SriXmlService::class)->generar($this->factura);
            $path = "sri/xml_enviado_{$this->factura->id}.xml";
            Storage::disk('private')->put($path, $result['xml']);

            $this->factura->updateQuietly([
                'xml_enviado_path' => $path,
                'xml_firmado_path' => null,
                'clave_acceso' => $result['clave_acceso'],
                'estado_sri' => 'xml_generado',
                'mensajes_sri' => null,
            ]);
            $this->factura->refresh();
            $this->dispatch('notify', message: 'XML generado exitosamente.', type: 'success');
        } catch (\Exception $e) {
            Log::error('Error generando XML SRI para factura ' . $this->factura->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', message: 'Error al generar XML: ' . $e->getMessage(), type: 'error');
        }
    }

    public function firmarXml(): void
    {
        $this->authorize('facturas.crear');

        if (!$this->factura->medico) {
            $this->dispatch('notify', message: 'La factura no tiene médico asignado.', type: 'error');
            return;
        }

        if (!$this->factura->xml_enviado_path) {
            $this->dispatch('notify', message: 'Primero debe generar el XML.', type: 'error');
            return;
        }

        if (!app(SriXmlService::class)->claveAccesoValida($this->factura->clave_acceso)) {
            $this->dispatch('notify', message: 'La clave de acceso de este XML no es válida. Use "Regenerar XML" antes de firmar.', type: 'error');
            return;
        }

        try {
            $xmlEnviado = Storage::disk('private')->get($this->factura->xml_enviado_path);
            $xmlFirmado = app(SriSignService::class)->firmar($xmlEnviado, $this->factura->medico);
            $path = "sri/xml_firmado_{$this->factura->id}.xml";
            Storage::disk('private')->put($path, $xmlFirmado);
            $this->factura->update([
                'xml_firmado_path' => $path,
                'estado_sri' => 'firmado',
            ]);
            $this->factura->refresh();
            $this->dispatch('notify', message: 'XML firmado exitosamente.', type: 'success');
        } catch (\Exception $e) {
            Log::error('Error firmando XML SRI factura ' . $this->factura->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', message: 'Error al firmar XML: ' . $e->getMessage(), type: 'error');
        }
    }

    public function enviarSri(): void
    {
        $this->authorize('facturas.crear');
        $this->enviando = true;

        try {
            $result = app(SriWebService::class)->enviar($this->factura);
            $this->factura->refresh();
            $msg = $result['estado'] === 'RECIBIDA'
                ? 'Comprobante recibido por el SRI. Ahora consulte la autorización.'
                : 'El SRI devolvió el comprobante: ' . $this->resumenMensajes($result['mensajes']);
            $this->dispatch('notify', message: $msg, type: $result['estado'] === 'RECIBIDA' ? 'success' : 'warning');
        } catch (\Exception $e) {
            Log::error('Error enviando al SRI factura ' . $this->factura->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', message: 'Error al enviar al SRI: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->enviando = false;
        }
    }

    public function autorizarSri(): void
    {
        $this->authorize('facturas.crear');
        $this->autorizando = true;

        try {
            $result = app(SriWebService::class)->autorizar($this->factura);
            $this->factura->refresh();
            [$msg, $tipo] = match ($result['estado']) {
                'AUTORIZADO' => ['Factura autorizada por el SRI. Nro: ' . ($result['numeroAutorizacion'] ?? ''), 'success'],
                'EN PROCESO' => ['El SRI aún está procesando el comprobante. Consulte nuevamente en unos minutos.', 'info'],
                default => ['Factura no autorizada: ' . $this->resumenMensajes($result['mensajes']), 'warning'],
            };
            $this->dispatch('notify', message: $msg, type: $tipo);
        } catch (\Exception $e) {
            Log::error('Error autorizando en SRI factura ' . $this->factura->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', message: 'Error al autorizar: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->autorizando = false;
        }
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
        $this->factura->refresh()->load('notaCredito');
        $this->procesarNotaCredito();
    }

    public function procesarNotaCredito(): void
    {
        $this->authorize('facturas.anular');
        $nota = $this->factura->notaCredito;
        if (!$nota) return;

        try {
            $result = app(NotaCreditoService::class)->procesar($nota);
            $msg = $result['estado'] === 'RECIBIDA'
                ? 'Nota de crédito recibida por el SRI. Ahora consulte la autorización.'
                : 'El SRI devolvió la nota de crédito: ' . $this->resumenMensajes($result['mensajes']);
            $this->dispatch('notify', message: $msg, type: $result['estado'] === 'RECIBIDA' ? 'success' : 'warning');
        } catch (\Exception $e) {
            Log::error('Error procesando nota de crédito ' . $nota->id . ': ' . $e->getMessage());
            $this->dispatch('notify', message: 'Nota de crédito: ' . $e->getMessage(), type: 'error');
        }

        $this->factura->refresh()->load('notaCredito');
    }

    public function autorizarNotaCredito(): void
    {
        $this->authorize('facturas.anular');
        $nota = $this->factura->notaCredito;
        if (!$nota) return;

        try {
            $result = app(NotaCreditoService::class)->autorizar($nota);
            [$msg, $tipo] = match ($result['estado']) {
                'AUTORIZADO' => ['Nota de crédito autorizada. La factura quedó anulada.', 'success'],
                'EN PROCESO' => ['El SRI aún está procesando la nota de crédito. Consulte nuevamente en unos minutos.', 'info'],
                default => ['Nota de crédito no autorizada: ' . $this->resumenMensajes($result['mensajes']), 'warning'],
            };
            $this->dispatch('notify', message: $msg, type: $tipo);
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Nota de crédito: ' . $e->getMessage(), type: 'error');
        }

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
