<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Services\AuditoriaService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Exportación CSV del inventario (existencias por lote y kardex de un producto) */
class InventarioExportController extends Controller
{
    /** Evita que Excel interprete como fórmula un texto escrito por un usuario */
    private static function seguro(array $fila): array
    {
        return array_map(fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, $fila);
    }

    private function csv(string $nombre, array $cabecera, iterable $filas): StreamedResponse
    {
        return response()->streamDownload(function () use ($cabecera, $filas) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre bien las tildes
            fputcsv($salida, $cabecera, ';');
            foreach ($filas as $fila) {
                fputcsv($salida, self::seguro($fila), ';');
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function stock(): StreamedResponse
    {
        app(AuditoriaService::class)->registrar('exportar', 'inventario', null, null, 'CSV de existencias por lote');

        $filas = function () {
            foreach (Lote::with('producto')->where('cantidad', '>', 0)->orderBy('producto_id')->orderBy('fecha_vencimiento')->cursor() as $l) {
                yield [
                    $l->producto?->descripcion(), Producto::TIPOS[$l->producto?->tipo] ?? '', $l->codigo_lote,
                    $l->fecha_vencimiento?->format('Y-m-d'), $l->cantidad, $l->producto?->unidad,
                    $l->vencido() ? 'Vencido' : ($l->porVencer() ? 'Por vencer' : 'Vigente'),
                    $l->proveedor, $l->costo_unitario, $l->registro_sanitario,
                ];
            }
        };

        return $this->csv('inventario_' . now()->format('Ymd_His') . '.csv',
            ['Producto', 'Tipo', 'Lote', 'Vencimiento', 'Cantidad', 'Unidad', 'Estado', 'Proveedor', 'Costo unitario', 'Registro sanitario'], $filas());
    }

    public function kardex(Producto $producto): StreamedResponse
    {
        app(AuditoriaService::class)->registrar('exportar', 'inventario', $producto->id, null, "CSV del kardex de {$producto->descripcion()}");

        $filas = function () use ($producto) {
            foreach (MovimientoInventario::with('lote', 'user', 'paciente')->where('producto_id', $producto->id)->orderBy('id')->cursor() as $m) {
                yield [
                    $m->created_at->format('Y-m-d H:i:s'), MovimientoInventario::TIPOS[$m->tipo], $m->motivoTexto(),
                    $m->signo() * $m->cantidad, $m->lote?->codigo_lote, $m->saldo_lote_despues,
                    $m->user?->name, $m->paciente?->cedula, $m->observacion,
                ];
            }
        };

        return $this->csv('kardex_' . $producto->id . '_' . now()->format('Ymd') . '.csv',
            ['Fecha', 'Movimiento', 'Motivo', 'Cantidad', 'Lote', 'Saldo del lote', 'Usuario', 'Paciente (HC)', 'Observación'], $filas());
    }
}
