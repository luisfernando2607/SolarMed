<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Paciente;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Especialidad;
use App\Livewire\Traits\WithCie10Autocomplete;
use App\Livewire\Traits\WithConsultaForm;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

class CrearConsultaSinTurno extends Component
{
    use WithPagination;
    use WithCie10Autocomplete, WithConsultaForm;

    public string $busqueda = '';
    public ?Paciente $selectedPaciente = null;
    public bool $showForm = false;

    public function seleccionarPaciente(Paciente $paciente): void
    {
        $this->selectedPaciente = $paciente;
        $this->showForm = false;
    }

    public function resetPaciente(): void
    {
        $this->selectedPaciente = null;
        $this->showForm = false;
        $this->resetForm();
    }

    public function iniciarConsulta(): void
    {
        $this->showForm = true;
    }

    public function guardar(): void
    {
        $this->authorize('expediente.crear');
        $this->validate();

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            session()->flash('error', 'No tienes un perfil de médico asociado.');
            return;
        }

        $consulta = ExpedienteConsulta::create($this->datosConsulta() + [
            'paciente_id' => $this->selectedPaciente->id,
            'medico_id' => $medico->id,
            'especialidad_id' => $medico->especialidad_id,
            'fecha' => now(),
        ]);

        app(\App\Services\AuditoriaService::class)->registrar('crear', 'consulta', $consulta->id, $consulta->paciente_id);
        // Aviso que sobrevive a la redirección (lo muestra el layout)
        session()->flash('notify', ['message' => 'Consulta registrada exitosamente.', 'type' => 'success']);
        $this->redirect(route('expedientes'), navigate: true);
    }

    public function render()
    {
        $pacientes = collect();
        if (!$this->selectedPaciente && !$this->showForm) {
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

        return view('livewire.crear-consulta-sin-turno', [
            'pacientes' => $pacientes,
        ])->layout('layouts.app');
    }
}
