<?php

namespace App\Livewire;

use App\Services\AjustesService;
use App\Services\AuditoriaService;
use Livewire\Component;

class ConfiguracionClinica extends Component
{
    // Agenda
    public string $tiposTexto = '';
    public string $duracionesTexto = '';
    public $duracion_defecto;
    public $hora_inicio;
    public $hora_fin;
    public array $dias = [];

    // Alertas prenatales
    public $pa_sistolica, $pa_diastolica, $pa_severa_sistolica, $pa_severa_diastolica;
    public $fcf_min, $fcf_max, $au_tolerancia_cm, $movimientos_desde_semana, $dias_sin_control, $aviso_fpp_dias;

    // Tareas
    public $hora_recordatorios;
    public $hora_respaldo;

    public const DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
    private const PRENATAL = ['pa_sistolica', 'pa_diastolica', 'pa_severa_sistolica', 'pa_severa_diastolica', 'fcf_min', 'fcf_max',
        'au_tolerancia_cm', 'movimientos_desde_semana', 'dias_sin_control', 'aviso_fpp_dias'];

    public function mount(): void
    {
        $a = app(AjustesService::class);
        $this->tiposTexto = implode("\n", $a->get('agenda.tipos'));
        $this->duracionesTexto = implode(', ', $a->get('agenda.duraciones'));
        $this->duracion_defecto = $a->get('agenda.duracion_defecto');
        $this->hora_inicio = $a->get('agenda.hora_inicio');
        $this->hora_fin = $a->get('agenda.hora_fin');
        $this->dias = array_map('strval', $a->get('agenda.dias_laborables'));
        foreach (self::PRENATAL as $campo) {
            $this->{$campo} = $a->get("prenatal.{$campo}");
        }
        $this->hora_recordatorios = $a->get('tareas.hora_recordatorios');
        $this->hora_respaldo = $a->get('tareas.hora_respaldo');
    }

    public function restaurarPrenatal(): void
    {
        foreach (self::PRENATAL as $campo) {
            $this->{$campo} = AjustesService::DEFECTOS["prenatal.{$campo}"];
        }
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');

        $tipos = collect(preg_split('/\R/', $this->tiposTexto))->map(fn ($t) => mb_substr(trim($t), 0, 60))->filter()->unique()->values()->all();
        $duraciones = collect(preg_split('/[\s,;]+/', $this->duracionesTexto))->filter(fn ($d) => ctype_digit($d))->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 5 && $d <= 480)->unique()->sort()->values()->all();

        $this->validate([
            'tiposTexto' => 'required|string|max:2000',
            'duracionesTexto' => 'required|string|max:200',
            'duracion_defecto' => 'required|integer',
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dias' => 'required|array|min:1',
            'dias.*' => 'in:0,1,2,3,4,5,6',
            'pa_sistolica' => 'required|integer|between:100,200',
            'pa_diastolica' => 'required|integer|between:60,130',
            'pa_severa_sistolica' => 'required|integer|between:120,250|gt:pa_sistolica',
            'pa_severa_diastolica' => 'required|integer|between:80,150|gt:pa_diastolica',
            'fcf_min' => 'required|integer|between:60,140',
            'fcf_max' => 'required|integer|between:140,220|gt:fcf_min',
            'au_tolerancia_cm' => 'required|numeric|between:1,10',
            'movimientos_desde_semana' => 'required|integer|between:16,40',
            'dias_sin_control' => 'required|integer|between:7,90',
            'aviso_fpp_dias' => 'required|integer|between:1,60',
            'hora_recordatorios' => ['required', 'date_format:H:i'],
            'hora_respaldo' => ['required', 'date_format:H:i'],
        ], [
            'hora_fin.after' => 'La hora de cierre debe ser posterior a la de inicio.',
            'dias.required' => 'Seleccione al menos un día de atención.',
            'pa_severa_sistolica.gt' => 'Debe ser mayor que el umbral de hipertensión.',
            'pa_severa_diastolica.gt' => 'Debe ser mayor que el umbral de hipertensión.',
            'fcf_max.gt' => 'Debe ser mayor que el mínimo.',
        ]);

        if (!$tipos) {
            $this->addError('tiposTexto', 'Agregue al menos un tipo de cita.');
            return;
        }
        if (!$duraciones) {
            $this->addError('duracionesTexto', 'Ingrese duraciones en minutos separadas por coma, por ejemplo: 15, 20, 30.');
            return;
        }
        if (!in_array((int) $this->duracion_defecto, $duraciones, true)) {
            $this->addError('duracion_defecto', 'La duración por defecto debe estar en la lista de duraciones.');
            return;
        }

        $valores = [
            'agenda.tipos' => $tipos,
            'agenda.duraciones' => $duraciones,
            'agenda.duracion_defecto' => (int) $this->duracion_defecto,
            'agenda.hora_inicio' => $this->hora_inicio,
            'agenda.hora_fin' => $this->hora_fin,
            'agenda.dias_laborables' => array_values(array_map('intval', $this->dias)),
            'tareas.hora_recordatorios' => $this->hora_recordatorios,
            'tareas.hora_respaldo' => $this->hora_respaldo,
        ];
        foreach (self::PRENATAL as $campo) {
            $valores["prenatal.{$campo}"] = $campo === 'au_tolerancia_cm' ? (float) $this->{$campo} : (int) $this->{$campo};
        }

        app(AjustesService::class)->guardar($valores);
        app(AuditoriaService::class)->registrar('editar', 'configuracion', null, null, 'Configuración de agenda, alertas y tareas');

        $this->tiposTexto = implode("\n", $tipos);
        $this->duracionesTexto = implode(', ', $duraciones);
        $this->dispatch('notify', message: 'Configuración guardada.', type: 'success');
    }

    public function render()
    {
        return view('livewire.configuracion-clinica')->layout('layouts.app');
    }
}
