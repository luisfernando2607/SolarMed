<?php

namespace App\Livewire;

use App\Models\ControlPrenatal;
use App\Models\Embarazo;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use App\Services\PrenatalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ControlPrenatalPaciente extends Component
{
    public Paciente $paciente;

    // Inicio de embarazo
    public $fum;

    // Nuevo control
    public $fecha;
    public $peso_materno;
    public $presion_arterial;
    public $altura_uterina;
    public $fcf;
    public $presentacion;
    public $movimientos_fetales = '';
    public $edemas;
    public $observaciones;

    // Cierre
    public $desenlace;
    public $fecha_fin;

    public const PRESENTACIONES = ['Cefálica', 'Podálica', 'Transversa', 'No determinable'];
    public const EDEMAS = ['Ausentes', '+', '++', '+++'];

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
        $this->fecha = today()->format('Y-m-d');
        $this->fecha_fin = today()->format('Y-m-d');
        app(AuditoriaService::class)->registrar('ver', 'control_prenatal', null, $paciente->id, 'Control prenatal');
    }

    private function embarazoActivo(): ?Embarazo
    {
        return $this->paciente->embarazos()->where('estado', 'activo')->first();
    }

    private function medicoActual(): ?Medico
    {
        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            $this->dispatch('notify', message: 'Necesita un perfil de médico para registrar controles prenatales.', type: 'error');
        }
        return $medico;
    }

    public function iniciarEmbarazo(): void
    {
        $this->authorize('expediente.crear');
        $this->validate([
            'fum' => ['required', 'date', 'before_or_equal:today', 'after:' . today()->subWeeks(44)->format('Y-m-d')],
        ], [
            'fum.required' => 'Ingrese la fecha de última menstruación.',
            'fum.before_or_equal' => 'La FUM no puede ser futura.',
            'fum.after' => 'La FUM corresponde a más de 44 semanas.',
        ]);

        if ($this->embarazoActivo()) {
            $this->dispatch('notify', message: 'La paciente ya tiene un embarazo activo.', type: 'error');
            return;
        }
        if (!$medico = $this->medicoActual()) {
            return;
        }

        $fum = Carbon::parse($this->fum);
        $embarazo = Embarazo::create([
            'paciente_id' => $this->paciente->id,
            'medico_id' => $medico->id,
            'fum' => $fum,
            'fpp' => app(PrenatalService::class)->fpp($fum),
            'checklist' => [],
        ]);
        $this->paciente->update(['fum' => $fum]);

        app(AuditoriaService::class)->registrar('crear', 'embarazo', $embarazo->id, $this->paciente->id, "FUM {$fum->format('d/m/Y')}");
        $this->reset('fum');
        $this->dispatch('notify', message: 'Embarazo registrado.', type: 'success');
    }

    public function registrarControl(): void
    {
        $this->authorize('expediente.crear');
        $embarazo = $this->embarazoActivo();
        if (!$embarazo) {
            return;
        }

        $this->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $embarazo->fum->format('Y-m-d')],
            'peso_materno' => 'nullable|numeric|min:30|max:250',
            'presion_arterial' => ['nullable', 'regex:/^\s*\d{2,3}\s*\/\s*\d{2,3}\s*$/'],
            'altura_uterina' => 'nullable|numeric|min:5|max:50',
            'fcf' => 'nullable|integer|min:60|max:220',
            'presentacion' => ['nullable', Rule::in(self::PRESENTACIONES)],
            'movimientos_fetales' => 'nullable|in:,1,0',
            'edemas' => ['nullable', Rule::in(self::EDEMAS)],
            'observaciones' => 'nullable|string|max:2000',
        ], [
            'presion_arterial.regex' => 'Use el formato 120/80.',
            'fecha.after_or_equal' => 'La fecha no puede ser anterior a la FUM.',
        ]);

        if (!$medico = $this->medicoActual()) {
            return;
        }

        $eg = $embarazo->edadGestacional(Carbon::parse($this->fecha));
        $control = ControlPrenatal::create([
            'embarazo_id' => $embarazo->id,
            'paciente_id' => $this->paciente->id,
            'medico_id' => $medico->id,
            'fecha' => $this->fecha,
            'semanas_gestacion' => $eg['semanas'],
            'dias_gestacion' => $eg['dias'],
            'fpp' => $embarazo->fpp,
            'peso_materno' => $this->peso_materno ?: null,
            'presion_arterial' => $this->presion_arterial ? preg_replace('/\s+/', '', $this->presion_arterial) : null,
            'altura_uterina' => $this->altura_uterina ?: null,
            'fcf' => $this->fcf ?: null,
            'presentacion' => $this->presentacion ?: null,
            'movimientos_fetales' => $this->movimientos_fetales === '' || $this->movimientos_fetales === null ? null : (bool) $this->movimientos_fetales,
            'edemas' => $this->edemas ?: null,
            'observaciones' => $this->observaciones,
        ]);

        app(AuditoriaService::class)->registrar('crear', 'control_prenatal', $control->id, $this->paciente->id, "{$eg['semanas']}+{$eg['dias']} semanas");
        $this->reset(['peso_materno', 'presion_arterial', 'altura_uterina', 'fcf', 'presentacion', 'movimientos_fetales', 'edemas', 'observaciones']);
        $this->fecha = today()->format('Y-m-d');

        $alertas = app(PrenatalService::class)->alertas($control);
        $this->dispatch(
            'notify',
            message: $alertas ? 'Control registrado con alertas: ' . collect($alertas)->pluck('mensaje')->implode('; ') : 'Control registrado.',
            type: $alertas ? 'warning' : 'success'
        );
    }

    public function toggleExamen(string $clave): void
    {
        $this->authorize('expediente.crear');
        $embarazo = $this->embarazoActivo();
        $validas = collect(config('clinica.checklist_prenatal'))->flatMap(fn ($t) => array_keys($t))->all();
        if (!$embarazo || !in_array($clave, $validas, true)) {
            return;
        }

        $checklist = $embarazo->checklist ?? [];
        if (isset($checklist[$clave])) {
            unset($checklist[$clave]);
        } else {
            $checklist[$clave] = today()->format('Y-m-d');
        }
        $embarazo->update(['checklist' => $checklist]);
    }

    public function cerrarEmbarazo(): void
    {
        $this->authorize('expediente.crear');
        $embarazo = $this->embarazoActivo();
        if (!$embarazo) {
            return;
        }

        $this->validate([
            'desenlace' => ['required', Rule::in(array_keys(Embarazo::DESENLACES))],
            'fecha_fin' => ['required', 'date', 'before_or_equal:today', 'after_or_equal:' . $embarazo->fum->format('Y-m-d')],
        ], ['desenlace.required' => 'Seleccione el desenlace.']);

        $embarazo->update(['estado' => 'finalizado', 'desenlace' => $this->desenlace, 'fecha_fin' => $this->fecha_fin]);
        app(AuditoriaService::class)->registrar('editar', 'embarazo', $embarazo->id, $this->paciente->id, 'Cierre: ' . Embarazo::DESENLACES[$this->desenlace]);
        $this->reset('desenlace');
        $this->dispatch('notify', message: 'Embarazo cerrado.', type: 'success');
    }

    public function render()
    {
        $embarazo = $this->embarazoActivo()?->load('controles.medico');
        $prenatal = app(PrenatalService::class);

        return view('livewire.control-prenatal-paciente', [
            'embarazo' => $embarazo,
            'egHoy' => $embarazo?->edadGestacional(),
            'trimestre' => $embarazo?->trimestre(),
            'alertas' => $embarazo ? $embarazo->controles->mapWithKeys(fn ($c) => [$c->id => $prenatal->alertas($c)]) : collect(),
            'checklistConfig' => config('clinica.checklist_prenatal'),
            'anteriores' => $this->paciente->embarazos()->where('estado', 'finalizado')->withCount('controles')->get(),
        ])->layout('layouts.app');
    }
}
