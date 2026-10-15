<?php

namespace App\Livewire;

use App\Models\Medico;
use App\Models\Teleconsulta;
use App\Services\TelemedicinaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Teleconsultas agendadas y realizadas, con los indicadores que pide la Norma Técnica de Telesalud
 * (num. 7.3.4: teleconsultas otorgadas, efectividad efectiva/parcial/fallida, duración).
 */
class ListaTeleconsultas extends Component
{
    use WithPagination;

    #[Url(except: 'proximas')]
    public string $vista = 'proximas'; // proximas | pasadas | todas
    #[Url(except: '')]
    public string $medico = '';

    public function mount(): void
    {
        // Un médico ve por defecto sus propias teleconsultas
        if (!Auth::user()->can('turnos.gestionar')) {
            $this->medico = (string) (Medico::where('user_id', Auth::id())->value('id') ?? '');
        }
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $base = Teleconsulta::query()->when($this->medico !== '', fn ($q) => $q->where('medico_id', (int) $this->medico));

        $lista = (clone $base)->with('paciente', 'medico')
            ->when($this->vista === 'proximas', fn ($q) => $q->whereIn('estado', ['programada', 'en_curso'])->where('fecha_hora', '>=', now()->subHours(2))->orderBy('fecha_hora'))
            ->when($this->vista === 'pasadas', fn ($q) => $q->where(fn ($w) => $w->whereNotIn('estado', ['programada', 'en_curso'])->orWhere('fecha_hora', '<', now()->subHours(2)))->orderByDesc('fecha_hora'))
            ->when($this->vista === 'todas', fn ($q) => $q->orderByDesc('fecha_hora'))
            ->paginate(20);

        $mes = (clone $base)->whereBetween('fecha_hora', [now()->startOfMonth(), now()->endOfMonth()]);
        $finalizadas = (clone $mes)->where('estado', 'finalizada');
        $conResultado = (clone $finalizadas)->whereNotNull('resultado_conexion')->count();
        $duraciones = (clone $finalizadas)->whereNotNull('inicio_at')->whereNotNull('fin_at')->get(['inicio_at', 'fin_at'])
            ->map(fn ($t) => $t->inicio_at->diffInMinutes($t->fin_at));

        return view('livewire.lista-teleconsultas', [
            'teleconsultas' => $lista,
            'activa' => app(TelemedicinaService::class)->activa(),
            'medicos' => Medico::where('activo', true)->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']),
            'kpis' => [
                'proximas' => (clone $base)->where('estado', 'programada')->where('fecha_hora', '>=', now())->count(),
                'sin_consentimiento' => (clone $base)->where('estado', 'programada')->where('fecha_hora', '>=', now())->whereNull('consentimiento_datos_at')->count(),
                'realizadas_mes' => (clone $finalizadas)->count(),
                'efectivas' => $conResultado ? (int) round((clone $finalizadas)->where('resultado_conexion', 'efectiva')->count() * 100 / $conResultado) : null,
                'no_asistio_mes' => (clone $mes)->where('estado', 'no_asistio')->count(),
                'duracion' => $duraciones->isNotEmpty() ? (int) round($duraciones->avg()) : null,
            ],
        ])->layout('layouts.app');
    }
}
