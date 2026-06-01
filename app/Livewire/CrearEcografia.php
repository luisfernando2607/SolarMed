<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Turno;
use App\Models\Ecografia;
use App\Models\Medico;
use App\Services\EcografiaService;
use Illuminate\Support\Facades\Auth;

class CrearEcografia extends Component
{
    public Turno $turno;
    public $paciente_id;
    public $fecha;
    public $indicacion;
    public $semanas_gestacion;
    public $fum;
    public $fpp;
    public $presentacion;
    public $lcf;
    public $placenta;
    public $liquido_amniotico;
    public $dbp;
    public $cc;
    public $ca;
    public $lf;
    public $peso_fetal_estimado;
    public $conclusion;

    public $ecografiaCreada = null;

    protected function rules(): array
    {
        return [
            'fecha' => 'required|date',
            'indicacion' => 'nullable|string|max:255',
            'semanas_gestacion' => 'nullable|string|max:20',
            'fum' => 'nullable|date',
            'fpp' => 'nullable|date',
            'presentacion' => 'nullable|string|max:50',
            'lcf' => 'nullable|integer|min:60|max:220',
            'placenta' => 'nullable|string|max:100',
            'liquido_amniotico' => 'nullable|string|max:50',
            'dbp' => 'nullable|numeric|min:0|max:120',
            'cc' => 'nullable|numeric|min:0|max:500',
            'ca' => 'nullable|numeric|min:0|max:500',
            'lf' => 'nullable|numeric|min:0|max:90',
            'peso_fetal_estimado' => 'nullable|integer|min:0|max:5000',
            'conclusion' => 'nullable|string|max:5000',
        ];
    }

    public function mount(Turno $turno): void
    {
        $this->turno = $turno->loadMissing('paciente');
        $this->paciente_id = $this->turno->paciente_id;
        $this->fecha = now()->format('Y-m-d');
    }

    public function guardar(): void
    {
        $this->validate();

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            session()->flash('error', 'No tienes un perfil de médico asociado.');
            return;
        }

        $eco = Ecografia::create([
            'paciente_id' => $this->paciente_id,
            'medico_id' => $medico->id,
            'fecha' => $this->fecha,
            'indicacion' => $this->indicacion,
            'semanas_gestacion' => $this->semanas_gestacion,
            'fum' => $this->fum,
            'fpp' => $this->fpp,
            'presentacion' => $this->presentacion,
            'lcf' => $this->lcf,
            'placenta' => $this->placenta,
            'liquido_amniotico' => $this->liquido_amniotico,
            'dbp' => $this->dbp,
            'cc' => $this->cc,
            'ca' => $this->ca,
            'lf' => $this->lf,
            'peso_fetal_estimado' => $this->peso_fetal_estimado,
            'conclusion' => $this->conclusion,
        ]);

        try {
            app(EcografiaService::class)->generarPdf($eco);
            $eco->refresh();
        } catch (\Exception $e) {
            report($e);
        }

        $this->ecografiaCreada = $eco;
        session()->flash('success', 'Ecografía registrada exitosamente.');
    }

    public function render()
    {
        return view('livewire.crear-ecografia')
            ->layout('layouts.app');
    }
}
