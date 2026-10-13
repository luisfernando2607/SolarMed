<?php

namespace App\Livewire;

use App\Models\Encuesta;
use App\Models\Medico;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Satisfacción del usuario externo: promedio por pregunta, índice de recomendación (NPS),
 * resultados por médico y comentarios. Los comentarios se muestran sin datos del paciente.
 */
class ReporteSatisfaccion extends Component
{
    #[Url(except: '')]
    public string $desde = '';
    #[Url(except: '')]
    public string $hasta = '';
    #[Url(except: '')]
    public string $medico = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAny(['reportes.ver', 'auditoria.ver']), 403);
        $this->desde = $this->desde ?: now()->subDays(89)->toDateString();
        $this->hasta = $this->hasta ?: now()->toDateString();
    }

    /** NPS = % promotores (9-10) − % detractores (0-6) */
    public static function nps(Collection $recomendaciones): ?int
    {
        $n = $recomendaciones->count();
        if (!$n) {
            return null;
        }

        return (int) round(($recomendaciones->filter(fn ($r) => $r >= 9)->count() - $recomendaciones->filter(fn ($r) => $r <= 6)->count()) * 100 / $n);
    }

    public function render()
    {
        $desde = strtotime($this->desde) ? Carbon::parse($this->desde)->startOfDay() : now()->subDays(89)->startOfDay();
        $hasta = strtotime($this->hasta) ? Carbon::parse($this->hasta)->endOfDay() : now()->endOfDay();
        $filtro = fn ($q) => $q->whereBetween('created_at', [$desde, $hasta])
            ->when($this->medico !== '', fn ($q) => $q->where('medico_id', (int) $this->medico));

        $creadas = Encuesta::query()->tap($filtro)->count();
        $resp = Encuesta::with('medico:id,nombres,apellidos')->respondidas()->tap($filtro)->latest('respondida_at')->get();

        $preguntas = collect(Encuesta::CORTAS)->map(fn ($texto, $campo) => [
            'texto' => $texto,
            'promedio' => $resp->count() ? round($resp->avg($campo), 1) : null,
        ]);

        $porMedico = $resp->groupBy('medico_id')->map(fn ($g) => [
            'nombre' => $g->first()->medico?->nombre_completo ?? '—',
            'respuestas' => $g->count(),
            'promedio' => round($g->map->promedio()->filter()->avg(), 1),
            'nps' => self::nps($g->pluck('recomendacion')),
        ])->sortByDesc('promedio')->values();

        return view('livewire.reporte-satisfaccion', [
            'kpis' => [
                'respuestas' => $resp->count(),
                'creadas' => $creadas,
                'tasa' => $creadas ? (int) round($resp->count() * 100 / $creadas) : null,
                'promedio' => $resp->count() ? round($resp->map->promedio()->filter()->avg(), 1) : null,
                'nps' => self::nps($resp->pluck('recomendacion')),
            ],
            'preguntas' => $preguntas,
            'porMedico' => $porMedico,
            'comentarios' => $resp->filter(fn ($e) => filled($e->comentario))->take(15),
            'medicos' => Medico::orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
        ])->layout('layouts.app');
    }
}
