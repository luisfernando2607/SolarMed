<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Livewire\Traits\WithCie10Autocomplete;
use App\Livewire\Traits\WithConsultaForm;
use App\Services\TurnoService;
use Illuminate\Support\Facades\Auth;

class AtenderTurno extends Component
{
    use WithCie10Autocomplete, WithConsultaForm;
    public Turno $turno;
    public ?Paciente $paciente = null;
    public $consultasAnteriores = [];

    public bool $showForm = false;

    public function mount(Turno $turno): void
    {
        $this->turno = $turno->loadMissing(['especialidad', 'paciente']);

        if ($this->turno->paciente_id) {
            $this->paciente = $this->turno->paciente;
            $this->consultasAnteriores = ExpedienteConsulta::where('paciente_id', $this->paciente->id)
                ->with('medico')
                ->orderBy('fecha', 'desc')
                ->take(5)
                ->get();
        }
    }

    public function toggleForm(): void
    {
        $this->showForm = !$this->showForm;
        if (!$this->showForm) {
            $this->resetForm();
        }
    }

    public function guardarConsulta(): void
    {
        $this->authorize('expediente.crear');
        $this->validate();

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            session()->flash('error', 'No tienes un perfil de médico asociado.');
            return;
        }

        ExpedienteConsulta::create($this->datosConsulta() + [
            'paciente_id' => $this->turno->paciente_id,
            'medico_id' => $medico->id,
            'especialidad_id' => $this->turno->especialidad_id,
            'turno_id' => $this->turno->id,
            'fecha' => now(),
        ]);

        $this->showForm = false;
        $this->resetForm();

        $this->consultasAnteriores = ExpedienteConsulta::where('paciente_id', $this->turno->paciente_id)
            ->with('medico')
            ->orderBy('fecha', 'desc')
            ->take(5)
            ->get();

        $this->dispatch('notify', message: 'Consulta registrada exitosamente.', type: 'success');
    }

    public function marcarCompletado(): void
    {
        $this->authorize('turnos.gestionar');
        app(TurnoService::class)->completar($this->turno);
        $this->redirect(route('sala-espera'), navigate: true);
    }

    public function render()
    {
        return view('livewire.atender-turno')
            ->layout('layouts.app');
    }
}
