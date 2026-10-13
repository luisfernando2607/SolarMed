<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Models\Embarazo;
use App\Models\ExpedienteConsulta;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\Turno;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Inicio de médicos, secretaría y enfermería: indicadores del día según el rol y la agenda de hoy. */
class InicioPersonal extends Component
{
    public function mount(): void
    {
        if (Auth::user()->can('configuracion.editar')) {
            $this->redirectRoute('admin.dashboard');
        }
    }

    public function render()
    {
        $user = Auth::user();
        $medico = Medico::where('user_id', $user->id)->first();

        $citasHoy = Cita::with('paciente', 'medico', 'especialidad')
            ->whereDate('fecha_hora', today())
            ->whereIn('estado', ['pendiente', 'confirmada', 'completada'])
            ->when($medico, fn ($q) => $q->where('medico_id', $medico->id))
            ->orderBy('fecha_hora')
            ->get();

        $esperando = Turno::with('especialidad', 'paciente')
            ->whereDate('fecha', today())->where('estado', 'esperando')
            ->when($medico, fn ($q) => $q->where('especialidad_id', $medico->especialidad_id))
            ->orderBy('hora_registro')->get();

        $kpis = [];
        if ($medico) {
            $kpis[] = ['titulo' => 'Mis citas de hoy', 'valor' => $citasHoy->whereIn('estado', ['pendiente', 'confirmada'])->count(), 'detalle' => $citasHoy->where('estado', 'completada')->count() . ' ya atendidas', 'ruta' => 'agenda'];
            $kpis[] = ['titulo' => 'En sala de espera', 'valor' => $esperando->count(), 'detalle' => $medico->especialidad?->nombre, 'ruta' => 'sala-espera'];
            $kpis[] = ['titulo' => 'Mis consultas del mes', 'valor' => ExpedienteConsulta::where('medico_id', $medico->id)->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'detalle' => ExpedienteConsulta::where('medico_id', $medico->id)->whereDate('fecha', today())->count() . ' hoy', 'ruta' => 'expedientes'];
            $mias = \App\Models\Encuesta::respondidas()->where('medico_id', $medico->id)->where('respondida_at', '>=', now()->subDays(90))->get();
            if ($mias->isNotEmpty()) {
                $kpis[] = ['titulo' => 'Satisfacción de mis pacientes', 'valor' => round($mias->map->promedio()->filter()->avg(), 1) . ' / 5',
                    'detalle' => $mias->count() . ' ' . ($mias->count() === 1 ? 'respuesta' : 'respuestas') . ' en 90 días', 'ruta' => null];
            }
            $kpis[] = ['titulo' => 'Embarazos en control', 'valor' => Embarazo::where('estado', 'activo')->where('medico_id', $medico->id)->count(), 'detalle' => 'Activos a mi cargo', 'ruta' => 'pacientes'];
        } else {
            $turnosHoy = Turno::whereDate('fecha', today());
            $kpis[] = ['titulo' => 'Turnos de hoy', 'valor' => (clone $turnosHoy)->count(), 'detalle' => (clone $turnosHoy)->where('estado', 'completado')->count() . ' completados', 'ruta' => 'sala-espera'];
            $kpis[] = ['titulo' => 'En sala de espera', 'valor' => $esperando->count(), 'detalle' => (clone $turnosHoy)->where('estado', 'en_atencion')->count() . ' en atención', 'ruta' => 'sala-espera'];
            $kpis[] = ['titulo' => 'Citas de hoy', 'valor' => $citasHoy->whereIn('estado', ['pendiente', 'confirmada'])->count(), 'detalle' => $citasHoy->where('estado', 'pendiente')->count() . ' sin confirmar', 'ruta' => 'agenda'];
            if ($user->can('preparacion.registrar')) {
                $sinSignos = Turno::whereDate('fecha', today())->where('estado', 'esperando')->whereNotNull('paciente_id')->doesntHave('preparacion')->count();
                $kpis[] = ['titulo' => 'Por tomar signos', 'valor' => $sinSignos, 'detalle' => 'Pacientes en espera sin preparación', 'ruta' => 'sala-espera'];
            }
            if ($user->can('facturas.ver')) {
                $facturasHoy = Factura::whereDate('fecha', today())->where('estado', '!=', 'anulada');
                $kpis[] = ['titulo' => 'Cobrado hoy', 'valor' => '$' . number_format((float) (clone $facturasHoy)->sum('total'), 2), 'detalle' => (clone $facturasHoy)->count() . ' facturas', 'ruta' => 'facturas'];
            }
        }

        return view('livewire.inicio-personal', [
            'medico' => $medico,
            'kpis' => $kpis,
            'citasHoy' => $citasHoy,
            'esperando' => $esperando->take(6),
            'totalEsperando' => $esperando->count(),
        ])->layout('layouts.app');
    }
}
