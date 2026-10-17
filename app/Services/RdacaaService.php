<?php

namespace App\Services;

use App\Models\ExpedienteConsulta;
use App\Models\Paciente;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Arma el reporte RDACAA (Registro Diario Automatizado de Consultas y Atenciones Ambulatorias)
 * del MSP del Ecuador: una fila por atención de consulta externa del período, con los bloques
 * A (establecimiento), B (profesional), C (paciente) y D (atención/morbilidad).
 *
 * La estructura sigue el instructivo RDACAA 2.0 (MSP, 2016). El orden de columnas y los catálogos
 * de códigos del archivo oficial pueden ajustarse aquí cuando se disponga del instructivo vigente.
 */
class RdacaaService
{
    /** Cabeceras en orden (bloques A–D) */
    public const CABECERA = [
        // Bloque A — Establecimiento
        'Fecha atención', 'Institución del SNS', 'Establecimiento', 'Unicódigo', 'Tipología',
        'Provincia (est.)', 'Cantón (est.)', 'Parroquia (est.)',
        // Bloque B — Profesional
        'Profesional', 'Cédula profesional', 'Especialidad',
        // Bloque C — Paciente
        'N.º historia clínica', 'Sexo', 'Fecha nacimiento', 'Edad', 'Autoidentificación étnica',
        'Nacionalidad', 'Discapacidad', 'Tipo de seguro', 'Provincia (pac.)', 'Cantón (pac.)', 'Parroquia (pac.)',
        // Bloque D — Atención / morbilidad
        'Tipo de atención', 'CIE-10', 'Diagnóstico', 'Condición del diagnóstico',
    ];

    public function __construct(private MarcaService $marca)
    {
    }

    /** Atenciones de consulta externa del período (mismo universo que el reporte de morbilidad) */
    public function consultas(CarbonInterface $desde, CarbonInterface $hasta, ?int $medicoId = null, ?int $especialidadId = null): Collection
    {
        return ExpedienteConsulta::with([
                'paciente:id,cedula,sexo,fecha_nacimiento,etnia,nacionalidad,discapacidad,seguro,provincia,canton,parroquia',
                'medico:id,nombres,apellidos', 'medico.user:id,cedula', 'especialidad:id,nombre',
            ])
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($medicoId, fn ($q) => $q->where('medico_id', $medicoId))
            ->when($especialidadId, fn ($q) => $q->where('especialidad_id', $especialidadId))
            ->orderBy('fecha')
            ->get();
    }

    /** Una fila RDACAA por atención, en el orden de self::CABECERA */
    public function filas(CarbonInterface $desde, CarbonInterface $hasta, ?int $medicoId = null, ?int $especialidadId = null): Collection
    {
        $est = $this->marca->establecimiento();

        return $this->consultas($desde, $hasta, $medicoId, $especialidadId)->map(function (ExpedienteConsulta $c) use ($est) {
            $p = $c->paciente;
            $edad = $p?->fecha_nacimiento ? (int) $p->fecha_nacimiento->diffInYears($c->fecha) : null;

            return [
                // A
                $c->fecha->format('Y-m-d'),
                $est['institucion'] ?? '',
                $est['nombre'] ?? '',
                $est['unicodigo'] ?? '',
                $est['tipologia'] ?? '',
                $est['provincia'] ?? '',
                $est['canton'] ?? '',
                $est['parroquia'] ?? '',
                // B
                $c->medico?->nombre_completo ?? '',
                $c->medico?->user?->cedula ?? '',
                $c->especialidad?->nombre ?? '',
                // C
                $p?->cedula ?? '',
                ['masculino' => 'H', 'femenino' => 'M'][$p?->sexo] ?? '',
                $p?->fecha_nacimiento?->format('Y-m-d') ?? '',
                $edad ?? '',
                Paciente::ETNIAS[$p?->etnia] ?? '',
                $p?->nacionalidad ?? '',
                Paciente::DISCAPACIDADES[$p?->discapacidad] ?? '',
                Paciente::SEGUROS[$p?->seguro] ?? '',
                $p?->provincia ?? '',
                $p?->canton ?? '',
                $p?->parroquia ?? '',
                // D
                ExpedienteConsulta::TIPOS_ATENCION[$c->tipo_atencion] ?? '',
                $c->codigo_cie10 ?? '',
                $c->diagnostico ?? '',
                ExpedienteConsulta::TIPOS_DIAGNOSTICO[$c->tipo_diagnostico] ?? '',
            ];
        });
    }
}
