<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Turno;
use App\Models\Paciente;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Livewire\Traits\WithCie10Autocomplete;
use App\Services\TurnoService;
use Illuminate\Support\Facades\Auth;

class AtenderTurno extends Component
{
    use WithCie10Autocomplete;
    public Turno $turno;
    public ?Paciente $paciente = null;
    public $consultasAnteriores = [];

    public bool $showForm = false;

    public $tipo_consulta = 'general';
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

    public function resetForm(): void
    {
        $this->tipo_consulta = 'general';
        $this->motivo_consulta = null;
        $this->anamnesis = null;
        $this->diagnostico = null;
        $this->codigo_cie10 = null;
        $this->tratamiento = null;
        $this->indicaciones = null;
        $this->requiere_derivacion = false;
        $this->derivacion_especialidad = null;
        $this->examen_fisico_pa = null;
        $this->examen_fisico_fc = null;
        $this->examen_fisico_fr = null;
        $this->examen_fisico_temp = null;
        $this->examen_fisico_peso = null;
        $this->examen_fisico_talla = null;
        $this->examen_fisico_imc = null;
        $this->examen_fisico_notas = null;
        $this->clearCie10();
    }

    public function calcularImc(): void
    {
        if ($this->examen_fisico_peso && $this->examen_fisico_talla && $this->examen_fisico_talla > 0) {
            $this->examen_fisico_imc = round($this->examen_fisico_peso / ($this->examen_fisico_talla * $this->examen_fisico_talla), 1);
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

        ExpedienteConsulta::create([
            'paciente_id' => $this->turno->paciente_id,
            'medico_id' => $medico->id,
            'especialidad_id' => $this->turno->especialidad_id,
            'tipo_consulta' => $this->tipo_consulta,
            'turno_id' => $this->turno->id,
            'fecha' => now(),
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
