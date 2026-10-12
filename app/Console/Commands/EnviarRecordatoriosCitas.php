<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCitaMail;
use App\Models\Cita;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosCitas extends Command
{
    protected $signature = 'citas:recordatorios';

    protected $description = 'Envía por correo el recordatorio de las citas del día siguiente';

    public function handle(): int
    {
        $enviados = 0;

        Cita::with('paciente')
            ->whereDate('fecha_hora', today()->addDay())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereNull('recordatorio_enviado_at')
            ->each(function (Cita $cita) use (&$enviados) {
                $correo = $cita->paciente?->email;
                if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    return;
                }
                Mail::to($correo)->queue(new RecordatorioCitaMail($cita));
                $cita->update(['recordatorio_enviado_at' => now()]);
                $enviados++;
            });

        $this->info("Recordatorios enviados: {$enviados}");

        return self::SUCCESS;
    }
}
