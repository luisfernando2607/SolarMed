<?php
namespace App\Services;

use App\Models\Factura;
use App\Models\FacturaItem;
use Illuminate\Support\Facades\DB;

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

    /** Crea factura con sus ítems */
    public function crear(array $cabecera, array $items): Factura {
        return DB::transaction(function () use ($cabecera, $items) {
            $subtotal = collect($items)->sum('subtotal');
            $descuento = $cabecera['descuento'] ?? 0;

            $factura = Factura::create(array_merge($cabecera, [
                'numero_factura' => $this->siguienteNumero(),
                'subtotal'       => $subtotal,
                'total'          => $subtotal - $descuento,
            ]));

            foreach ($items as $item) {
                $factura->items()->create($item);
            }

            return $factura;
        });
    }
}
