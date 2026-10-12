<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Models\Embarazo;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\Turno;
use App\Services\PrenatalService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Panel de pendientes del día según los permisos del usuario. */
class PanelPendientes extends Component
{
    private function hoy(): ?array
    {
        if (!Auth::user()->can('turnos.ver')) {
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

    public function render()
    {
        return view('livewire.panel-pendientes', [
            'hoy' => $this->hoy(),
            'sri' => $this->sri(),
            'facturacion' => $this->facturacionPorMedico(),
            'embarazos' => $this->embarazos(),
        ]);
    }
}
