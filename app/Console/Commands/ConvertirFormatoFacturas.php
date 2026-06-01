<?php
namespace App\Console\Commands;

use App\Models\Factura;
use App\Services\SriConfigService;
use Illuminate\Console\Command;

class ConvertirFormatoFacturas extends Command
{
    protected $signature = 'facturas:convertir-formato';
    protected $description = 'Convierte numero_factura de NV-XXXXXX a 001-001-XXXXXXXXX';

    public function handle(SriConfigService $config): int
    {
        $estab = $config->get('sri_establecimiento', '001');
        $ptoEmi = $config->get('sri_pto_emi', '001');
        $prefijo = "{$estab}-{$ptoEmi}-";

        $facturas = Factura::where('numero_factura', 'not like', "{$prefijo}%")->orderBy('id')->get();

        if ($facturas->isEmpty()) {
            $this->info('No hay facturas con formato antiguo.');
            return 0;
        }

        $bar = $this->output->createProgressBar($facturas->count());
        $bar->start();

        $secuencial = 1;
        foreach ($facturas as $factura) {
            // Intentar extraer el número secuencial antiguo
            $parts = explode('-', $factura->numero_factura);
            $oldNum = (int) end($parts);

            // Usar el número antiguo como secuencial si es mayor al actual
            if ($oldNum >= $secuencial) {
                $secuencial = $oldNum;
            }

            $nuevo = $prefijo . str_pad($secuencial, 9, '0', STR_PAD_LEFT);

            Factura::withoutTimestamps(fn () => $factura->update(['numero_factura' => $nuevo]));

            $secuencial++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$facturas->count()} facturas convertidas al formato {$prefijo}XXXXXXXXX.");

        return 0;
    }
}
