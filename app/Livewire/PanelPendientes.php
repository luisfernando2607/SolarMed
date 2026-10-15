<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Models\Embarazo;
use App\Models\Factura;
use App\Models\IncidenteSeguridad;
use App\Models\SolicitudInformacion;
use App\Models\Medico;
use App\Models\Turno;
use App\Services\PrenatalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Panel de pendientes del día según los permisos del usuario. */
class PanelPendientes extends Component
{
    /** El dashboard personal ya muestra sus propios indicadores del día */
    public bool $mostrarHoy = true;

    private function hoy(): ?array
    {
        if (!$this->mostrarHoy || !Auth::user()->can('turnos.ver')) {
            return null;
        }

        $citas = Cita::whereDate('fecha_hora', today())->whereIn('estado', ['pendiente', 'confirmada']);

        return [
            'citas' => (clone $citas)->count(),
            'sin_confirmar' => (clone $citas)->where('estado', 'pendiente')->count(),
            'esperando' => Turno::whereDate('fecha', today())->where('estado', 'esperando')->count(),
            'en_atencion' => Turno::whereDate('fecha', today())->where('estado', 'en_atencion')->count(),
        ];
    }

    /** Reservas hechas por pacientes en la página pública que esperan confirmación */
    private function enLinea(): ?array
    {
        if (!Auth::user()->can('turnos.gestionar')) {
            return null;
        }
        $citas = Cita::enLineaPorConfirmar();
        $total = (clone $citas)->count();

        return $total ? ['total' => $total, 'proxima' => (clone $citas)->orderBy('fecha_hora')->value('fecha_hora')] : null;
    }

    private function sri(): ?array
    {
        if (!Auth::user()->can('facturas.ver')) {
            return null;
        }

        $vigentes = Factura::where('estado', '!=', 'anulada');

        return [
            'sin_emitir' => (clone $vigentes)->where(fn ($q) => $q->whereNull('estado_sri')->orWhereIn('estado_sri', ['pendiente', 'xml_generado', 'firmado']))->count(),
            'con_problema' => (clone $vigentes)->whereIn('estado_sri', ['rechazado', 'no_autorizado'])->latest('fecha')->limit(5)->get(['id', 'numero_factura', 'estado_sri']),
            'en_proceso' => (clone $vigentes)->whereIn('estado_sri', ['enviado', 'error_envio', 'error_autorizacion'])->count(),
        ];
    }

    private function facturacionPorMedico(): ?Collection
    {
        if (!Auth::user()->can('reportes.ver')) {
            return null;
        }

        return Factura::query()
            ->whereBetween('fecha', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('estado', '!=', 'anulada')
            ->selectRaw('medico_id, count(*) as cantidad, sum(total) as monto')
            ->groupBy('medico_id')
            ->with('medico:id,nombres,apellidos')
            ->orderByDesc('monto')
            ->get();
    }

    private function embarazos(): ?array
    {
        if (!Auth::user()->can('expediente.ver')) {
            return null;
        }

        // Un médico ve sus pacientes; el admin ve todos
        $medicoId = Auth::user()->hasRole('admin') ? null : Medico::where('user_id', Auth::id())->value('id');
        $prenatal = app(PrenatalService::class);

        $activos = Embarazo::where('estado', 'activo')
            ->when($medicoId, fn ($q) => $q->where('medico_id', $medicoId))
            ->with(['paciente:id,nombres,apellidos', 'controles' => fn ($q) => $q->latest('fecha')->latest('id')])
            ->get();

        $atencion = $activos->map(function (Embarazo $e) use ($prenatal) {
            $ultimo = $e->controles->first();
            $motivos = [];
            if ($ultimo) {
                foreach ($prenatal->alertas($ultimo) as $alerta) {
                    $motivos[] = ['nivel' => $alerta['nivel'], 'texto' => $alerta['mensaje']];
                }
            }
            $diasSinControl = (int) ($ultimo?->fecha ?? $e->fum)->diffInDays(today());
            if ($diasSinControl > (int) app(\App\Services\AjustesService::class)->get('prenatal.dias_sin_control')) {
                $motivos[] = ['nivel' => 'media', 'texto' => "{$diasSinControl} días sin control"];
            }
            $diasFpp = (int) today()->diffInDays($e->fpp, false);
            if ($diasFpp <= (int) app(\App\Services\AjustesService::class)->get('prenatal.aviso_fpp_dias')) {
                $motivos[] = ['nivel' => $diasFpp < 0 ? 'alta' : 'media', 'texto' => $diasFpp < 0 ? 'FPP superada hace ' . abs($diasFpp) . ' días' : "FPP en {$diasFpp} días"];
            }

            return ['embarazo' => $e, 'eg' => $e->edadGestacional(), 'motivos' => $motivos];
        })->filter(fn ($x) => $x['motivos'])
          ->sortByDesc(fn ($x) => collect($x['motivos'])->contains('nivel', 'alta'))
          ->values();

        return ['activos' => $activos->count(), 'atencion' => $atencion];
    }

    /** Plazos legales: copias de HC (48 h), derechos LOPDP (15 días) y notificación de incidentes (5 días) */
    private function cumplimiento(): ?array
    {
        if (!Auth::user()->can('auditoria.ver')) {
            return null;
        }

        $pendientes = SolicitudInformacion::pendientes();
        $datos = [
            'solicitudes' => (clone $pendientes)->count(),
            'vencidas' => (clone $pendientes)->where('fecha_limite', '<', now())->count(),
            'incidentes' => IncidenteSeguridad::where('requiere_notificacion', true)->whereNull('notificado_autoridad_at')->count(),
        ];

        return array_sum($datos) ? $datos : null;
    }

    /** Existencias por debajo del mínimo, lotes por vencer y vencidos con stock */
    private function inventario(): ?array
    {
        if (!Auth::user()->can('inventario.ver')) {
            return null;
        }
        $a = app(\App\Services\InventarioService::class)->alertas();

        return array_sum($a) ? $a : null;
    }

    /** Facturas de días anteriores que aún no entran en un cierre de caja */
    private function caja(): ?array
    {
        if (!Auth::user()->can('caja.cerrar')) {
            return null;
        }
        $pendientes = \App\Models\Factura::whereNull('cierre_id')->where('created_at', '<', today());
        $desde = (clone $pendientes)->min('created_at');

        return $desde ? ['desde' => \Carbon\Carbon::parse($desde), 'facturas' => $pendientes->count()] : null;
    }

    public function render()
    {
        return view('livewire.panel-pendientes', [
            'caja' => $this->caja(),
            'inventario' => $this->inventario(),
            'cumplimiento' => $this->cumplimiento(),
            'enLinea' => $this->enLinea(),
            'hoy' => $this->hoy(),
            'sri' => $this->sri(),
            'facturacion' => $this->facturacionPorMedico(),
            'embarazos' => $this->embarazos(),
        ]);
    }
}
