<?php
namespace App\Services;

use App\Models\Factura;
use App\Models\ServicioTarifario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacturaService {

    /** Genera número de factura formato SRI: 001-001-000000001 */
    public function siguienteNumero(): string {
        $config = app(SriConfigService::class);
        $estab = $config->get('sri_establecimiento', '001');
        $ptoEmi = $config->get('sri_pto_emi', '001');

        $ultimo = Factura::where('numero_factura', 'like', "{$estab}-{$ptoEmi}-%")
            ->orderBy('id', 'desc')
            ->value('numero_factura');

        if ($ultimo) {
            $partes = explode('-', $ultimo);
            $n = ((int) end($partes)) + 1;
        } else {
            $n = 1;
        }

        return "{$estab}-{$ptoEmi}-" . str_pad($n, 9, '0', STR_PAD_LEFT);
    }

    /**
     * Crea factura con sus ítems.
     * Precios y totales se calculan aquí desde el tarifario: los valores que llegan del navegador
     * solo aportan servicio_id y cantidad.
     */
    public function crear(array $cabecera, array $items): Factura {
        $lineas = $this->calcularLineas($items);
        $subtotal = round(array_sum(array_column($lineas, 'subtotal')), 2);
        $descuento = round((float) ($cabecera['descuento'] ?? 0), 2);

        if ($descuento < 0 || $descuento > $subtotal) {
            throw ValidationException::withMessages([
                'descuento' => 'El descuento no puede ser negativo ni mayor al subtotal.',
            ]);
        }

        // Evita que dos facturas simultáneas obtengan el mismo número secuencial
        return Cache::lock('factura-numero', 10)->block(5, function () use ($cabecera, $lineas, $subtotal, $descuento) {
            return DB::transaction(function () use ($cabecera, $lineas, $subtotal, $descuento) {
                $factura = Factura::create(array_merge($cabecera, [
                    'numero_factura' => $this->siguienteNumero(),
                    'ambiente_sri'   => app(SriConfigService::class)->get('sri_ambiente', '1'),
                    'descuento'      => $descuento,
                    'subtotal'       => $subtotal,
                    'total'          => round($subtotal - $descuento, 2),
                ]));

                foreach ($lineas as $linea) {
                    $factura->items()->create($linea);
                }

                return $factura;
            });
        });
    }

    private function calcularLineas(array $items): array {
        $servicios = ServicioTarifario::whereIn('id', array_column($items, 'servicio_id'))
            ->where('activo', true)
            ->get()
            ->keyBy('id');

        $lineas = [];
        foreach ($items as $i => $item) {
            $servicio = $servicios->get((int) ($item['servicio_id'] ?? 0));
            $cantidad = (int) ($item['cantidad'] ?? 0);

            if (!$servicio || $cantidad < 1) {
                throw ValidationException::withMessages([
                    "items.$i.servicio_id" => 'Servicio no válido o inactivo.',
                ]);
            }

            $precio = (float) $servicio->precio;
            $lineas[] = [
                'servicio_id'     => $servicio->id,
                'descripcion'     => $servicio->nombre,
                'cantidad'        => $cantidad,
                'precio_unitario' => $precio,
                'subtotal'        => round($precio * $cantidad, 2),
            ];
        }

        return $lineas;
    }
}
