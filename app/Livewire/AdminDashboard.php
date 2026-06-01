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
    public string $periodo = 'mensual';

    public function render()
    {
        $this->authorize('configuracion.editar');

        $totalPacientes = Paciente::count();
        $totalTurnos = Turno::count();
        $totalConsultas = ExpedienteConsulta::count();
        $totalFacturado = Factura::where('estado', 'pagada')->sum('total');

        $porSexo = Paciente::select('sexo', DB::raw('count(*) as total'))
            ->whereNotNull('sexo')
            ->groupBy('sexo')
            ->pluck('total', 'sexo');

        $porCiudad = Paciente::select('ciudad', DB::raw('count(*) as total'))
            ->whereNotNull('ciudad')
            ->where('ciudad', '!=', '')
            ->groupBy('ciudad')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $rangosEdad = [
            '0-12' => Paciente::whereNotNull('fecha_nacimiento')->get()->filter(fn ($p) => $p->edad !== null && $p->edad <= 12)->count(),
            '13-18' => Paciente::whereNotNull('fecha_nacimiento')->get()->filter(fn ($p) => $p->edad !== null && $p->edad >= 13 && $p->edad <= 18)->count(),
            '19-35' => Paciente::whereNotNull('fecha_nacimiento')->get()->filter(fn ($p) => $p->edad !== null && $p->edad >= 19 && $p->edad <= 35)->count(),
            '36-50' => Paciente::whereNotNull('fecha_nacimiento')->get()->filter(fn ($p) => $p->edad !== null && $p->edad >= 36 && $p->edad <= 50)->count(),
            '51-65' => Paciente::whereNotNull('fecha_nacimiento')->get()->filter(fn ($p) => $p->edad !== null && $p->edad >= 51 && $p->edad <= 65)->count(),
            '65+' => Paciente::whereNotNull('fecha_nacimiento')->get()->filter(fn ($p) => $p->edad !== null && $p->edad > 65)->count(),
        ];

        $turnosUltimosMeses = Turno::select(
            DB::raw("DATE_FORMAT(fecha, '%Y-%m') as mes"),
            DB::raw('count(*) as total'),
            DB::raw("SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados"),
            DB::raw("SUM(CASE WHEN estado = 'cancelado' THEN 1 ELSE 0 END) as cancelados")
        )
            ->where('fecha', '>=', Carbon::today()->subMonths(6))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $motivosData = ExpedienteConsulta::select(
            'motivo_consulta',
            'especialidad_id',
            DB::raw('count(*) as total')
        )
            ->with('especialidad')
            ->whereNotNull('motivo_consulta')
            ->groupBy('motivo_consulta', 'especialidad_id')
            ->orderBy('motivo_consulta')
            ->get();

        $motivosLabels = $motivosData->groupBy('motivo_consulta')
            ->map(fn($g) => $g->sum('total'))
            ->sortDesc()
            ->take(10)
            ->keys()
            ->map(fn($m) => Str::limit($m, 42));

        $especialidades = $motivosData->pluck('especialidad.nombre')->unique()->filter()->values();
        $especialidadColores = ['#1D9E75', '#378ADD', '#D4537E', '#BA7517'];

        $motivosGrid = [];
        foreach ($motivosData as $row) {
            $key = Str::limit($row->motivo_consulta, 42);
            $esp = $row->especialidad?->nombre ?? 'Sin especificar';
            $motivosGrid[$key][$esp] = ($motivosGrid[$key][$esp] ?? 0) + $row->total;
        }

        $motivosDatasets = $especialidades->map(fn($esp, $i) => [
            'label' => $esp,
            'data' => $motivosLabels->map(fn($ml) => $motivosGrid[$ml][$esp] ?? 0)->values(),
            'backgroundColor' => $especialidadColores[$i % count($especialidadColores)],
        ]);

        $facturacionMensual = Factura::select(
            DB::raw("DATE_FORMAT(fecha, '%Y-%m') as mes"),
            DB::raw('count(*) as total_facturas'),
            DB::raw('COALESCE(SUM(total), 0) as monto')
        )
            ->where('estado', 'pagada')
            ->where('fecha', '>=', Carbon::today()->subMonths(6))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Heatmap: turnos por día de semana y hora
        $heatmap = Turno::select(
            DB::raw("DAYOFWEEK(fecha) as dia_num"),
            DB::raw("HOUR(hora_registro) as hora"),
            DB::raw('count(*) as total')
        )
            ->whereNotNull('hora_registro')
            ->where('fecha', '>=', Carbon::today()->subMonths(3))
            ->where(DB::raw("DAYOFWEEK(fecha)"), '<=', 7)
            ->where(DB::raw("HOUR(hora_registro)"), '>=', 8)
            ->where(DB::raw("HOUR(hora_registro)"), '<=', 19)
            ->groupBy('dia_num', 'hora')
            ->orderBy('dia_num')
            ->orderBy('hora')
            ->get();

        $diasSemana = [2 => 'Lun', 3 => 'Mar', 4 => 'Mie', 5 => 'Jue', 6 => 'Vie', 7 => 'Sab', 1 => 'Dom'];
        $horas = range(8, 19);
        $heatGrid = [];
        foreach ($horas as $h) {
            foreach ($diasSemana as $num => $label) {
                $heatGrid[$h][$num] = 0;
            }
        }
        $maxHeat = 0;
        foreach ($heatmap as $row) {
            $heatGrid[$row->hora][$row->dia_num] = $row->total;
            $maxHeat = max($maxHeat, $row->total);
        }

        // Diagnósticos más frecuentes
        $topDiagnosticos = ExpedienteConsulta::select(
            'codigo_cie10',
            'diagnostico',
            DB::raw('count(*) as total')
        )
            ->whereNotNull('codigo_cie10')
            ->where('codigo_cie10', '!=', '')
            ->groupBy('codigo_cie10', 'diagnostico')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($d) => [
                'codigo' => $d->codigo_cie10,
                'diagnostico' => Str::limit($d->diagnostico ?? 'Sin diagnóstico', 40),
                'total' => $d->total,
            ]);

        return view('livewire.admin-dashboard', [
            'totalPacientes' => $totalPacientes,
            'totalTurnos' => $totalTurnos,
            'totalConsultas' => $totalConsultas,
            'totalFacturado' => $totalFacturado,
            'porSexo' => $porSexo,
            'porCiudad' => $porCiudad,
            'rangosEdad' => $rangosEdad,
            'turnosUltimosMeses' => $turnosUltimosMeses,
            'motivosLabels' => $motivosLabels,
            'motivosDatasets' => $motivosDatasets,
            'facturacionMensual' => $facturacionMensual,
            'heatGrid' => $heatGrid,
            'maxHeat' => $maxHeat,
            'diasSemana' => $diasSemana,
            'horas' => $horas,
            'topDiagnosticos' => $topDiagnosticos,
        ])->layout('layouts.app');
    }
}
