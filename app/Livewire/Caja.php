<?php

namespace App\Livewire;

use App\Models\CierreCaja;
use App\Services\CajaService;
use Livewire\Component;
use Livewire\WithPagination;

/** Cierre de caja diario: período abierto, conteo de efectivo e historial de cierres */
class Caja extends Component
{
    use WithPagination;

    public $fondo_inicial = 0;
    public $contado_efectivo = '';
    public array $billetes = [];
    public string $observaciones = '';

    public ?int $reabrirId = null;
    public string $motivoReapertura = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAny(['caja.cerrar', 'facturas.ver']), 403);
        $this->billetes = array_fill_keys(array_keys(CierreCaja::DENOMINACIONES), '');
    }

    /** Al usar el contador de billetes y monedas, el total pasa al efectivo contado */
    public function updatedBilletes(): void
    {
        $total = 0;
        foreach ($this->billetes as $clave => $cantidad) {
            $total += max(0, (int) $cantidad) * CierreCaja::valorDenominacion((string) $clave);
        }
        $this->contado_efectivo = number_format($total, 2, '.', '');
    }

    public function cerrar(): void
    {
        $this->authorize('caja.cerrar');
        $this->validate([
            'fondo_inicial' => 'required|numeric|min:0|max:100000',
            'contado_efectivo' => 'required|numeric|min:0|max:1000000',
            'observaciones' => 'nullable|string|max:1000',
            'billetes.*' => 'nullable|integer|min:0|max:100000',
        ], [
            'contado_efectivo.required' => 'Ingrese el efectivo contado en caja.',
        ], ['fondo_inicial' => 'fondo inicial', 'contado_efectivo' => 'efectivo contado']);

        $denominaciones = array_filter(array_map('intval', $this->billetes));
        $cierre = app(CajaService::class)->cerrar(auth()->id(), (float) $this->fondo_inicial, (float) $this->contado_efectivo,
            $denominaciones, trim($this->observaciones));

        $this->reset('contado_efectivo', 'observaciones');
        $this->billetes = array_fill_keys(array_keys(CierreCaja::DENOMINACIONES), '');
        $this->resetPage();
        $this->dispatch('notify', message: $cierre->diferencia == 0
            ? 'Caja cerrada y cuadrada.'
            : sprintf('Caja cerrada con %s de $%.2f.', $cierre->diferencia > 0 ? 'sobrante' : 'faltante', abs($cierre->diferencia)),
            type: $cierre->diferencia == 0 ? 'success' : 'warning');
    }

    public function pedirReapertura(int $id): void
    {
        $this->authorize('configuracion.editar');
        $this->reabrirId = $id;
        $this->motivoReapertura = '';
    }

    public function reabrir(): void
    {
        $this->authorize('configuracion.editar');
        $this->validate(['motivoReapertura' => 'required|string|min:5|max:500'], [], ['motivoReapertura' => 'motivo']);
        app(CajaService::class)->reabrir(CierreCaja::findOrFail($this->reabrirId), auth()->id(), trim($this->motivoReapertura));
        $this->reset('reabrirId', 'motivoReapertura');
        $this->dispatch('notify', message: 'Cierre reabierto: sus facturas volvieron a la caja abierta.', type: 'success');
    }

    public function render()
    {
        $servicio = app(CajaService::class);

        return view('livewire.caja', [
            'periodo' => $servicio->periodoAbierto(),
            'ultimo' => $servicio->ultimoCierre(),
            'cierres' => CierreCaja::with('user:id,name', 'reabiertoPor:id,name')->latest('hasta')->latest('id')->paginate(10),
        ])->layout('layouts.app');
    }
}
