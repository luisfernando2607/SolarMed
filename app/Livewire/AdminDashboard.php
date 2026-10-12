<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\ExpedienteConsulta;
use App\Models\Factura;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminDashboard extends Component
{

    // Expresiones de fecha según el motor (MySQL en producción, SQLite en las pruebas)
    private function sqlMes(string $col): string
    {
        return DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', {$col})" : "DATE_FORMAT({$col}, '%Y-%m')";
    }

    /** 1 = domingo … 7 = sábado, como DAYOFWEEK de MySQL */
    private function sqlDiaSemana(string $col): string
    {
        return DB::getDriverName() === 'sqlite' ? "(CAST(strftime('%w', {$col}) AS INTEGER) + 1)" : "DAYOFWEEK({$col})";
    }

    private function sqlHora(string $col): string
    {
        return DB::getDriverName() === 'sqlite' ? "CAST(strftime('%H', {$col}) AS INTEGER)" : "HOUR({$col})";
    }

    /** Variación porcentual; null si no hay base de comparación */
    private function variacion(float $actual, float $anterior): ?float
    {
        return $anterior > 0 ? round(($actual - $anterior) / $anterior * 100, 1) : null;
    }

    /** Últimos N meses como ['2026-05' => 'may', …] (incluye meses sin datos) */
    private function ultimosMeses(int $n): array
    {
        $meses = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $m = Carbon::today()->startOfMonth()->subMonths($i);
            $meses[$m->format('Y-m')] = ucfirst($m->translatedFormat('M'));
        }
        return $meses;
    }

    public function render()
    {
        $this->authorize('configuracion.editar');

        // ── Indicadores del mes (con el mes anterior para comparar) ──
        [$iniMes, $finMes] = [now()->startOfMonth(), now()->endOfMonth()];
        [$iniAnt, $finAnt] = [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()];

        $facturasMes = Factura::where('estado', 'pagada')->whereBetween('fecha', [$iniMes, $finMes]);
        $facturasAnt = Factura::where('estado', 'pagada')->whereBetween('fecha', [$iniAnt, $finAnt]);
        $ingresosMes = (float) (clone $facturasMes)->sum('total');
        $ingresosAnt = (float) (clone $facturasAnt)->sum('total');
        $nFacturasMes = (clone $facturasMes)->count();
        $nFacturasAnt = (clone $facturasAnt)->count();
        $consultasMes = ExpedienteConsulta::whereBetween('fecha', [$iniMes, $finMes])->count();
        $consultasAnt = ExpedienteConsulta::whereBetween('fecha', [$iniAnt, $finAnt])->count();
        $pacientesMes = Paciente::whereBetween('created_at', [$iniMes, $finMes])->count();
        $pacientesAnt = Paciente::whereBetween('created_at', [$iniAnt, $finAnt])->count();
        $ticketMes = $nFacturasMes ? $ingresosMes / $nFacturasMes : 0;
        $ticketAnt = $nFacturasAnt ? $ingresosAnt / $nFacturasAnt : 0;

        $kpis = [
            ['titulo' => 'Ingresos del mes', 'valor' => '$' . number_format($ingresosMes, 2), 'delta' => $this->variacion($ingresosMes, $ingresosAnt), 'detalle' => "{$nFacturasMes} facturas pagadas"],
            ['titulo' => 'Consultas del mes', 'valor' => number_format($consultasMes), 'delta' => $this->variacion($consultasMes, $consultasAnt), 'detalle' => 'Mes anterior: ' . number_format($consultasAnt)],
            ['titulo' => 'Pacientes nuevos', 'valor' => number_format($pacientesMes), 'delta' => $this->variacion($pacientesMes, $pacientesAnt), 'detalle' => number_format(Paciente::count()) . ' pacientes en total'],
            ['titulo' => 'Ticket promedio', 'valor' => '$' . number_format($ticketMes, 2), 'delta' => $this->variacion($ticketMes, $ticketAnt), 'detalle' => 'Por factura pagada'],
        ];

        // ── Series de 6 meses (con meses vacíos en cero) ──
        $meses = $this->ultimosMeses(6);
        $desde = Carbon::today()->startOfMonth()->subMonths(5);

        $facturacion = Factura::select(DB::raw($this->sqlMes('fecha') . ' as mes'), DB::raw('COALESCE(SUM(total), 0) as monto'))
            ->where('estado', 'pagada')->where('fecha', '>=', $desde)
            ->groupBy('mes')->pluck('monto', 'mes');

        $turnos = Turno::select(
            DB::raw($this->sqlMes('fecha') . ' as mes'),
            DB::raw("SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados"),
            DB::raw("SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados"),
            DB::raw('count(*) as total')
        )->where('fecha', '>=', $desde)->groupBy('mes')->get()->keyBy('mes');

        $totalTurnos6 = (int) $turnos->sum('total');
        $tasaCancelacion = $totalTurnos6 ? round($turnos->sum('cancelados') / $totalTurnos6 * 100, 1) : 0;

        $series = [
            'meses' => array_values($meses),
            'facturacion' => array_map(fn ($m) => round((float) ($facturacion[$m] ?? 0), 2), array_keys($meses)),
            'completados' => array_map(fn ($m) => (int) ($turnos[$m]->completados ?? 0), array_keys($meses)),
            'cancelados' => array_map(fn ($m) => (int) ($turnos[$m]->cancelados ?? 0), array_keys($meses)),
            'otros' => array_map(fn ($m) => (int) (($turnos[$m]->total ?? 0) - ($turnos[$m]->completados ?? 0) - ($turnos[$m]->cancelados ?? 0)), array_keys($meses)),
        ];

        // ── Rankings ──
        $motivos = ExpedienteConsulta::select('motivo_consulta', DB::raw('count(*) as total'))
            ->whereNotNull('motivo_consulta')->where('motivo_consulta', '!=', '')
            ->groupBy('motivo_consulta')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($r) => ['etiqueta' => Str::limit($r->motivo_consulta, 38), 'total' => (int) $r->total]);

        $diagnosticos = ExpedienteConsulta::select('codigo_cie10', 'diagnostico', DB::raw('count(*) as total'))
            ->whereNotNull('codigo_cie10')->where('codigo_cie10', '!=', '')
            ->groupBy('codigo_cie10', 'diagnostico')->orderByDesc('total')->limit(8)->get()
            ->map(fn ($d) => ['etiqueta' => $d->codigo_cie10 . ' · ' . Str::limit($d->diagnostico ?? 'Sin diagnóstico', 30), 'total' => (int) $d->total]);

        $ciudades = Paciente::select('ciudad', DB::raw('count(*) as total'))
            ->whereNotNull('ciudad')->where('ciudad', '!=', '')
            ->groupBy('ciudad')->orderByDesc('total')->limit(6)->get();

        $porSexo = Paciente::select('sexo', DB::raw('count(*) as total'))
            ->whereNotNull('sexo')->groupBy('sexo')->pluck('total', 'sexo');

        // Edad: una sola consulta (antes cargaba todos los pacientes 6 veces)
        $edades = Paciente::whereNotNull('fecha_nacimiento')->pluck('fecha_nacimiento')->map(fn ($f) => Carbon::parse($f)->age);
        $rangosEdad = [
            '0–12' => $edades->filter(fn ($e) => $e <= 12)->count(),
            '13–18' => $edades->filter(fn ($e) => $e >= 13 && $e <= 18)->count(),
            '19–35' => $edades->filter(fn ($e) => $e >= 19 && $e <= 35)->count(),
            '36–50' => $edades->filter(fn ($e) => $e >= 36 && $e <= 50)->count(),
            '51–65' => $edades->filter(fn ($e) => $e >= 51 && $e <= 65)->count(),
            '65+' => $edades->filter(fn ($e) => $e > 65)->count(),
        ];

        // ── Mapa de calor: turnos por día y hora (3 meses) ──
        $heatmap = Turno::select(
            DB::raw($this->sqlDiaSemana('fecha') . ' as dia_num'),
            DB::raw($this->sqlHora('hora_registro') . ' as hora'),
            DB::raw('count(*) as total')
        )
            ->whereNotNull('hora_registro')
            ->where('fecha', '>=', Carbon::today()->subMonths(3))
            ->where(DB::raw($this->sqlHora('hora_registro')), '>=', 7)
            ->where(DB::raw($this->sqlHora('hora_registro')), '<=', 19)
            ->groupBy('dia_num', 'hora')->get();

        $diasSemana = [2 => 'Lun', 3 => 'Mar', 4 => 'Mié', 5 => 'Jue', 6 => 'Vie', 7 => 'Sáb', 1 => 'Dom'];
        $horas = range(7, 19);
        $heatGrid = [];
        $maxHeat = 0;
        foreach ($heatmap as $row) {
            $heatGrid[(int) $row->hora][(int) $row->dia_num] = (int) $row->total;
            $maxHeat = max($maxHeat, (int) $row->total);
        }

        return view('livewire.admin-dashboard', [
            'kpis' => $kpis,
            'series' => $series,
            'tasaCancelacion' => $tasaCancelacion,
            'totalTurnos6' => $totalTurnos6,
            'totalFacturado6' => array_sum($series['facturacion']),
            'motivos' => $motivos,
            'diagnosticos' => $diagnosticos,
            'ciudades' => $ciudades,
            'porSexo' => $porSexo,
            'rangosEdad' => $rangosEdad,
            'heatGrid' => $heatGrid,
            'maxHeat' => $maxHeat,
            'diasSemana' => $diasSemana,
            'horas' => $horas,
        ])->layout('layouts.app');
    }
}
