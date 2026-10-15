<?php

namespace App\Services;

use App\Models\CierreCaja;
use App\Models\Factura;
use App\Models\NotaCredito;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cierre de caja. Reglas:
 * - Entran al período todas las facturas aún no incluidas en un cierre (cierre_id vacío).
 * - Se cuentan como cobradas solo las facturas pagadas; las anuladas antes de cerrar no suman.
 * - Una nota de crédito sobre una factura ya cerrada en un día anterior es una devolución y se resta
 *   de su forma de pago; si la factura es del mismo período, ya está anulada y no suma.
 * - Efectivo esperado en caja = fondo inicial + efectivo cobrado − devoluciones en efectivo.
 */
class CajaService
{
    public function ultimoCierre(): ?CierreCaja
    {
        return CierreCaja::where('estado', 'cerrado')->latest('hasta')->latest('id')->first();
    }

    private function facturasPendientes()
    {
        return Factura::with('paciente:id,nombres,apellidos')->whereNull('cierre_id')->orderBy('created_at')->orderBy('id');
    }

    private function notasPendientes()
    {
        // Solo las de facturas ya cerradas: las del mismo período se reflejan en la factura anulada
        return NotaCredito::with('factura:id,numero_factura,forma_pago,cierre_id')
            ->whereNull('cierre_id')
            ->whereHas('factura', fn ($q) => $q->whereNotNull('cierre_id'))
            ->orderBy('created_at');
    }

    /** Resumen del período abierto, para la pantalla y para cerrar */
    public function periodoAbierto(): array
    {
        $facturas = $this->facturasPendientes()->get();
        $notas = $this->notasPendientes()->get();

        return $this->calcular($facturas, $notas);
    }

    private function calcular(Collection $facturas, Collection $notas): array
    {
        $ceros = array_fill_keys(array_keys(CierreCaja::FORMAS_PAGO), 0.0);
        $cobrado = $ceros;
        foreach ($facturas->where('estado', 'pagada') as $f) {
            $cobrado[$f->forma_pago] = round(($cobrado[$f->forma_pago] ?? 0) + (float) $f->total, 2);
        }
        $devoluciones = $ceros;
        foreach ($notas as $n) {
            $forma = $n->factura?->forma_pago ?? 'efectivo';
            $devoluciones[$forma] = round(($devoluciones[$forma] ?? 0) + (float) $n->valor_modificacion, 2);
        }

        $ultimo = $this->ultimoCierre();
        $desde = $ultimo?->hasta
            ?? ($facturas->min('created_at') ? Carbon::parse($facturas->min('created_at')) : now()->startOfDay());

        return [
            'desde' => $desde,
            'facturas' => $facturas,
            'notas' => $notas,
            'cobrado' => $cobrado,
            'devoluciones' => $devoluciones,
            'efectivo_neto' => round($cobrado['efectivo'] - $devoluciones['efectivo'], 2),
            'pagadas' => $facturas->where('estado', 'pagada')->count(),
            'anuladas' => $facturas->where('estado', 'anulada')->count(),
        ];
    }

    public function cerrar(int $userId, float $fondoInicial, float $contadoEfectivo, ?array $denominaciones, ?string $observaciones): CierreCaja
    {
        return DB::transaction(function () use ($userId, $fondoInicial, $contadoEfectivo, $denominaciones, $observaciones) {
            // Bloquea lo que entra al cierre: dos cierres simultáneos no pueden tomar la misma factura
            $facturas = $this->facturasPendientes()->lockForUpdate()->get();
            $notas = $this->notasPendientes()->lockForUpdate()->get();

            if ($facturas->isEmpty() && $notas->isEmpty() && $fondoInicial == 0 && $contadoEfectivo == 0) {
                throw ValidationException::withMessages(['contado_efectivo' => 'No hay movimientos para cerrar.']);
            }

            $r = $this->calcular($facturas, $notas);
            $esperado = round($fondoInicial + $r['efectivo_neto'], 2);

            $cierre = CierreCaja::create([
                'fecha' => today(),
                'user_id' => $userId,
                'desde' => $r['desde'],
                'hasta' => now(),
                'fondo_inicial' => round($fondoInicial, 2),
                'cobrado' => $r['cobrado'],
                'devoluciones' => $r['devoluciones'],
                'efectivo_esperado' => $esperado,
                'contado_efectivo' => round($contadoEfectivo, 2),
                'diferencia' => round($contadoEfectivo - $esperado, 2),
                'denominaciones' => $denominaciones ?: null,
                'detalle' => [
                    'facturas' => $facturas->map(fn ($f) => [
                        'id' => $f->id, 'numero' => $f->numero_factura, 'paciente' => $f->paciente?->nombre_completo,
                        'forma_pago' => $f->forma_pago, 'total' => (float) $f->total, 'estado' => $f->estado,
                        'hora' => $f->created_at?->format('d/m/Y H:i'),
                    ])->values()->all(),
                    'notas' => $notas->map(fn ($n) => [
                        'id' => $n->id, 'numero' => $n->numero, 'factura' => $n->factura?->numero_factura,
                        'forma_pago' => $n->factura?->forma_pago, 'valor' => (float) $n->valor_modificacion,
                    ])->values()->all(),
                ],
                'facturas_count' => $r['pagadas'],
                'anuladas_count' => $r['anuladas'],
                'notas_credito_total' => round(array_sum($r['devoluciones']), 2),
                'observaciones' => $observaciones ?: null,
                'estado' => 'cerrado',
            ]);

            Factura::whereKey($facturas->pluck('id'))->update(['cierre_id' => $cierre->id]);
            NotaCredito::whereKey($notas->pluck('id'))->update(['cierre_id' => $cierre->id]);

            app(AuditoriaService::class)->registrar('crear', 'cierre_caja', $cierre->id, null,
                sprintf('Cierre de caja: cobrado $%.2f, diferencia $%.2f', $cierre->totalCobrado(), $cierre->diferencia));

            return $cierre;
        });
    }

    /** El cierre queda como constancia; sus facturas vuelven al período abierto para un nuevo cierre */
    public function reabrir(CierreCaja $cierre, int $userId, string $motivo): void
    {
        if ($cierre->estado !== 'cerrado') {
            throw ValidationException::withMessages(['motivo' => 'Este cierre ya fue reabierto.']);
        }

        DB::transaction(function () use ($cierre, $userId, $motivo) {
            $cierre->update(['estado' => 'reabierto', 'reabierto_por' => $userId, 'reabierto_at' => now(), 'motivo_reapertura' => $motivo]);
            Factura::where('cierre_id', $cierre->id)->update(['cierre_id' => null]);
            NotaCredito::where('cierre_id', $cierre->id)->update(['cierre_id' => null]);
        });

        app(AuditoriaService::class)->registrar('editar', 'cierre_caja', $cierre->id, null, "Cierre reabierto: {$motivo}");
    }
}
