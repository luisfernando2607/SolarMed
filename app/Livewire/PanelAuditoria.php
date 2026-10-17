<?php

namespace App\Livewire;

use App\Models\AuditoriaHc;
use App\Models\ExpedienteConsulta;
use App\Models\IncidenteSeguridad;
use App\Models\PlanAccion;
use App\Models\RegistroAcceso;
use App\Models\SolicitudInformacion;
use App\Services\AuditoriaService;
use App\Services\CalidadHistoriaService;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/**
 * Panel de auditoría: reúne en una sola pantalla los indicadores que un auditor del MSP
 * revisa (calidad de la HC form.002, planes de acción, incidentes, solicitudes de información
 * y la integridad de la bitácora de accesos) con enlaces a cada módulo.
 */
class PanelAuditoria extends Component
{
    public string $desde = '';
    public string $hasta = '';

    /** Máximo de consultas evaluadas para el promedio de calidad */
    private const LIMITE = 2000;

    public function mount(): void
    {
        $this->authorize('auditoria.ver');
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = today()->toDateString();
    }

    public function verificarIntegridad(): void
    {
        $this->authorize('auditoria.gestionar');
        $r = app(AuditoriaService::class)->verificarIntegridad();
        $this->dispatch('notify',
            message: $r['ok'] ? 'La bitácora de accesos está íntegra.' : 'Se detectó una alteración en la bitácora.',
            type: $r['ok'] ? 'success' : 'error');
    }

    private function rango(): array
    {
        $desde = strtotime($this->desde) ? $this->desde : now()->startOfMonth()->toDateString();
        $hasta = strtotime($this->hasta) ? $this->hasta : today()->toDateString();
        return [$desde, $hasta];
    }

    public function render()
    {
        [$desde, $hasta] = $this->rango();

        // Calidad de la HC (form.002) en el período
        $consultas = ExpedienteConsulta::query()
            ->with('paciente', 'medico.user')
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->latest('fecha')->take(self::LIMITE)->get();

        $evaluaciones = app(CalidadHistoriaService::class)->evaluarVarias($consultas);
        $puntajes = $evaluaciones->pluck('puntaje');
        $promedioCalidad = $puntajes->count() ? (int) round($puntajes->avg()) : null;
        $buenas = $puntajes->filter(fn ($p) => $p >= CalidadHistoriaService::UMBRAL_BUENO)->count();

        $hace7 = now()->subDays(7);

        return view('livewire.panel-auditoria', [
            'desde' => $desde,
            'hasta' => $hasta,
            'calidad' => [
                'promedio' => $promedioCalidad,
                'evaluadas' => $puntajes->count(),
                'buenas' => $buenas,
                'buenasPct' => $puntajes->count() ? (int) round($buenas / $puntajes->count() * 100) : 0,
                'auditadas' => AuditoriaHc::where('estado', 'cerrada')
                    ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)->count(),
            ],
            'planes' => [
                'abiertos' => PlanAccion::where('estado', '!=', 'cumplido')->count(),
                'vencidos' => PlanAccion::where('estado', '!=', 'cumplido')->whereDate('fecha_limite', '<', today())->count(),
            ],
            'incidentes' => [
                'abiertos' => IncidenteSeguridad::where('estado', '!=', 'cerrado')->count(),
                'por_notificar' => IncidenteSeguridad::where('estado', 'abierto')->where('requiere_notificacion', true)
                    ->whereNull('notificado_autoridad_at')->count(),
            ],
            'solicitudes' => [
                'pendientes' => SolicitudInformacion::pendientes()->count(),
                'vencidas' => SolicitudInformacion::pendientes()->get()->filter->vencida()->count(),
            ],
            'accesos' => [
                'hoy' => RegistroAcceso::whereDate('created_at', today())
                    ->whereNotIn('accion', RegistroAcceso::ACCIONES_SESION)->count(),
                'descargas_7' => RegistroAcceso::where('created_at', '>=', $hace7)
                    ->whereIn('accion', ['descargar', 'exportar'])->count(),
                'fallidos_7' => RegistroAcceso::where('created_at', '>=', $hace7)
                    ->whereIn('accion', ['login_fallido', 'bloqueo'])->count(),
            ],
            'integridad' => Cache::get(AuditoriaService::CLAVE_INTEGRIDAD),
        ])->layout('layouts.app');
    }
}
