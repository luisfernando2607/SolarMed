<?php

namespace App\Livewire\Traits;

use App\Models\ExpedienteConsulta;

/**
 * Campos, validación y armado de datos del formulario de consulta clínica,
 * compartido por AtenderTurno, CrearConsulta, CrearConsultaSinTurno y EditarConsulta.
 */
trait WithConsultaForm
{
    public $tipo_consulta = 'general';
    public $tipo_atencion = 'primera';
    public $tipo_diagnostico = 'presuntivo';
    public $revision_sistemas;
    public $motivo_consulta;
    public $anamnesis;
    public $diagnostico;
    public $codigo_cie10;
    public $tratamiento;
    public $indicaciones;
    public $requiere_derivacion = false;
    public $derivacion_especialidad;
    /** presencial | telemedicina: la fija la teleconsulta, no el formulario (Norma Técnica de Telesalud) */
    #[\Livewire\Attributes\Locked]
    public $modalidad = 'presencial';

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
            'tipo_atencion' => 'required|in:primera,subsecuente',
            'anamnesis' => 'nullable|string|max:5000',
            'revision_sistemas' => 'nullable|string|max:3000',
            'tipo_diagnostico' => 'required|in:presuntivo,definitivo',
            'diagnostico' => 'required|string|max:2000',
            'codigo_cie10' => 'nullable|string|max:10',
            'tratamiento' => 'nullable|string|max:5000',
            'indicaciones' => 'nullable|string|max:5000',
            'requiere_derivacion' => 'boolean',
            'derivacion_especialidad' => 'required_if:requiere_derivacion,true|nullable|string|max:255',
        ];
    }

    protected function messages(): array
    {
        return [
            'motivo_consulta.required' => 'El motivo de consulta es obligatorio.',
            'diagnostico.required' => 'El diagnóstico es obligatorio.',
            'tipo_atencion.required' => 'Indique si es primera consulta o subsecuente.',
            'tipo_diagnostico.required' => 'Indique si el diagnóstico es presuntivo o definitivo.',
        ];
    }

    /**
     * Formulario 002, bloque B: subsecuente si el paciente ya tiene consultas en la misma especialidad.
     * Es una sugerencia; el médico puede cambiarla.
     */
    protected function sugerirTipoAtencion(?int $pacienteId, ?int $especialidadId): void
    {
        if (!$pacienteId) {
            return;
        }
        $previas = ExpedienteConsulta::where('paciente_id', $pacienteId)
            ->when($especialidadId, fn ($q) => $q->where('especialidad_id', $especialidadId))
            ->exists();
        $this->tipo_atencion = $previas ? 'subsecuente' : 'primera';
    }

    public function calcularImc(): void
    {
        if ($imc = $this->imc()) {
            $this->examen_fisico_imc = $imc;
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'tipo_consulta', 'tipo_atencion', 'tipo_diagnostico', 'revision_sistemas',
            'motivo_consulta', 'anamnesis', 'diagnostico', 'codigo_cie10',
            'tratamiento', 'indicaciones', 'requiere_derivacion', 'derivacion_especialidad', 'modalidad',
            'examen_fisico_pa', 'examen_fisico_fc', 'examen_fisico_fr', 'examen_fisico_temp',
            'examen_fisico_peso', 'examen_fisico_talla', 'examen_fisico_imc', 'examen_fisico_notas',
        ]);
        $this->clearCie10();
    }

    protected function llenarDesdeConsulta(ExpedienteConsulta $consulta): void
    {
        $this->tipo_consulta = $consulta->tipo_consulta;
        $this->modalidad = $consulta->modalidad ?? 'presencial';
        $this->tipo_atencion = $consulta->tipo_atencion;
        $this->tipo_diagnostico = $consulta->tipo_diagnostico;
        $this->revision_sistemas = $consulta->revision_sistemas;
        $this->motivo_consulta = $consulta->motivo_consulta;
        $this->anamnesis = $consulta->anamnesis;
        $this->diagnostico = $consulta->diagnostico;
        $this->codigo_cie10 = $consulta->codigo_cie10;
        $this->tratamiento = $consulta->tratamiento;
        $this->indicaciones = $consulta->indicaciones;
        $this->requiere_derivacion = $consulta->requiere_derivacion;
        $this->derivacion_especialidad = $consulta->derivacion_especialidad;

        $ef = $consulta->examen_fisico ?? [];
        $this->examen_fisico_pa = $ef['pa'] ?? null;
        $this->examen_fisico_fc = $ef['fc'] ?? null;
        $this->examen_fisico_fr = $ef['fr'] ?? null;
        $this->examen_fisico_temp = $ef['temp'] ?? null;
        $this->examen_fisico_peso = $ef['peso'] ?? null;
        $this->examen_fisico_talla = $ef['talla'] ?? null;
        $this->examen_fisico_imc = $ef['imc'] ?? null;
        $this->examen_fisico_notas = $ef['notas'] ?? null;
    }

    /** Trae los signos que tomó enfermería en la preparación del turno, sin pisar lo que el médico ya escribió */
    protected function llenarDesdePreparacion(?\App\Models\Preparacion $p): void
    {
        if (!$p) {
            return;
        }
        $valores = [
            'examen_fisico_pa' => $p->presion(),
            'examen_fisico_fc' => $p->frecuencia_cardiaca,
            'examen_fisico_fr' => $p->frecuencia_respiratoria,
            'examen_fisico_temp' => $p->temperatura,
            'examen_fisico_peso' => $p->peso,
            'examen_fisico_talla' => $p->talla,
            'examen_fisico_imc' => $p->imc(),
        ];
        foreach ($valores as $campo => $valor) {
            if (($this->{$campo} === null || $this->{$campo} === '') && $valor !== null) {
                $this->{$campo} = $valor;
            }
        }
    }

    /** Campos clínicos listos para crear o actualizar un ExpedienteConsulta. */
    protected function datosConsulta(): array
    {
        return [
            'tipo_consulta' => $this->tipo_consulta,
            'modalidad' => $this->modalidad === 'telemedicina' ? 'telemedicina' : 'presencial',
            'tipo_atencion' => $this->tipo_atencion,
            'motivo_consulta' => $this->motivo_consulta,
            'anamnesis' => $this->anamnesis,
            'revision_sistemas' => $this->revision_sistemas,
            'examen_fisico' => [
                'pa' => $this->examen_fisico_pa,
                'fc' => $this->examen_fisico_fc,
                'fr' => $this->examen_fisico_fr,
                'temp' => $this->examen_fisico_temp,
                'peso' => $this->examen_fisico_peso,
                'talla' => $this->examen_fisico_talla,
                'imc' => $this->examen_fisico_imc ?? $this->imc(),
                'notas' => $this->examen_fisico_notas,
            ],
            'diagnostico' => $this->diagnostico,
            'codigo_cie10' => $this->codigo_cie10,
            'tipo_diagnostico' => $this->tipo_diagnostico,
            'tratamiento' => $this->tratamiento,
            'indicaciones' => $this->indicaciones,
            'requiere_derivacion' => $this->requiere_derivacion,
            'derivacion_especialidad' => $this->requiere_derivacion ? $this->derivacion_especialidad : null,
        ];
    }

    private function imc(): ?float
    {
        $peso = (float) $this->examen_fisico_peso;
        $talla = (float) $this->examen_fisico_talla;

        return $peso > 0 && $talla > 0 ? round($peso / ($talla * $talla), 1) : null;
    }
}
