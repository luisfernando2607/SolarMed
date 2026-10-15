<?php

namespace App\Services;

use App\Models\ControlPrenatal;
use App\Models\Embarazo;
use App\Models\ExpedienteConsulta;
use App\Models\Paciente;
use App\Models\Preparacion;
use Carbon\Carbon;

/**
 * Series de evolución del paciente (signos vitales y medidas) para los gráficos de la ficha.
 *
 * Fuentes: preparación de enfermería, examen físico de la consulta y controles prenatales.
 * Si el mismo día hay un valor de varias fuentes se usa uno solo, con esta prioridad:
 * enfermería > control prenatal > consulta (la consulta suele copiar lo que tomó enfermería).
 * Dentro de una misma fuente y día queda la última medición.
 */
class EvolucionPacienteService
{
    public const ENFERMERIA = 'Enfermería';
    public const CONSULTA = 'Consulta';
    public const PRENATAL = 'Control prenatal';

    private const PRIORIDAD = [self::ENFERMERIA => 3, self::PRENATAL => 2, self::CONSULTA => 1];

    /** [clave => [título, unidad, decimales]] de las medidas generales */
    public const MEDIDAS = [
        'sistolica' => ['Presión sistólica', 'mmHg', 0],
        'diastolica' => ['Presión diastólica', 'mmHg', 0],
        'peso' => ['Peso', 'kg', 1],
        'imc' => ['IMC', 'kg/m²', 1],
        'glucosa' => ['Glucosa capilar', 'mg/dl', 0],
        'saturacion' => ['Saturación de oxígeno', '%', 0],
        'fc' => ['Frecuencia cardíaca', 'lpm', 0],
    ];

    /** "120/80" → [120, 80]; cualquier otra cosa → [null, null] */
    public static function presion(?string $pa): array
    {
        if ($pa !== null && preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $pa, $m)) {
            [$s, $d] = [(int) $m[1], (int) $m[2]];
            if ($s >= 50 && $s <= 300 && $d >= 20 && $d <= 200 && $d < $s) {
                return [$s, $d];
            }
        }

