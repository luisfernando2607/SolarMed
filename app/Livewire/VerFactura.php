<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Factura;
use App\Services\SriXmlService;
use App\Services\SriSignService;
use App\Services\SriWebService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VerFactura extends Component
{
    public Factura $factura;
    public bool $enviando = false;
    public bool $autorizando = false;

    public function mount(Factura $factura): void
    {
        $this->factura = $factura->loadMissing('paciente', 'medico.user', 'user', 'items.servicio', 'especialidad');
    }

    public function anular(): void
    {
        $this->authorize('facturas.anular');

        if ($this->factura->estado_sri && $this->factura->estado_sri !== 'pendiente') {
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
                'clave_acceso' => $result['clave_acceso'],
                'estado_sri' => 'xml_generado',
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

        try {
            $xmlEnviado = Storage::disk('private')->get($this->factura->xml_enviado_path);
            $xmlFirmado = app(SriSignService::class)->firmar($xmlEnviado, $this->factura->medico);
            $path = "sri/xml_firmado_{$this->factura->id}.xml";
            Storage::disk('private')->put($path, $xmlFirmado);
            $this->factura->update([
                'xml_autorizado_path' => $path,
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
                ? 'Comprobante recibido por el SRI.'
                : 'El SRI rechazó el comprobante.';
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
            $msg = $result['estado'] === 'AUTORIZADO'
                ? 'Factura autorizada por el SRI. Nro: ' . ($result['numeroAutorizacion'] ?? '')
                : 'Factura no autorizada.';
            $this->dispatch('notify', message: $msg, type: $result['estado'] === 'AUTORIZADO' ? 'success' : 'warning');
        } catch (\Exception $e) {
            Log::error('Error autorizando en SRI factura ' . $this->factura->id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('notify', message: 'Error al autorizar: ' . $e->getMessage(), type: 'error');
        } finally {
            $this->autorizando = false;
        }
    }

    public function render()
    {
        return view('livewire.ver-factura')
            ->layout('layouts.app');
    }
}
