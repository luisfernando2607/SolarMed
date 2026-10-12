<?php

namespace App\Livewire;

use App\Models\PlanAccion;
use App\Services\AuditoriaService;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Seguimiento de los planes de acción de la auditoría de historias clínicas.
 * Ciclo: pendiente → en proceso → cumplido (con evidencia). Un plan cumplido queda cerrado.
 */
class PlanesAccion extends Component
{
    use WithPagination;

    public string $filtro = 'abiertos';

    #[Locked]
    public ?int $editandoId = null;
    public string $estado = '';
    public string $evidencia = '';

    public function updatedFiltro(): void
    {
        $this->resetPage();
    }

    public function editar(int $id): void
    {
        $this->authorize('auditoria.gestionar');
        $plan = PlanAccion::findOrFail($id);
        if ($plan->estado === 'cumplido') {
            return;
        }
        $this->editandoId = $plan->id;
        $this->estado = $plan->estado;
        $this->evidencia = (string) $plan->evidencia;
        $this->resetErrorBag();
    }

    public function cancelar(): void
    {
        $this->reset(['editandoId', 'estado', 'evidencia']);
    }

    public function guardar(): void
    {
        $this->authorize('auditoria.gestionar');
        $plan = PlanAccion::findOrFail($this->editandoId);
        if ($plan->estado === 'cumplido') {
            $this->dispatch('notify', message: 'El plan ya está cumplido y cerrado.', type: 'error');
            $this->cancelar();
            return;
        }

        $this->validate([
            'estado' => 'required|in:' . implode(',', array_keys(PlanAccion::ESTADOS)),
            'evidencia' => 'required_if:estado,cumplido|nullable|string|max:3000',
        ], ['evidencia.required_if' => 'Para marcarlo como cumplido describa la evidencia.']);

        $plan->update([
            'estado' => $this->estado,
            'evidencia' => trim($this->evidencia) ?: null,
            'cerrado_at' => $this->estado === 'cumplido' ? now() : null,
        ]);
        app(AuditoriaService::class)->registrar('editar', 'plan_accion', $plan->id, $plan->auditoria?->consulta?->paciente_id, PlanAccion::ESTADOS[$plan->estado]);

        $this->cancelar();
        $this->dispatch('notify', message: 'Plan de acción actualizado.', type: 'success');
    }

    public function render()
    {
        $planes = PlanAccion::with('responsable', 'medico', 'auditoria.consulta.paciente')
            ->when($this->filtro === 'abiertos', fn ($q) => $q->where('estado', '!=', 'cumplido'))
            ->when($this->filtro === 'vencidos', fn ($q) => $q->where('estado', '!=', 'cumplido')->whereDate('fecha_limite', '<', today()))
            ->when($this->filtro === 'cumplidos', fn ($q) => $q->where('estado', 'cumplido'))
            ->orderByRaw("estado = 'cumplido'")
            ->orderBy('fecha_limite')
            ->paginate(15);

        return view('livewire.planes-accion', [
            'planes' => $planes,
            'vencidos' => PlanAccion::where('estado', '!=', 'cumplido')->whereDate('fecha_limite', '<', today())->count(),
        ])->layout('layouts.app');
    }
}
