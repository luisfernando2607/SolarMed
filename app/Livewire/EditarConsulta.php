<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ExpedienteConsulta;
use App\Models\Cie10Code;
use App\Livewire\Traits\WithCie10Autocomplete;
use App\Livewire\Traits\WithConsultaForm;

class EditarConsulta extends Component
{
    use WithCie10Autocomplete, WithConsultaForm;

    public ExpedienteConsulta $consulta;
    public $paciente;

    public function mount(ExpedienteConsulta $consulta): void
    {
        $this->consulta = $consulta->loadMissing('paciente', 'medico', 'especialidad');
        $this->paciente = $this->consulta->paciente;

        $this->llenarDesdeConsulta($this->consulta);

        if ($this->codigo_cie10) {
            $code = Cie10Code::where('codigo', $this->codigo_cie10)->first();
            if ($code) {
                $this->cie10Search = $code->codigo . ' — ' . $code->descripcion;
                $this->cie10Selected = $code->toArray();
            }
        }
    }

    public function guardar(): void
    {
        $this->validate();

        $this->consulta->update($this->datosConsulta());

        $this->dispatch('notify', message: 'Consulta actualizada exitosamente.', type: 'success');

        $referer = request()->header('referer');
        if ($referer && str_contains($referer, route('pacientes'))) {
            $this->redirect(route('pacientes'), navigate: true);
        } elseif ($this->consulta->turno_id) {
            $this->redirect(route('turno.atender', $this->consulta->turno_id), navigate: true);
        } else {
            $this->redirect(route('pacientes'), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.editar-consulta')
            ->layout('layouts.app');
    }
}
