<?php

namespace App\Livewire;

use App\Models\PlantillaConsentimiento;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Textos reutilizables por procedimiento para el consentimiento informado (los redactan los profesionales) */
class PlantillasConsentimiento extends Component
{
    public bool $editando = false;
    #[Locked]
    public ?int $editId = null;

    public $procedimiento, $en_que_consiste, $como_se_realiza, $duracion_estimada, $beneficios,
        $riesgos_frecuentes, $riesgos_graves, $alternativas, $manejo_posterior, $consecuencias_no_realizar;

    public function nueva(): void
    {
        $this->authorize('expediente.crear');
        $this->reset(PlantillaConsentimiento::CAMPOS);
        $this->editId = null;
        $this->editando = true;
    }

    public function editar(int $id): void
    {
        $this->authorize('expediente.crear');
        $plantilla = PlantillaConsentimiento::findOrFail($id);
        foreach (PlantillaConsentimiento::CAMPOS as $campo) {
            $this->{$campo} = $plantilla->{$campo};
        }
        $this->editId = $plantilla->id;
        $this->editando = true;
    }

    public function cancelar(): void
    {
        $this->editando = false;
        $this->editId = null;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $this->authorize('expediente.crear');
        $reglas = array_fill_keys(PlantillaConsentimiento::CAMPOS, 'nullable|string|max:5000');
        $this->validate(['procedimiento' => 'required|string|max:200', 'en_que_consiste' => 'required|string|max:5000',
            'duracion_estimada' => 'nullable|string|max:100'] + $reglas, [
            'procedimiento.required' => 'Escriba el nombre del procedimiento.',
            'en_que_consiste.required' => 'Explique en qué consiste el procedimiento.',
        ]);

        $datos = collect(PlantillaConsentimiento::CAMPOS)->mapWithKeys(fn ($c) => [$c => filled($this->{$c}) ? $this->{$c} : null])->all();
        if ($this->editId) {
            $plantilla = PlantillaConsentimiento::findOrFail($this->editId);
            $plantilla->update($datos);
            app(AuditoriaService::class)->registrar('editar', 'plantilla_consentimiento', $plantilla->id, null, $plantilla->procedimiento);
        } else {
            $plantilla = PlantillaConsentimiento::create($datos + ['activo' => true, 'created_by' => Auth::id()]);
            app(AuditoriaService::class)->registrar('crear', 'plantilla_consentimiento', $plantilla->id, null, $plantilla->procedimiento);
        }

        $this->cancelar();
        $this->dispatch('notify', message: 'Plantilla guardada.', type: 'success');
    }

    public function alternarActivo(int $id): void
    {
        $this->authorize('expediente.crear');
        $plantilla = PlantillaConsentimiento::findOrFail($id);
        $plantilla->update(['activo' => !$plantilla->activo]);
        app(AuditoriaService::class)->registrar('editar', 'plantilla_consentimiento', $plantilla->id, null,
            ($plantilla->activo ? 'Activada: ' : 'Desactivada: ') . $plantilla->procedimiento);
    }

    public function render()
    {
        return view('livewire.plantillas-consentimiento', [
            'plantillas' => PlantillaConsentimiento::with('autor')->orderByDesc('activo')->orderBy('procedimiento')->get(),
        ])->layout('layouts.app');
    }
}