        return [null, null];
    }

    private static function numero(mixed $v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        $v = str_replace(',', '.', trim((string) $v));

        return is_numeric($v) ? (float) $v : null;
    }

    private static function imc(?float $peso, ?float $talla): ?float
    {
        // La talla se registra en metros; si alguien la escribió en centímetros se convierte
        if ($talla && $talla > 3) {
            $talla /= 100;
        }

        return $peso && $talla && $talla > 0.3 ? round($peso / ($talla ** 2), 1) : null;
    }

    /**
     * @return array<string, array<int, array{fecha: string, x: int, valor: float, fuente: string}>>
     *         clave de medida => puntos ordenados por fecha
     */
    public function medidas(Paciente $paciente): array
    {
        $crudos = []; // [medida][dia] => [fuente, fecha, valor]

        $agregar = function (string $medida, Carbon $fecha, ?float $valor, string $fuente) use (&$crudos) {
            if ($valor === null) {
                return;
            }
            $dia = $fecha->toDateString();
            $actual = $crudos[$medida][$dia] ?? null;
            $gana = !$actual
                || self::PRIORIDAD[$fuente] > self::PRIORIDAD[$actual[0]]
                || (self::PRIORIDAD[$fuente] === self::PRIORIDAD[$actual[0]] && $fecha->gte($actual[1]));
            if ($gana) {
                $crudos[$medida][$dia] = [$fuente, $fecha, $valor];
            }
        };

        $preparaciones = Preparacion::where('paciente_id', $paciente->id)->orderBy('fecha')->get();
        foreach ($preparaciones as $p) {
            $agregar('sistolica', $p->fecha, self::numero($p->presion_sistolica), self::ENFERMERIA);
            $agregar('diastolica', $p->fecha, self::numero($p->presion_diastolica), self::ENFERMERIA);
            $agregar('peso', $p->fecha, self::numero($p->peso), self::ENFERMERIA);
            $agregar('imc', $p->fecha, self::imc(self::numero($p->peso), self::numero($p->talla)), self::ENFERMERIA);
            $agregar('glucosa', $p->fecha, self::numero($p->glucosa_capilar), self::ENFERMERIA);
            $agregar('saturacion', $p->fecha, self::numero($p->saturacion), self::ENFERMERIA);
            $agregar('fc', $p->fecha, self::numero($p->frecuencia_cardiaca), self::ENFERMERIA);
        }

        // Consultas cuyo turno ya tiene preparación: sus signos vienen de enfermería, no se duplican
        $turnosConPreparacion = $preparaciones->pluck('turno_id')->filter()->all();
        $consultas = ExpedienteConsulta::where('paciente_id', $paciente->id)
            ->when($turnosConPreparacion, fn ($q) => $q->where(fn ($w) => $w->whereNull('turno_id')->orWhereNotIn('turno_id', $turnosConPreparacion)))
            ->orderBy('fecha')->get(['id', 'fecha', 'turno_id', 'examen_fisico']);
        foreach ($consultas as $c) {
            $ef = is_array($c->examen_fisico) ? $c->examen_fisico : [];
            [$s, $d] = self::presion($ef['pa'] ?? null);
            $peso = self::numero($ef['peso'] ?? null);
            $agregar('sistolica', $c->fecha, $s !== null ? (float) $s : null, self::CONSULTA);
            $agregar('diastolica', $c->fecha, $d !== null ? (float) $d : null, self::CONSULTA);
            $agregar('peso', $c->fecha, $peso, self::CONSULTA);
            $agregar('imc', $c->fecha, self::numero($ef['imc'] ?? null) ?? self::imc($peso, self::numero($ef['talla'] ?? null)), self::CONSULTA);
            $agregar('fc', $c->fecha, self::numero($ef['fc'] ?? null), self::CONSULTA);
        }

        foreach (ControlPrenatal::where('paciente_id', $paciente->id)->orderBy('fecha')->get() as $cp) {
            [$s, $d] = self::presion($cp->presion_arterial);
            $fecha = $cp->fecha->copy()->setTimeFrom($cp->created_at ?? $cp->fecha);
            $agregar('sistolica', $fecha, $s !== null ? (float) $s : null, self::PRENATAL);
            $agregar('diastolica', $fecha, $d !== null ? (float) $d : null, self::PRENATAL);
            $agregar('peso', $fecha, self::numero($cp->peso_materno), self::PRENATAL);
        }

        $resultado = [];
        foreach (array_keys(self::MEDIDAS) as $medida) {
            $puntos = [];
            foreach ($crudos[$medida] ?? [] as [$fuente, $fecha, $valor]) {
                $puntos[] = [
                    'fecha' => $fecha->format('d/m/Y'),
                    'x' => $fecha->copy()->startOfDay()->getTimestampMs(),
                    'valor' => round($valor, self::MEDIDAS[$medida][2]),
                    'fuente' => $fuente,
                ];
            }
            usort($puntos, fn ($a, $b) => $a['x'] <=> $b['x']);
            $resultado[$medida] = $puntos;
        }

        return $resultado;
    }

    /**
     * Embarazo en control: ganancia de peso y altura uterina por semana de gestación.
     *
     * @return array{ganancia: array, altura_uterina: array, tolerancia_au: float}|null
     */
    public function embarazo(Paciente $paciente): ?array
    {
        $embarazo = Embarazo::where('paciente_id', $paciente->id)->where('estado', 'activo')->latest('fum')->first();
        if (!$embarazo) {
            return null;
        }

        $controles = ControlPrenatal::where('embarazo_id', $embarazo->id)->orderBy('fecha')->orderBy('id')->get();
        $semana = fn (ControlPrenatal $c) => $c->semanas_gestacion !== null
            ? round($c->semanas_gestacion + ((int) $c->dias_gestacion) / 7, 1)
            : ($embarazo->fum ? round($embarazo->fum->diffInDays($c->fecha) / 7, 1) : null);

        $base = null;
        $ganancia = [];
        $altura = [];
        foreach ($controles as $c) {
            $sem = $semana($c);
            if ($sem === null) {
                continue;
            }
            $peso = self::numero($c->peso_materno);
            if ($peso !== null) {
                $base ??= $peso;
                $ganancia[] = ['semana' => $sem, 'valor' => round($peso - $base, 1), 'peso' => $peso, 'fecha' => $c->fecha->format('d/m/Y'), 'fuente' => self::PRENATAL];
            }
            $au = self::numero($c->altura_uterina);
            if ($au !== null) {
                $altura[] = ['semana' => $sem, 'valor' => $au, 'fecha' => $c->fecha->format('d/m/Y'), 'fuente' => self::PRENATAL];
            }
        }

        return [
            'ganancia' => $ganancia,
            'altura_uterina' => $altura,
            'tolerancia_au' => (float) app(AjustesService::class)->get('prenatal.au_tolerancia_cm'),
        ];
    }
}
