<?php

namespace App\Services;

use App\Models\Cie10Code;
use App\Models\ExpedienteConsulta;
use Illuminate\Support\Collection;

/**
 * Evalúa automáticamente la calidad del llenado de una consulta contra los bloques del
 * formulario SNS-MSP/HCU-form.002/2021 (Consulta externa) del Reglamento de la HCU (AM 00115-2021).
 * El resultado es una pre-evaluación: el auditor la confirma o corrige en la auditoría manual.
 */
class CalidadHistoriaService
{
    /** clave => [título, qué se verifica, peso] */
    public const CRITERIOS = [
        'A' => ['Identificación del paciente', 'Cédula, nombres, apellidos, sexo y fecha de nacimiento', 1.0],
        'B' => ['Motivo de consulta', 'Motivo registrado e indicado si es primera o subsecuente', 1.0],
        'C' => ['Antecedentes personales', 'Antecedentes, medicación, cirugías o alergias (o «niega»)', 1.0],
        'D' => ['Antecedentes familiares', 'Antecedentes patológicos familiares (o «niega»)', 1.0],
        'E' => ['Enfermedad o problema actual', 'Descripción de la enfermedad actual (mín. 20 caracteres)', 1.0],
        'F' => ['Constantes vitales y antropometría', 'Presión arterial, pulso, frecuencia respiratoria, temperatura, peso y talla', 1.0],
        'G' => ['Revisión de órganos y sistemas', 'Revisión actual de órganos y sistemas registrada', 0.5],
        'H' => ['Examen físico', 'Hallazgos del examen físico registrados', 1.0],
        'I' => ['Diagnóstico', 'Diagnóstico con código CIE-10 válido, presuntivo o definitivo', 1.0],
        'J' => ['Plan de tratamiento', 'Plan diagnóstico, terapéutico o educacional registrado', 1.0],
        'K' => ['Profesional responsable', 'Médico identificado con cédula y firma registrada', 1.0],
        'T' => ['Registro oportuno', 'Registrada dentro de las 24 h de la atención', 0.5],
    ];

    /** Umbrales del semáforo de cumplimiento (%) */
    public const UMBRAL_BUENO = 90;
    public const UMBRAL_ACEPTABLE = 75;

    /** @var array<string, bool> códigos CIE-10 ya consultados */
    private array $cie10 = [];

    /**
     * @return array{criterios: array<string, array{titulo: string, detalle: string, peso: float, ok: bool}>, puntaje: int}
     */
    public function evaluar(ExpedienteConsulta $c): array
    {
        $c->loadMissing('paciente', 'medico.user');
        $p = $c->paciente;
        $ef = $c->examen_fisico ?? [];
        $lleno = fn ($v) => trim((string) $v) !== '';

        $ok = [
            'A' => $p && $lleno($p->cedula) && $lleno($p->nombres) && $lleno($p->apellidos) && $lleno($p->sexo) && $p->fecha_nacimiento,
            'B' => $lleno($c->motivo_consulta) && in_array($c->tipo_atencion, array_keys(ExpedienteConsulta::TIPOS_ATENCION), true),
            'C' => $p && ($lleno($p->antecedentes) || $lleno($p->medicamentos) || $lleno($p->cirugias) || $lleno($p->alergias)),
            'D' => $p && $lleno($p->enfermedades_familiares),
            'E' => mb_strlen(trim((string) $c->anamnesis)) >= 20,
            'F' => collect(['pa', 'fc', 'fr', 'temp', 'peso', 'talla'])->every(fn ($k) => $lleno($ef[$k] ?? null)),
            'G' => $lleno($c->revision_sistemas),
            'H' => $lleno($ef['notas'] ?? null),
            'I' => $lleno($c->diagnostico) && $this->cie10Valido($c->codigo_cie10)
                && in_array($c->tipo_diagnostico, array_keys(ExpedienteConsulta::TIPOS_DIAGNOSTICO), true),
            'J' => $lleno($c->tratamiento),
            'K' => $c->medico && $lleno($c->medico->user?->cedula) && $lleno($c->medico->firma_path),
            'T' => $c->fecha && $c->created_at && abs($c->created_at->diffInHours($c->fecha)) <= 24,
        ];

        $criterios = [];
        foreach (self::CRITERIOS as $clave => [$titulo, $detalle, $peso]) {
            $criterios[$clave] = ['titulo' => $titulo, 'detalle' => $detalle, 'peso' => $peso, 'ok' => (bool) $ok[$clave]];
        }

        return ['criterios' => $criterios, 'puntaje' => self::puntaje($criterios)];
    }

    /** Puntaje 0–100 ponderado a partir de criterios con 'ok' (sirve también para la evaluación manual) */
    public static function puntaje(array $criterios): int
    {
        $total = $logrado = 0.0;
        foreach (self::CRITERIOS as $clave => [, , $peso]) {
            $total += $peso;
            $logrado += !empty($criterios[$clave]['ok']) ? $peso : 0;
        }

        return $total > 0 ? (int) round($logrado / $total * 100) : 0;
    }

    /** Clases del semáforo para un puntaje */
    public static function tono(int $puntaje): string
    {
        return $puntaje >= self::UMBRAL_BUENO ? 'bg-emerald-50 text-emerald-700'
            : ($puntaje >= self::UMBRAL_ACEPTABLE ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700');
    }

    /** Evalúa varias consultas precargando los códigos CIE-10 en una sola consulta */
    public function evaluarVarias(Collection $consultas): Collection
    {
        $codigos = $consultas->pluck('codigo_cie10')->filter()->map(fn ($c) => strtoupper(trim($c)))->unique()->values();
        if ($codigos->isNotEmpty()) {
            $existentes = Cie10Code::whereIn('codigo', $codigos)->pluck('codigo')->map(fn ($c) => strtoupper($c))->flip();
            foreach ($codigos as $codigo) {
                $this->cie10[$codigo] = isset($existentes[$codigo]);
            }
        }

        return $consultas->mapWithKeys(fn (ExpedienteConsulta $c) => [$c->id => $this->evaluar($c)]);
    }

    private function cie10Valido(?string $codigo): bool
    {
        $codigo = strtoupper(trim((string) $codigo));
        if ($codigo === '') {
            return false;
        }

        return $this->cie10[$codigo] ??= Cie10Code::where('codigo', $codigo)->exists();
    }
}
