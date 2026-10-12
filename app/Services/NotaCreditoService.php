<?php
namespace App\Services;

use App\Models\Factura;
use App\Models\NotaCredito;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nota de crédito electrónica que anula totalmente una factura autorizada por el SRI.
 * Se emite con el mismo RUC (médico) de la factura y tiene su propia numeración por RUC.
 * El envío y la autorización los realiza ComprobanteSriService.
 */
class NotaCreditoService {

    public function crear(Factura $factura, string $motivo, int $userId): NotaCredito
    {
        if ($factura->estado_sri !== 'autorizado' || $factura->estado !== 'pagada') {
            throw ValidationException::withMessages([
                'motivo_nota_credito' => 'Solo se emite nota de crédito sobre facturas pagadas y autorizadas por el SRI.',
            ]);
        }
        if ($factura->notaCredito()->exists()) {
            throw ValidationException::withMessages([
                'motivo_nota_credito' => 'Esta factura ya tiene una nota de crédito.',
            ]);
        }

        $emisor = app(SriConfigService::class)->emisor($factura->medico);
        $ruc = $factura->ruc_emisor ?: $emisor['ruc'];
        [$estab, $ptoEmi] = explode('-', $factura->numero_factura);

        return Cache::lock('nota-credito-numero-' . ($ruc ?: 'sin-ruc'), 10)->block(5, function () use ($factura, $motivo, $userId, $ruc, $estab, $ptoEmi) {
            return DB::transaction(fn () => NotaCredito::create([
                'factura_id' => $factura->id,
                'medico_id' => $factura->medico_id,
                'user_id' => $userId,
                'numero' => $this->siguienteNumero($ruc, $estab, $ptoEmi),
                'ruc_emisor' => $ruc,
                'fecha' => now(),
                'motivo' => $motivo,
                'subtotal' => $factura->total, // total sin impuestos (servicios de salud: IVA 0 %)
                'valor_modificacion' => $factura->total,
                'ambiente_sri' => $factura->ambiente_sri ?: '1',
                'estado_sri' => 'pendiente',
            ]));
        });
    }

    public function siguienteNumero(?string $ruc, string $estab, string $ptoEmi): string
    {
        $ultimo = NotaCredito::where('ruc_emisor', $ruc)
            ->where('numero', 'like', "{$estab}-{$ptoEmi}-%")
            ->orderBy('id', 'desc')
            ->value('numero');

        $n = $ultimo ? ((int) substr($ultimo, strrpos($ultimo, '-') + 1)) + 1 : 1;

        return "{$estab}-{$ptoEmi}-" . str_pad($n, 9, '0', STR_PAD_LEFT);
    }
}
