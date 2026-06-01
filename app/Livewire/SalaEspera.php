<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Turno;
use App\Models\Especialidad;
use App\Services\TurnoService;
use Carbon\Carbon;

class SalaEspera extends Component
{
    public $especialidades = [];
    public string $vista = 'activos';
    public string $fecha = '';
    public ?int $turnoSeleccionado = null;

    public function mount(): void
    {
        $this->especialidades = Especialidad::where('activo', true)->get();
        $this->fecha = Carbon::today()->format('Y-m-d');
    }

    public function updatedVista(): void
    {
        if ($this->vista === 'activos') {
            $this->fecha = Carbon::today()->format('Y-m-d');
        }
    }

    public function llamar(int $turnoId): void
    {
        $this->authorize('turnos.gestionar');
        app(TurnoService::class)->llamar(Turno::findOrFail($turnoId));
    }

    public function completar(int $turnoId): void
    {
        $this->authorize('turnos.gestionar');
        app(TurnoService::class)->completar(Turno::findOrFail($turnoId));
    }

    public function cancelar(int $turnoId): void
    {
        $this->authorize('turnos.gestionar');
        app(TurnoService::class)->cancelar(Turno::findOrFail($turnoId));
    }

    public function verDetalle(int $turnoId): void
    {
        $this->turnoSeleccionado = $turnoId;
    }

    public function cerrarDetalle(): void
    {
        $this->turnoSeleccionado = null;
    }

    public function getListeners(): array
    {
        return ['turno-registrado' => '$refresh'];
    }

    public function render()
    {
        $fechaCarbon = $this->fecha ? Carbon::parse($this->fecha) : Carbon::today();
        $colas = [];
        $historial = [];
        $detalle = null;

        if ($this->turnoSeleccionado) {
            $detalle = Turno::with(['paciente', 'especialidad'])
                ->find($this->turnoSeleccionado);
        }

        foreach ($this->especialidades as $esp) {
            $colas[$esp->id] = [
                'especialidad' => $esp,
                'en_atencion' => Turno::where('especialidad_id', $esp->id)
                    ->where('fecha', $fechaCarbon)
                    ->where('estado', 'en_atencion')
                    ->with('paciente')
                    ->first(),
                'esperando' => Turno::where('especialidad_id', $esp->id)
                    ->where('fecha', $fechaCarbon)
                    ->where('estado', 'esperando')
                    ->orderBy('numero_turno')
                    ->with('paciente')
                    ->get(),
            ];

            $historial[$esp->id] = [
                'especialidad' => $esp,
                'turnos' => Turno::where('especialidad_id', $esp->id)
                    ->where('fecha', $fechaCarbon)
                    ->orderBy('numero_turno')
                    ->with('paciente')
                    ->get(),
            ];
        }

        return view('livewire.sala-espera', [
            'colas' => $colas,
            'historial' => $historial,
            'detalle' => $detalle,
        ])->layout('layouts.app');
    }
}
