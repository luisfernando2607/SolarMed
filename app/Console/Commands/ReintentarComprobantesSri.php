<?php

namespace App\Console\Commands;

use App\Jobs\AutorizarComprobanteSri;
use App\Models\Factura;
use App\Models\NotaCredito;
use Illuminate\Console\Command;

class ReintentarComprobantesSri extends Command
{
    protected $signature = 'sri:reintentar {--minutos=10 : Solo comprobantes sin cambios desde hace estos minutos}';

    protected $description = 'Retoma el envío y la autorización de comprobantes que quedaron pendientes con el SRI';

    public function handle(): int
    {
        $limite = now()->subMinutes((int) $this->option('minutos'));
        $total = 0;

        foreach ([Factura::class, NotaCredito::class] as $modelo) {
            // No incluye 'firmado': esos aún no se intentaron enviar y requieren que alguien presione "Emitir al SRI"
            $modelo::whereIn('estado_sri', ['error_envio', 'enviado', 'error_autorizacion'])
                ->where('updated_at', '<=', $limite)
                ->each(function ($comprobante) use (&$total) {
                    AutorizarComprobanteSri::dispatch($comprobante);
                    $total++;
                });
        }

        $this->info("Comprobantes programados: {$total}");

        return self::SUCCESS;
    }
}
