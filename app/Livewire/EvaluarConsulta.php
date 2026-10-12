<?php

namespace App\Livewire;

use App\Models\AuditoriaHc;
use App\Models\ExpedienteConsulta;
use App\Models\PlanAccion;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\CalidadHistoriaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Auditoría manual de una consulta: parte de la evaluación automática, el auditor confirma o corrige
 * cada bloque con su observación y la cierra. Cerrada ya no se modifica; de ella salen los planes de acción.
 */
class EvaluarConsulta extends Component
{
    #[Locked]
    public ExpedienteConsulta $consulta;

    #[Locked]
    public ?int $auditoriaId = null;

    /** clave => ['ok' => bool, 'obs' => string] */
    public array $resultado = [];
    public string $observacion = '';

    // Plan de acción
    public string $hallazgo = '';
    public string $accion = '';
    public string $responsable = '';
    public string $fechaLimite = '';

    public function mount(ExpedienteConsulta $consulta): void
    {
        $this->consulta = $consulta;
        app(AuditoriaService::class)->registrar('ver', 'consulta', $consulta->id, $consulta->paciente_id, 'Auditoría de calidad de la HC');

        $borrador = AuditoriaHc::where('consulta_id', $consulta->id)->where('user_id', Auth::id())->where('estado', 'borrador')->latest('id')->first();
        if ($borrador) {
            $this->auditoriaId = $borrador->id;
            $this->resultado = $borrador->resultado;
            $this->observacion = (string) $borrador->observacion;
        } else {
            $auto = app(CalidadHistoriaService::class)->evaluar($consulta)['criterios'];
            $this->resultado = collect($auto)->map(fn ($c) => ['ok' => $c['ok'], 'obs' => ''])->all();
        }
        $this->fechaLimite = today()->addDays(30)->toDateString();
    }

    private function auditoriaAbierta(): ?AuditoriaHc
    {
        return $this->auditoriaId ? AuditoriaHc::find($this->auditoriaId) : null;
    }

    private function datos(): array
    {
        $this->validate([
            'observacion' => 'nullable|string|max:3000',
            'resultado.*.obs' => 'nullable|string|max:500',
        ]);

        // Solo las claves conocidas, normalizadas
        $resultado = collect(CalidadHistoriaService::CRITERIOS)
            ->map(fn ($c, $clave) => ['ok' => (bool) ($this->resultado[$clave]['ok'] ?? false), 'obs' => trim((string) ($this->resultado[$clave]['obs'] ?? ''))])
            ->all();

        return [
            'resultado' => $resultado,
            'puntaje' => CalidadHistoriaService::puntaje($resultado),
            'puntaje_automatico' => app(CalidadHistoriaService::class)->evaluar($this->consulta)['puntaje'],
            'observacion' => trim($this->observacion) ?: null,
        ];
    }

    public function guardar(bool $cerrar = false): void
    {
        $this->authorize('auditoria.gestionar');

        $auditoria = $this->auditoriaAbierta();
        if ($auditoria?->cerrada()) {
            $this->dispatch('notify', message: 'Esta evaluación ya está cerrada y no se puede modificar.', type: 'error');
            return;
        }

        $datos = $this->datos() + ['fecha' => today()];
        if ($cerrar) {
            $datos += ['estado' => 'cerrada', 'cerrada_at' => now()];
        }

        if ($auditoria) {
            $auditoria->update($datos);
        } else {
            $auditoria = AuditoriaHc::create($datos + ['consulta_id' => $this->consulta->id, 'user_id' => Auth::id()]);
            $this->auditoriaId = $auditoria->id;
        }

        if ($cerrar) {
            app(AuditoriaService::class)->registrar('auditar', 'consulta', $this->consulta->id, $this->consulta->paciente_id, "{$auditoria->numero()}: {$auditoria->puntaje}%");
            // La lista vuelve a la evaluación automática por si se audita de nuevo
            $this->auditoriaId = null;
            $this->observacion = '';
            $this->resultado = collect(app(CalidadHistoriaService::class)->evaluar($this->consulta)['criterios'])
                ->map(fn ($c) => ['ok' => $c['ok'], 'obs' => ''])->all();
            $this->hallazgo = collect($auditoria->resultado)->reject(fn ($r) => $r['ok'])
                ->map(fn ($r, $k) => CalidadHistoriaService::CRITERIOS[$k][0] . ($r['obs'] ? ": {$r['obs']}" : ''))
                ->implode('; ');
            $this->dispatch('notify', message: "Evaluación {$auditoria->numero()} cerrada con {$auditoria->puntaje}%.", type: 'success');
        } else {
            $this->dispatch('notify', message: 'Borrador guardado.', type: 'success');
        }
    }

    public function agregarPlan(int $auditoriaId): void
    {
        $this->authorize('auditoria.gestionar');

        $auditoria = AuditoriaHc::where('consulta_id', $this->consulta->id)->where('estado', 'cerrada')->findOrFail($auditoriaId);
        $this->validate([
            'hallazgo' => 'required|string|max:2000',
            'accion' => 'required|string|max:2000',
            'responsable' => 'required|exists:users,id',
            'fechaLimite' => 'required|date|after_or_equal:today',
        ], [], ['accion' => 'acción', 'fechaLimite' => 'fecha límite']);

        $plan = PlanAccion::create([
            'auditoria_hc_id' => $auditoria->id,
            'medico_id' => $this->consulta->medico_id,
            'hallazgo' => $this->hallazgo,
            'accion' => $this->accion,
            'responsable_user_id' => (int) $this->responsable,
            'fecha_limite' => $this->fechaLimite,
            'creado_por' => Auth::id(),
        ]);
        app(AuditoriaService::class)->registrar('crear', 'plan_accion', $plan->id, $this->consulta->paciente_id, "Plan de acción de {$auditoria->numero()}");

        $this->reset(['hallazgo', 'accion', 'responsable']);
        $this->dispatch('notify', message: 'Plan de acción registrado.', type: 'success');
    }

    public function render(CalidadHistoriaService $calidad)
    {
        $this->consulta->loadMissing('paciente', 'medico.user', 'especialidad');

        return view('livewire.evaluar-consulta', [
            'automatica' => $calidad->evaluar($this->consulta),
            'cerradas' => AuditoriaHc::with('auditor', 'planes.responsable')->where('consulta_id', $this->consulta->id)
                ->where('estado', 'cerrada')->latest('cerrada_at')->get(),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
