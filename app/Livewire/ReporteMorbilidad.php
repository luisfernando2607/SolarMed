<?php

namespace App\Livewire;

use App\Models\Cie10Code;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Producción y morbilidad de la consulta externa: atenciones de primera vez y subsecuentes,
 * distribución por sexo y grupo de edad, y diagnósticos CIE-10 más frecuentes.
 * Uso estadístico de la HC (AM 5216-A Art. 7): la exportación identifica al paciente solo por su número de HC.
 */
class ReporteMorbilidad extends Component
{
    /** Grupos de edad de los reportes de morbilidad del MSP: [etiqueta, desde, hasta] en años cumplidos */
    public const GRUPOS_EDAD = [
        ['< 1 año', 0, 0],
        ['1 a 4', 1, 4],
        ['5 a 9', 5, 9],
        ['10 a 14', 10, 14],
        ['15 a 19', 15, 19],
        ['20 a 49', 20, 49],
        ['50 a 64', 50, 64],
        ['65 y más', 65, 200],
    ];

    #[Url(except: '')]
    public string $desde = '';
    #[Url(except: '')]
    public string $hasta = '';
    #[Url(except: '')]
    public string $medico = '';
    #[Url(except: '')]
    public string $especialidad = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAny(['reportes.ver', 'auditoria.ver']), 403);
        $this->desde = $this->desde ?: now()->startOfMonth()->toDateString();
        $this->hasta = $this->hasta ?: now()->toDateString();
    }

    private function periodo(): array
    {
        $desde = strtotime($this->desde) ? Carbon::parse($this->desde)->startOfDay() : now()->startOfMonth();
        $hasta = strtotime($this->hasta) ? Carbon::parse($this->hasta)->endOfDay() : now()->endOfDay();

        return $desde->lte($hasta) ? [$desde, $hasta] : [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
    }

    private function consultas(): Collection
    {
        [$desde, $hasta] = $this->periodo();

        return ExpedienteConsulta::with('paciente:id,cedula,sexo,fecha_nacimiento', 'medico:id,nombres,apellidos', 'especialidad:id,nombre')
            ->whereBetween('fecha', [$desde, $hasta])
            ->when($this->medico !== '', fn ($q) => $q->where('medico_id', (int) $this->medico))
            ->when($this->especialidad !== '', fn ($q) => $q->where('especialidad_id', (int) $this->especialidad))
            ->orderBy('fecha')
            ->get();
    }

    /** Edad en años cumplidos a la fecha de la atención */
    public static function edadAl(ExpedienteConsulta $c): ?int
    {
        return $c->paciente?->fecha_nacimiento ? (int) $c->paciente->fecha_nacimiento->diffInYears($c->fecha) : null;
    }

    public static function grupoEdad(?int $edad): string
    {
        if ($edad === null) {
            return 'Sin fecha de nacimiento';
        }
        foreach (self::GRUPOS_EDAD as [$etiqueta, $min, $max]) {
            if ($edad >= $min && $edad <= $max) {
                return $etiqueta;
            }
        }

        return '65 y más';
    }

    private static function sexo(ExpedienteConsulta $c): string
    {
        return ['masculino' => 'H', 'femenino' => 'M'][$c->paciente?->sexo] ?? '?';
    }

    public function exportar()
    {
        abort_unless(auth()->user()->canAny(['reportes.ver', 'auditoria.ver']), 403);
        [$desde, $hasta] = $this->periodo();
        $consultas = $this->consultas();
        app(AuditoriaService::class)->registrar('exportar', 'morbilidad', null, null,
            "Producción y morbilidad {$desde->format('d/m/Y')}–{$hasta->format('d/m/Y')} ({$consultas->count()} atenciones)");

        $seguro = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($consultas, $seguro) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Fecha', 'Hora', 'N.º HC', 'Sexo', 'Edad', 'Grupo de edad', 'Tipo de atención', 'CIE-10', 'Diagnóstico', 'Presuntivo/Definitivo', 'Profesional', 'Especialidad'], ';');
            foreach ($consultas as $c) {
                $edad = self::edadAl($c);
                fputcsv($salida, array_map($seguro, [
                    $c->fecha->format('Y-m-d'), $c->fecha->format('H:i'), $c->paciente?->cedula, self::sexo($c), $edad, self::grupoEdad($edad),
                    ['primera' => 'Primera', 'subsecuente' => 'Subsecuente'][$c->tipo_atencion] ?? 'Sin registrar',
                    $c->codigo_cie10, $c->diagnostico,
                    ['presuntivo' => 'Presuntivo', 'definitivo' => 'Definitivo'][$c->tipo_diagnostico] ?? '',
                    $c->medico?->nombre_completo, $c->especialidad?->nombre,
                ]), ';');
            }
            fclose($salida);
        }, 'morbilidad_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Exporta el reporte RDACAA del MSP (una fila por atención, bloques A–D) */
    public function exportarRdacaa(\App\Services\RdacaaService $rdacaa)
    {
        abort_unless(auth()->user()->canAny(['reportes.ver', 'auditoria.ver']), 403);
        [$desde, $hasta] = $this->periodo();
        $medicoId = $this->medico !== '' ? (int) $this->medico : null;
        $especialidadId = $this->especialidad !== '' ? (int) $this->especialidad : null;
        $filas = $rdacaa->filas($desde, $hasta, $medicoId, $especialidadId);

        app(AuditoriaService::class)->registrar('exportar', 'rdacaa', null, null,
            "RDACAA {$desde->format('d/m/Y')}–{$hasta->format('d/m/Y')} ({$filas->count()} atenciones)");

        $seguro = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;

        return response()->streamDownload(function () use ($filas, $seguro) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, \App\Services\RdacaaService::CABECERA, ';');
            foreach ($filas as $fila) {
                fputcsv($salida, array_map($seguro, $fila), ';');
            }
            fclose($salida);
        }, 'rdacaa_' . $desde->format('Ymd') . '_' . $hasta->format('Ymd') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $consultas = $this->consultas();
        $total = $consultas->count();

        // Sexo × grupo de edad (incluye la fila "Sin fecha de nacimiento" solo si hay casos)
        $grupos = array_column(self::GRUPOS_EDAD, 0);
        $matriz = array_fill_keys($grupos, ['H' => 0, 'M' => 0, '?' => 0]);
        foreach ($consultas as $c) {
            $g = self::grupoEdad(self::edadAl($c));
            $matriz[$g] ??= ['H' => 0, 'M' => 0, '?' => 0];
            $matriz[$g][self::sexo($c)]++;
        }

        // Diagnósticos más frecuentes por código CIE-10
        $conCodigo = $consultas->filter(fn ($c) => filled($c->codigo_cie10));
        $porCodigo = $conCodigo->groupBy(fn ($c) => strtoupper(trim($c->codigo_cie10)));
        $descripciones = Cie10Code::whereIn('codigo', $porCodigo->keys())->pluck('descripcion', 'codigo');
        $top = $porCodigo->map(fn ($g, $codigo) => [
            'codigo' => $codigo,
            'descripcion' => $descripciones[$codigo] ?? $g->first()->diagnostico,
            'total' => $g->count(),
            'H' => $g->filter(fn ($c) => self::sexo($c) === 'H')->count(),
            'M' => $g->filter(fn ($c) => self::sexo($c) === 'M')->count(),
        ])->sortByDesc('total')->take(10)->values();

        $porMedico = $consultas->groupBy('medico_id')->map(fn ($g) => [
            'nombre' => $g->first()->medico?->nombre_completo ?? '—',
            'total' => $g->count(),
            'primera' => $g->where('tipo_atencion', 'primera')->count(),
            'subsecuente' => $g->where('tipo_atencion', 'subsecuente')->count(),
            'con_cie' => $g->filter(fn ($c) => filled($c->codigo_cie10))->count(),
        ])->sortByDesc('total')->values();

        return view('livewire.reporte-morbilidad', [
            'kpis' => [
                'total' => $total,
                'pacientes' => $consultas->pluck('paciente_id')->unique()->count(),
                'primera' => $consultas->where('tipo_atencion', 'primera')->count(),
                'subsecuente' => $consultas->where('tipo_atencion', 'subsecuente')->count(),
                'sin_tipo' => $consultas->whereNull('tipo_atencion')->count(),
                'con_cie' => $conCodigo->count(),
            ],
            'matriz' => array_filter($matriz, fn ($fila, $g) => in_array($g, $grupos, true) || array_sum($fila) > 0, ARRAY_FILTER_USE_BOTH),
            'top' => $top,
            'porMedico' => $porMedico,
            'medicos' => Medico::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
            'especialidades' => Especialidad::orderBy('nombre')->get(['id', 'nombre']),
        ])->layout('layouts.app');
    }
}
