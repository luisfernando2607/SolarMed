<?php

namespace App\Services;

use App\Models\Encuesta;
use App\Models\ExpedienteConsulta;

/** Crea la encuesta de satisfacción de cada consulta y arma el mensaje para enviarla */
class EncuestaService
{
    public function __construct(private AjustesService $ajustes, private MarcaService $marca) {}

    public function activas(): bool
    {
        return (bool) $this->ajustes->get('encuestas.activas');
    }

    public function crearParaConsulta(ExpedienteConsulta $consulta): ?Encuesta
    {
        if (!$this->activas()) {
            return null;
        }

        return Encuesta::firstOrCreate(['consulta_id' => $consulta->id], [
            'token' => Encuesta::nuevoToken(),
            'paciente_id' => $consulta->paciente_id,
            'medico_id' => $consulta->medico_id,
            'especialidad_id' => $consulta->especialidad_id,
            'expira_at' => now()->addDays((int) $this->ajustes->get('encuestas.dias_validez')),
        ]);
    }

    /** Enlace de WhatsApp con la invitación, o null si el paciente no tiene celular válido */
    public function enlaceWhatsapp(Encuesta $e): ?string
    {
        $p = $e->paciente;
        $telefono = WhatsappService::normalizarTelefono($p?->telefono) ?? WhatsappService::normalizarTelefono($p?->telefono_secundario);
        if (!$telefono) {
            return null;
        }
        // Con identidad reservada no se escribe el nombre en el mensaje
        $saludo = $p->identidad_reservada ? 'Hola' : 'Hola ' . strtok((string) $p->nombres, ' ');
        $texto = "{$saludo}, gracias por atenderse en {$this->marca->nombre()}. ¿Nos ayuda con un minuto para calificar la atención? {$e->url()}";

        return "https://wa.me/{$telefono}?text=" . rawurlencode($texto);
    }
}
