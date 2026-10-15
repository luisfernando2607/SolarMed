<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCitaMail;
use App\Models\Cita;
use App\Services\WhatsappCloudService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosCitas extends Command
{
    protected $signature = 'citas:recordatorios';

    protected $description = 'Envía el recordatorio de las citas del día siguiente por correo y, si está activo, por WhatsApp';

    public function handle(WhatsappCloudService $whatsapp): int
    {
        $correos = 0;
        $mensajes = 0;

        // Cada canal lleva su propia marca, para no repetir el envío si el comando corre de nuevo
        Cita::with('paciente', 'medico')
            ->whereDate('fecha_hora', today()->addDay())
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereNull('recordatorio_enviado_at')
            ->each(function (Cita $cita) use (&$correos) {
                $correo = $cita->paciente?->email;
                if (!$correo || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                    return;
                }
                Mail::to($correo)->queue(new RecordatorioCitaMail($cita));
                $cita->update(['recordatorio_enviado_at' => now()]);
                $correos++;
            });

        if ($whatsapp->usoActivo('recordatorio')) {
            Cita::with('paciente', 'medico')
                ->whereDate('fecha_hora', today()->addDay())
                ->whereIn('estado', ['pendiente', 'confirmada'])
                ->whereNull('whatsapp_recordatorio_at')
                ->each(function (Cita $cita) use ($whatsapp, &$mensajes) {
                    [$mensaje] = $whatsapp->recordatorioCita($cita);
                    if ($mensaje) {
                        $cita->update(['whatsapp_recordatorio_at' => now()]);
                        $mensajes++;
                    }
                });
        }

        $this->info("Recordatorios enviados: {$correos} por correo, {$mensajes} por WhatsApp");

        return self::SUCCESS;
    }
}
