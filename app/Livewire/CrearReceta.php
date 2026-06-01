<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Turno;
use App\Models\Receta;
use App\Models\Medico;
use Illuminate\Support\Facades\Auth;

class CrearReceta extends Component
{
    public Turno $turno;
    public $paciente_id;
    public $medicamentos = [];
    public $observaciones;

    protected function rules(): array
    {
        return [
            'medicamentos' => 'required|array|min:1',
            'medicamentos.*.medicamento' => 'required|string|max:255',
            'medicamentos.*.presentacion' => 'nullable|string|max:100',
            'medicamentos.*.dosis' => 'nullable|string|max:100',
            'medicamentos.*.frecuencia' => 'nullable|string|max:100',
            'medicamentos.*.duracion' => 'nullable|string|max:100',
            'medicamentos.*.indicaciones' => 'nullable|string|max:500',
            'observaciones' => 'nullable|string|max:2000',
        ];
    }

    protected $messages = [
        'medicamentos.required' => 'Debe agregar al menos un medicamento.',
        'medicamentos.*.medicamento.required' => 'El nombre del medicamento es obligatorio.',
    ];

    public function mount(Turno $turno): void
    {
        $this->turno = $turno->loadMissing('paciente');
        $this->paciente_id = $this->turno->paciente_id;
        $this->agregarMedicamento();
    }

    public function agregarMedicamento(): void
    {
        $this->medicamentos[] = [
            'medicamento' => '',
            'presentacion' => '',
            'dosis' => '',
            'frecuencia' => '',
            'duracion' => '',
            'indicaciones' => '',
        ];
    }

    public function quitarMedicamento(int $index): void
    {
        unset($this->medicamentos[$index]);
        $this->medicamentos = array_values($this->medicamentos);
    }

    public function guardar(): void
    {
        $this->validate();

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            session()->flash('error', 'No tienes un perfil de médico asociado.');
            return;
        }

        Receta::create([
            'paciente_id' => $this->paciente_id,
            'medico_id' => $medico->id,
            'fecha' => now(),
            'medicamentos' => $this->medicamentos,
            'observaciones' => $this->observaciones,
        ]);

        session()->flash('success', 'Receta creada exitosamente.');
        $this->redirect(route('turno.atender', $this->turno->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.crear-receta')
            ->layout('layouts.app');
    }
}
