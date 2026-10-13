<?php

namespace App\Services;

use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Movimientos de inventario. Toda operación corre en una transacción y bloquea los lotes que toca,
 * así el stock nunca queda negativo aunque dos personas registren a la vez.
 * Las salidas usan FEFO: primero el lote que vence antes, y nunca un lote vencido.
 */
class InventarioService
{
    public function __construct(private AuditoriaService $auditoria) {}

    /** Ingreso de un lote nuevo (o suma a un lote existente con el mismo código y vencimiento) */
    public function entrada(Producto $producto, array $datos, string $motivo = 'compra', ?string $observacion = null): Lote
    {
        $cantidad = (int) ($datos['cantidad'] ?? 0);
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor que cero.']);
        }
        $this->validarMotivo('entrada', $motivo);

        $lote = DB::transaction(function () use ($producto, $datos, $cantidad, $motivo, $observacion) {
            $vence = $datos['fecha_vencimiento'] ?: null;
            $lote = Lote::where('producto_id', $producto->id)
                ->where('codigo_lote', $datos['codigo_lote'])
                ->when($vence, fn ($q) => $q->whereDate('fecha_vencimiento', $vence), fn ($q) => $q->whereNull('fecha_vencimiento'))
                ->lockForUpdate()->first();

            if ($lote) {
                $lote->cantidad += $cantidad;
                $lote->save();
            } else {
                $lote = Lote::create([
                    'producto_id' => $producto->id,
                    'codigo_lote' => $datos['codigo_lote'],
                    'fecha_vencimiento' => $vence,
                    'cantidad' => $cantidad,
                    'proveedor' => $datos['proveedor'] ?? null,
                    'costo_unitario' => ($datos['costo_unitario'] ?? '') !== '' ? $datos['costo_unitario'] : null,
                    'registro_sanitario' => $datos['registro_sanitario'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            }

            $this->movimiento($producto, $lote, 'entrada', $motivo, $cantidad, $observacion);

            return $lote;
        });

        $this->auditoria->registrar('entrada', 'inventario', $producto->id, null, "{$producto->descripcion()}: +{$cantidad} (lote {$lote->codigo_lote})");

        return $lote;
    }

    /**
     * Salida por FEFO. Devuelve los movimientos creados (uno por lote tocado).
     *
     * @return Collection<int, MovimientoInventario>
     */
    public function salida(Producto $producto, int $cantidad, string $motivo, ?int $pacienteId = null, ?int $consultaId = null, ?string $observacion = null): Collection
    {
        if ($cantidad < 1) {
            throw ValidationException::withMessages(['cantidad' => 'La cantidad debe ser mayor que cero.']);
        }
        $this->validarMotivo('salida', $motivo);

        $movimientos = DB::transaction(function () use ($producto, $cantidad, $motivo, $pacienteId, $consultaId, $observacion) {
            $lotes = Lote::where('producto_id', $producto->id)->disponibles()
                ->orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC, id ASC')
                ->lockForUpdate()->get();

            $disponible = (int) $lotes->sum('cantidad');
            if ($disponible < $cantidad) {
                throw ValidationException::withMessages([
                    'cantidad' => "Solo hay {$disponible} {$producto->unidad} disponibles (no se cuentan lotes vencidos).",
                ]);
            }

            $pendiente = $cantidad;
            $creados = collect();
            foreach ($lotes as $lote) {
                if ($pendiente === 0) {
                    break;
                }
                $toma = min($pendiente, $lote->cantidad);
                $lote->cantidad -= $toma;
                $lote->save();
                $pendiente -= $toma;
                $creados->push($this->movimiento($producto, $lote, 'salida', $motivo, $toma, $observacion, $pacienteId, $consultaId));
            }

            return $creados;
        });

        $this->auditoria->registrar('salida', 'inventario', $producto->id, $pacienteId, "{$producto->descripcion()}: −{$cantidad} (" . (MovimientoInventario::MOTIVOS['salida'][$motivo] ?? $motivo) . ')');

        return $movimientos;
    }

    /** Ajuste por conteo físico: registra la diferencia entre lo contado y lo que dice el sistema */
    public function ajuste(Lote $lote, int $conteo, ?string $observacion = null): MovimientoInventario
    {
        if ($conteo < 0) {
            throw ValidationException::withMessages(['conteo' => 'El conteo no puede ser negativo.']);
        }
        if (!filled($observacion)) {
            throw ValidationException::withMessages(['observacion' => 'Explique el motivo del ajuste.']);
        }

        $mov = DB::transaction(function () use ($lote, $conteo, $observacion) {
            $lote = Lote::whereKey($lote->id)->lockForUpdate()->firstOrFail();
            $diferencia = $conteo - $lote->cantidad;
            if ($diferencia === 0) {
                throw ValidationException::withMessages(['conteo' => 'El conteo coincide con el sistema: no hay nada que ajustar.']);
            }
            $lote->cantidad = $conteo;
            $lote->save();

            return $this->movimiento($lote->producto, $lote, 'ajuste', $diferencia > 0 ? 'conteo_mas' : 'conteo_menos', abs($diferencia), $observacion);
        });

        $this->auditoria->registrar('ajuste', 'inventario', $lote->producto_id, null, "Lote {$lote->codigo_lote}: conteo {$conteo} ({$mov->motivoTexto()} de {$mov->cantidad})");

        return $mov;
    }

    /** Baja de un lote (o parte de él) por vencimiento, daño o pérdida */
    public function baja(Lote $lote, string $motivo, ?int $cantidad = null, ?string $observacion = null): MovimientoInventario
    {
        $this->validarMotivo('baja', $motivo);

        $mov = DB::transaction(function () use ($lote, $motivo, $cantidad, $observacion) {
            $lote = Lote::whereKey($lote->id)->lockForUpdate()->firstOrFail();
            $cantidad ??= $lote->cantidad;
            if ($cantidad < 1) {
                throw ValidationException::withMessages(['cantidad' => 'El lote no tiene existencias para dar de baja.']);
            }
            if ($cantidad > $lote->cantidad) {
                throw ValidationException::withMessages(['cantidad' => "El lote solo tiene {$lote->cantidad} {$lote->producto->unidad}."]);
            }
            $lote->cantidad -= $cantidad;
            $lote->save();

            return $this->movimiento($lote->producto, $lote, 'baja', $motivo, $cantidad, $observacion);
        });

        $this->auditoria->registrar('baja', 'inventario', $lote->producto_id, null, "Lote {$lote->codigo_lote}: −{$mov->cantidad} ({$mov->motivoTexto()})");

        return $mov;
    }

    /** Existencias entregables (sin lotes vencidos) por producto */
    public function stockDisponible(Producto $producto): int
    {
        return (int) Lote::where('producto_id', $producto->id)->disponibles()->sum('cantidad');
    }

    /**
     * Totales para las alertas del panel y del Estado del sistema.
     *
     * @return array{stock_bajo: int, por_vencer: int, vencidos: int}
     */
    public function alertas(): array
    {
        $hoy = today();

        $stockBajo = Producto::where('activo', true)->where('stock_minimo', '>', 0)
            ->withSum(['lotes as disponible' => fn ($q) => $q->disponibles()], 'cantidad')
            ->get()
            ->filter(fn ($p) => (int) $p->disponible <= $p->stock_minimo)
            ->count();

        return [
            'stock_bajo' => $stockBajo,
            'por_vencer' => Lote::where('cantidad', '>', 0)->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '>=', $hoy)
                ->whereDate('fecha_vencimiento', '<=', $hoy->copy()->addDays(Producto::DIAS_POR_VENCER))->count(),
            'vencidos' => Lote::where('cantidad', '>', 0)->whereNotNull('fecha_vencimiento')->whereDate('fecha_vencimiento', '<', $hoy)->count(),
        ];
    }

    private function movimiento(Producto $producto, Lote $lote, string $tipo, string $motivo, int $cantidad, ?string $observacion,
        ?int $pacienteId = null, ?int $consultaId = null): MovimientoInventario
    {
        return MovimientoInventario::create([
            'producto_id' => $producto->id,
            'lote_id' => $lote->id,
            'tipo' => $tipo,
            'motivo' => $motivo,
            'cantidad' => $cantidad,
            'saldo_lote_despues' => $lote->cantidad,
            'user_id' => Auth::id(),
            'paciente_id' => $pacienteId,
            'consulta_id' => $consultaId,
            'observacion' => $observacion ? mb_substr($observacion, 0, 500) : null,
        ]);
    }

    private function validarMotivo(string $tipo, string $motivo): void
    {
        if (!array_key_exists($motivo, MovimientoInventario::MOTIVOS[$tipo])) {
            throw ValidationException::withMessages(['motivo' => 'Seleccione un motivo válido.']);
        }
    }
}
