<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Locked;
use App\Models\Turno;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Livewire\Traits\WithCie10Autocomplete;
use App\Livewire\Traits\WithConsultaForm;
use Illuminate\Support\Facades\Auth;

class CrearConsulta extends Component
{
    use WithCie10Autocomplete, WithConsultaForm;

    public Turno $turno;
    #[Locked]
    public $paciente_id;

    public function mount(Turno $turno): void
    {
        $this->turno = $turno->loadMissing('paciente');
        $this->paciente_id = $this->turno->paciente_id;
        $this->sugerirTipoAtencion($this->paciente_id, $this->turno->especialidad_id);
    }

    public function guardar(): void
    {
        $this->validate();

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            session()->flash('error', 'No tienes un perfil de médico asociado.');
            return;
        }

        $consulta = ExpedienteConsulta::create($this->datosConsulta() + [
            'paciente_id' => $this->paciente_id,
            'medico_id' => $medico->id,
            'especialidad_id' => $this->turno->especialidad_id,
            'turno_id' => $this->turno->id,
            'fecha' => now(),
        ]);

        app(\App\Services\AuditoriaService::class)->registrar('crear', 'consulta', $consulta->id, $consulta->paciente_id);
        // Aviso que sobrevive a la redirección (lo muestra el layout)
        session()->flash('notify', ['message' => 'Consulta registrada exitosamente.', 'type' => 'success']);
        $this->redirect(route('turno.atender', $this->turno->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.crear-consulta')
            ->layout('layouts.app');
    }
}
