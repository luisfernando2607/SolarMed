<?php

namespace App\Livewire;

use App\Models\IncidenteSeguridad;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/** Registro de vulneraciones de seguridad de datos personales (LOPDP: notificar en máximo 5 días) */
class IncidentesSeguridad extends Component
{
    use WithPagination;

    public bool $mostrarForm = false;
    #[Locked]
    public ?int $editId = null;

    public string $fecha_deteccion = '';
    public string $descripcion = '';
    public string $datos_afectados = '';
    public $pacientes_afectados = 0;
    public string $severidad = 'media';
    public bool $requiere_notificacion = true;
    public string $medidas = '';
    public string $notificado_autoridad_at = '';
    public string $estado = 'abierto';

    public function nuevo(): void
    {
        $this->authorize('auditoria.gestionar');
        $this->reset(['editId', 'descripcion', 'datos_afectados', 'pacientes_afectados', 'severidad', 'requiere_notificacion', 'medidas', 'notificado_autoridad_at', 'estado']);
        $this->fecha_deteccion = now()->format('Y-m-d\TH:i');
        $this->resetErrorBag();
        $this->mostrarForm = true;
    }

    public function editar(int $id): void
    {
        $this->authorize('auditoria.gestionar');
        $i = IncidenteSeguridad::findOrFail($id);
        $this->editId = $i->id;
        $this->fecha_deteccion = $i->fecha_deteccion->format('Y-m-d\TH:i');
        $this->descripcion = $i->descripcion;
        $this->datos_afectados = $i->datos_afectados;
        $this->pacientes_afectados = $i->pacientes_afectados;
        $this->severidad = $i->severidad;
        $this->requiere_notificacion = $i->requiere_notificacion;
        $this->medidas = (string) $i->medidas;
        $this->notificado_autoridad_at = $i->notificado_autoridad_at?->format('Y-m-d\TH:i') ?? '';
        $this->estado = $i->estado;
        $this->resetErrorBag();
        $this->mostrarForm = true;
    }

    public function guardar(): void
    {
        $this->authorize('auditoria.gestionar');
        $this->validate([
            'fecha_deteccion' => 'required|date|before_or_equal:now',
            'descripcion' => 'required|string|max:5000',
            'datos_afectados' => 'required|string|max:2000',
            'pacientes_afectados' => 'required|integer|min:0|max:1000000',
            'severidad' => ['required', Rule::in(array_keys(IncidenteSeguridad::SEVERIDADES))],
            'requiere_notificacion' => 'boolean',
            'medidas' => 'nullable|string|max:5000',
            'notificado_autoridad_at' => 'nullable|date|after_or_equal:fecha_deteccion',
            'estado' => ['required', Rule::in(array_keys(IncidenteSeguridad::ESTADOS))],
        ], [
            'descripcion.required' => 'Describa qué ocurrió.',
            'datos_afectados.required' => 'Indique qué datos se vieron comprometidos.',
            'notificado_autoridad_at.after_or_equal' => 'La notificación no puede ser anterior a la detección.',
        ]);

        $notificado = $this->requiere_notificacion && $this->notificado_autoridad_at !== '' ? Carbon::parse($this->notificado_autoridad_at) : null;
        $datos = [
            'fecha_deteccion' => Carbon::parse($this->fecha_deteccion),
            'descripcion' => $this->descripcion,
            'datos_afectados' => $this->datos_afectados,
            'pacientes_afectados' => (int) $this->pacientes_afectados,
            'severidad' => $this->severidad,
            'requiere_notificacion' => $this->requiere_notificacion,
            'medidas' => $this->medidas ?: null,
            'notificado_autoridad_at' => $notificado,
            // Al registrar la notificación, un incidente abierto pasa a "notificado"
            'estado' => $notificado && $this->estado === 'abierto' ? 'notificado' : $this->estado,
        ];

        if ($this->editId) {
            $incidente = IncidenteSeguridad::findOrFail($this->editId);
            $incidente->update($datos);
            app(AuditoriaService::class)->registrar('editar', 'incidente', $incidente->id, null, "Incidente de seguridad ({$incidente->estado})");
        } else {
            $incidente = IncidenteSeguridad::create($datos + ['registrado_por' => Auth::id()]);
            app(AuditoriaService::class)->registrar('crear', 'incidente', $incidente->id, null, "Incidente de seguridad, severidad {$incidente->severidad}");
        }

        $this->mostrarForm = false;
        $this->dispatch('notify', message: 'Incidente guardado.', type: 'success');
    }

    public function render()
    {
        $porNotificar = IncidenteSeguridad::where('requiere_notificacion', true)->whereNull('notificado_autoridad_at');

        return view('livewire.incidentes-seguridad', [
            'incidentes' => IncidenteSeguridad::with('registrador')->latest('fecha_deteccion')->paginate(20),
            'resumen' => [
                'abiertos' => IncidenteSeguridad::where('estado', '!=', 'cerrado')->count(),
                'por_notificar' => (clone $porNotificar)->count(),
                'vencidos' => (clone $porNotificar)->where('fecha_deteccion', '<', now()->subDays(IncidenteSeguridad::DIAS_NOTIFICACION))->count(),
            ],
        ])->layout('layouts.app');
    }
}
