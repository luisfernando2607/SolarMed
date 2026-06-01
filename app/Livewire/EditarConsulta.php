<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Cie10Code;
use App\Livewire\Traits\WithCie10Autocomplete;
use Illuminate\Support\Facades\Auth;

class EditarConsulta extends Component
{
    use WithCie10Autocomplete;
    public ExpedienteConsulta $consulta;
    public $paciente;

    public $tipo_consulta;
    public $motivo_consulta;
    public $anamnesis;
    public $diagnostico;
    public $codigo_cie10;
    public $tratamiento;
    public $indicaciones;
    public $requiere_derivacion = false;
    public $derivacion_especialidad;

    public $examen_fisico_pa;
    public $examen_fisico_fc;
    public $examen_fisico_fr;
    public $examen_fisico_temp;
    public $examen_fisico_peso;
    public $examen_fisico_talla;
    public $examen_fisico_imc;
    public $examen_fisico_notas;

    protected function rules(): array
    {
        return [
            'tipo_consulta' => 'required|in:general,ginecologica,control_prenatal',
            'motivo_consulta' => 'required|string|max:2000',
            'anamnesis' => 'nullable|string|max:5000',
            'diagnostico' => 'required|string|max:2000',
            'codigo_cie10' => 'nullable|string|max:10',
            'tratamiento' => 'nullable|string|max:5000',
            'indicaciones' => 'nullable|string|max:5000',
            'requiere_derivacion' => 'boolean',
            'derivacion_especialidad' => 'required_if:requiere_derivacion,true|nullable|string|max:255',
        ];
    }

    protected $messages = [
        'motivo_consulta.required' => 'El motivo de consulta es obligatorio.',
        'diagnostico.required' => 'El diagnóstico es obligatorio.',
    ];

    public function mount(ExpedienteConsulta $consulta): void
    {
        $this->consulta = $consulta->loadMissing('paciente', 'medico', 'especialidad');
        $this->paciente = $this->consulta->paciente;

        $this->tipo_consulta = $this->consulta->tipo_consulta;
        $this->motivo_consulta = $this->consulta->motivo_consulta;
        $this->anamnesis = $this->consulta->anamnesis;
        $this->diagnostico = $this->consulta->diagnostico;
        $this->codigo_cie10 = $this->consulta->codigo_cie10;
        if ($this->codigo_cie10) {
            $code = Cie10Code::where('codigo', $this->codigo_cie10)->first();
            if ($code) {
                $this->cie10Search = $code->codigo . ' — ' . $code->descripcion;
                $this->cie10Selected = $code->toArray();
            }
        }
        $this->tratamiento = $this->consulta->tratamiento;
        $this->indicaciones = $this->consulta->indicaciones;
        $this->requiere_derivacion = $this->consulta->requiere_derivacion;
        $this->derivacion_especialidad = $this->consulta->derivacion_especialidad;

        $ef = $this->consulta->examen_fisico ?? [];
        $this->examen_fisico_pa = $ef['pa'] ?? null;
        $this->examen_fisico_fc = $ef['fc'] ?? null;
        $this->examen_fisico_fr = $ef['fr'] ?? null;
        $this->examen_fisico_temp = $ef['temp'] ?? null;
        $this->examen_fisico_peso = $ef['peso'] ?? null;
        $this->examen_fisico_talla = $ef['talla'] ?? null;
        $this->examen_fisico_imc = $ef['imc'] ?? null;
        $this->examen_fisico_notas = $ef['notas'] ?? null;
    }

    public function calcularImc(): void
    {
        if ($this->examen_fisico_peso && $this->examen_fisico_talla && $this->examen_fisico_talla > 0) {
            $this->examen_fisico_imc = round($this->examen_fisico_peso / ($this->examen_fisico_talla * $this->examen_fisico_talla), 1);
        }
    }

    public function guardar(): void
    {
        $this->validate();

        $examenFisico = [
            'pa' => $this->examen_fisico_pa,
            'fc' => $this->examen_fisico_fc,
            'fr' => $this->examen_fisico_fr,
            'temp' => $this->examen_fisico_temp,
            'peso' => $this->examen_fisico_peso,
            'talla' => $this->examen_fisico_talla,
            'imc' => $this->examen_fisico_imc ?? ($this->examen_fisico_peso && $this->examen_fisico_talla && $this->examen_fisico_talla > 0
                ? round($this->examen_fisico_peso / ($this->examen_fisico_talla * $this->examen_fisico_talla), 1)
                : null),
            'notas' => $this->examen_fisico_notas,
        ];

        $this->consulta->update([
            'tipo_consulta' => $this->tipo_consulta,
            'motivo_consulta' => $this->motivo_consulta,
            'anamnesis' => $this->anamnesis,
            'examen_fisico' => $examenFisico,
            'diagnostico' => $this->diagnostico,
            'codigo_cie10' => $this->codigo_cie10,
            'tratamiento' => $this->tratamiento,
            'indicaciones' => $this->indicaciones,
            'requiere_derivacion' => $this->requiere_derivacion,
            'derivacion_especialidad' => $this->requiere_derivacion ? $this->derivacion_especialidad : null,
        ]);

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
