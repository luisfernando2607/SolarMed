<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Factura;
use App\Models\Paciente;
use App\Models\ExpedienteConsulta;
use App\Models\ServicioTarifario;
use App\Services\FacturaService;
use Illuminate\Support\Facades\Auth;

class CrearFactura extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public ?Paciente $selectedPaciente = null;
    public $serviciosDisponibles = [];
    public $items = [];
    public $descuento = 0;
    public $forma_pago = 'efectivo';
    public $referencia_pago;
    public $observaciones;

    protected function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.servicio_id' => 'required|integer|exists:servicios_tarifario,id',
            'items.*.descripcion' => 'required|string|max:200',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'forma_pago' => 'required|in:efectivo,transferencia,tarjeta',
            'referencia_pago' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public function seleccionarPaciente(Paciente $paciente): void
    {
        $this->selectedPaciente = $paciente;
        $this->items = [];
        $this->descuento = 0;

        $this->serviciosDisponibles = ServicioTarifario::where('activo', true)
            ->orderBy('nombre')
            ->get();

        $this->autoDetectarServicios($paciente);
    }

    public function resetPaciente(): void
    {
        $this->selectedPaciente = null;
        $this->serviciosDisponibles = [];
        $this->items = [];
        $this->descuento = 0;
        $this->forma_pago = 'efectivo';
        $this->referencia_pago = null;
        $this->observaciones = null;
    }

    protected function autoDetectarServicios(Paciente $paciente): void
    {
        $turno = $paciente->turnos()
            ->whereIn('estado', ['completado', 'en_atencion'])
            ->latest()
            ->first();

        if (!$turno) return;

        $consulta = ExpedienteConsulta::where('turno_id', $turno->id)->first();
        if (!$consulta) return;

        $mapa = [
            'general' => 'consulta general',
            'ginecologica' => 'consulta ginecológica',
            'control_prenatal' => 'control prenatal',
        ];

        $nombreBuscado = $mapa[$consulta->tipo_consulta] ?? null;
        if ($nombreBuscado) {
            $servicio = $this->serviciosDisponibles->first(fn($s) =>
                mb_strtolower($s->nombre) === $nombreBuscado
            );
            if ($servicio) {
                $this->items[] = [
                    'servicio_id' => $servicio->id,
                    'descripcion' => $servicio->nombre,
                    'cantidad' => 1,
                    'precio_unitario' => (float) $servicio->precio,
                    'subtotal' => (float) $servicio->precio,
                ];
            }
        }

        $ecografia = $consulta->relationLoaded('ecografia') ? $consulta->ecografia : null;
        if (!$ecografia) {
            $ecografia = \App\Models\Ecografia::where('consulta_id', $consulta->id)->first();
        }
        if ($ecografia) {
            $servicioEco = $this->serviciosDisponibles->first(fn($s) =>
                str_contains(mb_strtolower($s->nombre), 'ecografía')
            );
            if ($servicioEco) {
                $this->items[] = [
                    'servicio_id' => $servicioEco->id,
                    'descripcion' => $servicioEco->nombre,
                    'cantidad' => 1,
                    'precio_unitario' => (float) $servicioEco->precio,
                    'subtotal' => (float) $servicioEco->precio,
                ];
            }
        }
    }

    public function agregarItem(int $servicioId): void
    {
        $servicio = ServicioTarifario::findOrFail($servicioId);

        foreach ($this->items as &$item) {
            if ($item['servicio_id'] === $servicioId) {
                $item['cantidad']++;
                $item['subtotal'] = $item['cantidad'] * $item['precio_unitario'];
                return;
            }
        }

        $this->items[] = [
            'servicio_id' => $servicio->id,
            'descripcion' => $servicio->nombre,
            'cantidad' => 1,
            'precio_unitario' => (float) $servicio->precio,
            'subtotal' => (float) $servicio->precio,
        ];
    }

    public function actualizarCantidad(int $index, int $cantidad): void
    {
        if ($cantidad < 1) return;
        $this->items[$index]['cantidad'] = $cantidad;
        $this->items[$index]['subtotal'] = $cantidad * $this->items[$index]['precio_unitario'];
    }

    public function quitarItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum('subtotal');
    }

    public function getTotalProperty(): float
    {
        return max(0, $this->subtotal - ($this->descuento ?? 0));
    }

    public function guardar(): void
    {
        $this->authorize('facturas.crear');
        $this->validate();

        $ultimoTurno = $this->selectedPaciente->turnos()
            ->whereIn('estado', ['completado', 'en_atencion'])
            ->latest()
            ->first();

        $medicoId = $ultimoTurno?->medico_id;
        $especialidadId = $ultimoTurno?->especialidad_id;

        if (!$especialidadId) {
            session()->flash('error', 'El paciente no tiene un turno con especialidad asignada.');
            return;
        }

        if (!$medicoId) {
            $consulta = ExpedienteConsulta::where('paciente_id', $this->selectedPaciente->id)
                ->latest()->first();
            $medicoId = $consulta?->medico_id;
        }

        if (!$medicoId) {
            $medico = \App\Models\Medico::where('user_id', Auth::id())->first();
            $medicoId = $medico?->id;
        }

        if (!$medicoId) {
            $medicoId = \App\Models\Medico::first()?->id;
        }

        $factura = app(FacturaService::class)->crear([
            'paciente_id' => $this->selectedPaciente->id,
            'medico_id' => $medicoId,
            'especialidad_id' => $especialidadId,
            'turno_id' => $ultimoTurno?->id,
            'user_id' => Auth::id(),
            'fecha' => now(),
            'descuento' => $this->descuento ?? 0,
            'forma_pago' => $this->forma_pago,
            'referencia_pago' => $this->referencia_pago,
            'observaciones' => $this->observaciones,
        ], $this->items);

        $this->dispatch('notify', message: "Factura {$factura->numero_factura} creada exitosamente.", type: 'success');
        $this->redirect(route('facturas.ver', $factura->id), navigate: true);
    }

    public function render()
    {
        $pacientes = collect();
        if (!$this->selectedPaciente) {
            $query = Paciente::query();
            if ($this->busqueda) {
                $query->where(function ($q) {
                    $q->where('nombres', 'like', "%{$this->busqueda}%")
                      ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
                      ->orWhere('cedula', 'like', "%{$this->busqueda}%");
                });
            }
            $pacientes = $query->orderBy('created_at', 'desc')->paginate(10);
        }

        return view('livewire.crear-factura', [
            'pacientes' => $pacientes,
        ])->layout('layouts.app');
    }
}
