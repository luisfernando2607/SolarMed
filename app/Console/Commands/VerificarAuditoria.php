<?php

namespace App\Console\Commands;

use App\Services\AuditoriaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerificarAuditoria extends Command
{
    protected $signature = 'auditoria:verificar';

    protected $description = 'Comprueba que el registro de auditoría no haya sido alterado (cadena de hashes SHA-256)';

    public function handle(AuditoriaService $auditoria): int
    {
        $r = $auditoria->verificarIntegridad();

        if ($r['integro']) {
            $this->info("Registro de auditoría íntegro: {$r['total']} registros verificados.");
            return self::SUCCESS;
        }

        Log::critical('Registro de auditoría alterado', $r);
        $this->error("El registro de auditoría fue alterado a partir del registro #{$r['primer_error']}.");
        return self::FAILURE;
    }
}
