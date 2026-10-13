<?php

namespace App\Livewire;

use App\Models\Preparacion;
use App\Models\Turno;
use App\Services\AuditoriaService;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Preparación de enfermería: signos vitales y medidas antes de que el médico atienda el turno */
class PreparacionTurno extends Component
{
    #[Locked]
    public Turno $turno;

    public $presion_sistolica, $presion_diastolica, $frecuencia_cardiaca, $frecuencia_respiratoria;
    public $temperatura, $saturacion, $peso, $talla, $perimetro_abdominal, $glucosa_capilar, $hemoglobina_capilar;
    public $observaciones;

    private const CAMPOS = ['presion_sistolica', 'presion_diastolica', 'frecuencia_cardiaca', 'frecuencia_respiratoria',
        'temperatura', 'saturacion', 'peso', 'talla', 'perimetro_abdominal', 'glucosa_capilar', 'hemoglobina_capilar', 'observaciones'];

    public function mount(Turno $turno): void
    {
        $this->turno = $turno->load('paciente', 'especialidad', 'preparacion.user');
        if ($p = $turno->preparacion) {
            foreach (self::CAMPOS as $c) {
                $this->{$c} = $p->{$c};
            }
        } elseif ($turno->paciente) {
            // La talla de un adulto casi no cambia: se sugiere la última registrada
            $this->talla = Preparacion::where('paciente_id', $turno->paciente_id)->whereNotNull('talla')->latest('fecha')->value('talla')
                ?? $turno->paciente->altura;
        }
    }

    /** Solo mientras el turno sigue abierto */
    public function getEditableProperty(): bool
    {
        return in_array($this->turno->estado, ['esperando', 'en_atencion'], true) && $this->turno->paciente_id !== null;
    }

    public function getImcProperty(): ?float
    {
        return (float) $this->peso > 0 && (float) $this->talla > 0 ? round((float) $this->peso / ((float) $this->talla ** 2), 1) : null;
    }

    public function guardar(): void
    {
        $this->authorize('preparacion.registrar');
        abort_unless($this->editable, 403);

        foreach (self::CAMPOS as $c) {
            if ($this->{$c} === '') {
                $this->{$c} = null;
            }
        }

        $datos = $this->validate([
            'presion_sistolica' => 'nullable|integer|between:50,260|required_with:presion_diastolica',
            'presion_diastolica' => 'nullable|integer|between:30,160|required_with:presion_sistolica|lt:presion_sistolica',
            'frecuencia_cardiaca' => 'nullable|integer|between:20,250',
            'frecuencia_respiratoria' => 'nullable|integer|between:5,80',
            'temperatura' => 'nullable|numeric|between:30,45',
            'saturacion' => 'nullable|integer|between:50,100',
            'peso' => 'nullable|numeric|between:0.5,400',
            'talla' => 'nullable|numeric|between:0.3,2.5',
            'perimetro_abdominal' => 'nullable|numeric|between:20,250',
            'glucosa_capilar' => 'nullable|numeric|between:10,900',
            'hemoglobina_capilar' => 'nullable|numeric|between:2,25',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'presion_diastolica.lt' => 'La diastólica debe ser menor que la sistólica.',
            'talla.between' => 'La talla va en metros (por ejemplo 1.62).',
        ], [
            'presion_sistolica' => 'presión sistólica', 'presion_diastolica' => 'presión diastólica',
            'frecuencia_cardiaca' => 'frecuencia cardíaca', 'frecuencia_respiratoria' => 'frecuencia respiratoria',
            'temperatura' => 'temperatura', 'saturacion' => 'saturación', 'peso' => 'peso', 'talla' => 'talla',
            'perimetro_abdominal' => 'perímetro abdominal', 'glucosa_capilar' => 'glucosa capilar', 'hemoglobina_capilar' => 'hemoglobina capilar',
        ]);

        if (!array_filter($datos, fn ($v) => $v !== null && $v !== '')) {
            $this->addError('presion_sistolica', 'Registre al menos un signo vital o medida.');
            return;
        }

        $nueva = !$this->turno->preparacion;
        $prep = Preparacion::updateOrCreate(['turno_id' => $this->turno->id], $datos + [
            'paciente_id' => $this->turno->paciente_id,
            'user_id' => auth()->id(),
            'fecha' => now(),
        ]);
        app(AuditoriaService::class)->registrar($nueva ? 'crear' : 'editar', 'preparacion', $prep->id, $prep->paciente_id, "Signos vitales del turno {$this->turno->codigo}");

        session()->flash('notify', ['message' => "Signos vitales de {$this->turno->codigo} registrados.", 'type' => 'success']);
        $this->redirect(route('sala-espera'), navigate: true);
    }

    public function render()
    {
        return view('livewire.preparacion-turno')->layout('layouts.app');
    }
}
