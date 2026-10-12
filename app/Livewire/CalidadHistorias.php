<?php

namespace App\Livewire;

use App\Models\AuditoriaHc;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\PlanAccion;
use App\Services\CalidadHistoriaService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Auditoría de la calidad del llenado de la historia clínica (formulario MSP 002):
 * cumplimiento por criterio y por médico, y muestra aleatoria para la auditoría manual.
 */
class CalidadHistorias extends Component
{
    use WithPagination;

    /** Máximo de consultas que se evalúan en pantalla por período */
    private const LIMITE = 2000;

    public string $desde = '';
    public string $hasta = '';
    public string $medico = '';
    public string $especialidad = '';
    public int $tamanoMuestra = 10;

    /** IDs de la muestra aleatoria generada */
    #[Locked]
    public array $muestra = [];

    public function mount(): void
    {
        $this->desde = now()->subDays(30)->toDateString();
        $this->hasta = today()->toDateString();
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['desde', 'hasta', 'medico', 'especialidad'], true)) {
            $this->muestra = [];
            $this->resetPage();
        }
    }

    private function consultas()
    {
        $desde = strtotime($this->desde) ? $this->desde : now()->subDays(30)->toDateString();
        $hasta = strtotime($this->hasta) ? $this->hasta : today()->toDateString();

        return ExpedienteConsulta::query()
            ->with('paciente', 'medico.user', 'especialidad')
            ->whereDate('fecha', '>=', $desde)
            ->whereDate('fecha', '<=', $hasta)
            ->when($this->medico !== '', fn ($q) => $q->where('medico_id', (int) $this->medico))
            ->when($this->especialidad !== '', fn ($q) => $q->where('especialidad_id', (int) $this->especialidad))
            ->latest('fecha')
            ->take(self::LIMITE);
    }

    public function generarMuestra(): void
    {
        $this->authorize('auditoria.gestionar');
        $this->validate(['tamanoMuestra' => 'required|integer|min:1|max:100'], [], ['tamanoMuestra' => 'tamaño de la muestra']);

        $ids = $this->consultas()->pluck('id');
        $this->muestra = $ids->shuffle()->take($this->tamanoMuestra)->values()->all();

        $this->dispatch('notify', message: count($this->muestra) . ' consultas seleccionadas al azar para auditoría.', type: 'success');
    }

    public function quitarMuestra(): void
    {
        $this->muestra = [];
    }

    public function render(CalidadHistoriaService $calidad)
    {
        $consultas = $this->consultas()->get();
        $evaluaciones = $calidad->evaluarVarias($consultas);
        $n = $consultas->count();

        $porCriterio = collect(CalidadHistoriaService::CRITERIOS)->map(fn ($c, $clave) => [
            'clave' => $clave,
            'titulo' => $c[0],
            'pct' => $n ? (int) round($evaluaciones->filter(fn ($e) => $e['criterios'][$clave]['ok'])->count() / $n * 100) : 0,
        ])->sortBy('pct')->values();

        $porMedico = $consultas->groupBy('medico_id')->map(fn ($grupo) => [
            'medico' => $grupo->first()->medico?->nombre_completo ?? '—',
            'consultas' => $grupo->count(),
            'pct' => (int) round($grupo->avg(fn ($c) => $evaluaciones[$c->id]['puntaje'])),
        ])->sortBy('pct')->values();

        // Tabla: la muestra si existe; si no, todas ordenadas de menor a mayor puntaje
        $filas = $this->muestra
            ? $consultas->whereIn('id', $this->muestra)
            : $consultas->sortBy(fn ($c) => $evaluaciones[$c->id]['puntaje']);
        $pagina = $this->getPage();
        $tabla = new LengthAwarePaginator($filas->forPage($pagina, 15)->values(), $filas->count(), 15, $pagina, ['path' => request()->url()]);

        $auditadas = AuditoriaHc::whereIn('consulta_id', $tabla->pluck('id'))->where('estado', 'cerrada')
            ->get(['id', 'consulta_id', 'puntaje'])->keyBy('consulta_id');

        return view('livewire.calidad-historias', [
            'n' => $n,
            'promedio' => $n ? (int) round($evaluaciones->avg('puntaje')) : 0,
            'buenas' => $n ? (int) round($evaluaciones->filter(fn ($e) => $e['puntaje'] >= CalidadHistoriaService::UMBRAL_BUENO)->count() / $n * 100) : 0,
            'auditoriasPeriodo' => AuditoriaHc::where('estado', 'cerrada')->whereBetween('fecha', [$this->desde ?: today(), $this->hasta ?: today()])->count(),
            'planesAbiertos' => PlanAccion::where('estado', '!=', 'cumplido')->count(),
            'planesVencidos' => PlanAccion::where('estado', '!=', 'cumplido')->whereDate('fecha_limite', '<', today())->count(),
            'porCriterio' => $porCriterio,
            'porMedico' => $porMedico,
            'tabla' => $tabla,
            'evaluaciones' => $evaluaciones,
            'auditadas' => $auditadas,
            'limitado' => $n >= self::LIMITE,
            'medicos' => Medico::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
            'especialidades' => Especialidad::orderBy('nombre')->get(['id', 'nombre']),
        ])->layout('layouts.app');
    }
}
