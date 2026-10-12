<?php
namespace App\Services;

use App\Models\Factura;
use App\Models\Medico;
use App\Models\ServicioTarifario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacturaService {

    /**
     * Genera número de factura formato SRI (001-001-000000001).
     * La secuencia es independiente para cada RUC emisor y punto de emisión.
     */
    public function siguienteNumero(array $emisor): string {
        $estab = $emisor['establecimiento'];
        $ptoEmi = $emisor['pto_emi'];

        $ultimo = Factura::where('ruc_emisor', $emisor['ruc'])
            ->where('numero_factura', 'like', "{$estab}-{$ptoEmi}-%")
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

        $config = app(SriConfigService::class);
        $emisor = $config->emisor(Medico::find($cabecera['medico_id'] ?? null));

        // Evita que dos facturas simultáneas del mismo emisor obtengan el mismo número secuencial
        return Cache::lock('factura-numero-' . ($emisor['ruc'] ?: 'sin-ruc'), 10)->block(5, function () use ($cabecera, $lineas, $subtotal, $descuento, $emisor) {
            return DB::transaction(function () use ($cabecera, $lineas, $subtotal, $descuento, $emisor) {
                $factura = Factura::create(array_merge($cabecera, [
                    'numero_factura' => $this->siguienteNumero($emisor),
                    'ruc_emisor'     => $emisor['ruc'],
                    'ambiente_sri'   => app(SriConfigService::class)->get('sri_ambiente', '1'),
                    'descuento'      => $descuento,
                    'subtotal'       => $subtotal,
                    'total'          => round($subtotal - $descuento, 2),
                ]));

                foreach ($lineas as $linea) {
                    $factura->items()->create($linea);
                }

                // Recarga para tener los valores por defecto de la base (estado, estado_sri)
                return $factura->refresh();
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
