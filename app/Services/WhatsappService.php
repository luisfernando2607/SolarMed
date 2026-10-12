<?php

namespace App\Services;

use App\Models\Cita;

/**
 * Recordatorios por WhatsApp sin API: arma un enlace wa.me con el mensaje ya escrito,
 * que el personal abre y envía desde su propio WhatsApp.
 */
class WhatsappService
{
    public const PLACEHOLDERS = ['{paciente}', '{fecha}', '{hora}', '{medico}', '{clinica}'];

    public function __construct(private AjustesService $ajustes, private MarcaService $marca) {}

    /**
     * Celular ecuatoriano en formato internacional sin "+" (5939XXXXXXXX), o null si no es un celular válido.
     * Acepta 09XXXXXXXX, 9XXXXXXXX, 5939XXXXXXXX y +593 09XXXXXXXX. Los fijos no reciben WhatsApp.
     */
    public static function normalizarTelefono(?string $telefono): ?string
    {
        $digitos = preg_replace('/\D/', '', (string) $telefono);

        if (str_starts_with($digitos, '593')) {
            $digitos = ltrim(substr($digitos, 3), '0');
        } else {
            $digitos = ltrim($digitos, '0');
        }

        return preg_match('/^9\d{8}$/', $digitos) ? '593' . $digitos : null;
    }

    public function mensaje(Cita $cita): string
    {
        $fecha = $cita->fecha_hora->copy()->locale('es');
        $paciente = $cita->paciente;
        // Identidad reservada (AM 5216-A Art. 13): el mensaje sale de la clínica, no lleva el nombre
        $nombre = $paciente?->identidad_reservada ? "paciente con cédula {$paciente->cedula_enmascarada}" : $paciente?->nombre_completo;

        return strtr((string) $this->ajustes->get('recordatorios.whatsapp_plantilla'), [
            '{paciente}' => (string) $nombre,
            '{fecha}' => $fecha->translatedFormat('l j \d\e F'),
            '{hora}' => $fecha->format('H:i'),
            '{medico}' => (string) $cita->medico?->nombre_completo,
            '{clinica}' => $this->marca->nombre(),
        ]);
    }

    /** Enlace para abrir WhatsApp con el recordatorio, o null si el paciente no tiene celular válido */
    public function enlaceRecordatorio(Cita $cita): ?string
    {
        $telefono = self::normalizarTelefono($cita->paciente?->telefono)
            ?? self::normalizarTelefono($cita->paciente?->telefono_secundario);

        return $telefono ? "https://wa.me/{$telefono}?text=" . rawurlencode($this->mensaje($cita)) : null;
    }
}
