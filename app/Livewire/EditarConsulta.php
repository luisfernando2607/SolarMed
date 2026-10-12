<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ExpedienteConsulta;
use App\Models\Cie10Code;
use App\Livewire\Traits\WithCie10Autocomplete;
use App\Livewire\Traits\WithConsultaForm;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EditarConsulta extends Component
{
    use WithCie10Autocomplete, WithConsultaForm;

    public ExpedienteConsulta $consulta;
    public $paciente;
    public $motivo_cambio;

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
        $this->authorize('update', $this->consulta);
        $this->validate($this->rules() + [
            'motivo_cambio' => 'required|string|min:5|max:500',
        ], $this->messages() + [
            'motivo_cambio.required' => 'Indique el motivo de la corrección.',
            'motivo_cambio.min' => 'Describa el motivo con al menos 5 caracteres.',
        ]);

        $nuevos = $this->datosConsulta();
        $anteriores = $this->consulta->only(array_keys($nuevos));

        if ($this->sinCambios($anteriores, $nuevos)) {
            $this->dispatch('notify', message: 'No hay cambios para guardar.', type: 'info');
            return;
        }

        DB::transaction(function () use ($anteriores, $nuevos) {
            $this->consulta->versiones()->create([
                'user_id' => Auth::id(),
                'datos_anteriores' => $anteriores,
                'motivo' => $this->motivo_cambio,
            ]);
            $this->consulta->update($nuevos);
        });

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

    private function sinCambios(array $anteriores, array $nuevos): bool
    {
        // Compara como texto para no distinguir 64 de "64" ni null de ""
        $normalizar = fn ($v) => is_array($v) ? array_map(fn ($x) => (string) $x, $v) : (string) $v;

        return array_map($normalizar, $anteriores) == array_map($normalizar, $nuevos);
    }

    public function render()
    {
        return view('livewire.editar-consulta', [
            'versiones' => $this->consulta->versiones()->with('user')->get(),
        ])->layout('layouts.app');
    }
}
