<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Teleconsulta;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Teleconsultas sincrónicas (Norma Técnica de Telesalud del MSP, 2025).
 * La sala de video es externa (Jitsi Meet o un enlace propio de la clínica): el navegador solo permite cámara
 * y micrófono en HTTPS, y esta app puede estar en la red local.
 */
class TelemedicinaService
{
    public const PLATAFORMAS = ['jitsi' => 'Jitsi Meet (sala automática)', 'manual' => 'Enlace propio (Zoom, Meet, Teams…)'];

    public function __construct(private AjustesService $ajustes, private MarcaService $marca) {}

    public function activa(): bool
    {
        return (bool) $this->ajustes->get('telemedicina.activa');
    }

    /**
     * Nombre de sala imposible de adivinar y sin datos del paciente ni del médico
     * (num. 7.2.1: los datos de salud son sensibles y no se divulgan).
     */
    public static function nombreSala(): string
    {
        return 'consulta-' . Str::lower(Str::random(32));
    }

    public function urlSala(): string
    {
        $base = rtrim((string) $this->ajustes->get('telemedicina.jitsi_url'), '/');

        return $base . '/' . self::nombreSala();
    }

    /** Crea la teleconsulta de una cita agendada en modalidad telemedicina (agendamiento, num. 7.1.3) */
    public function crearParaCita(Cita $cita, ?int $userId = null): Teleconsulta
    {
        $plataforma = (string) $this->ajustes->get('telemedicina.plataforma');

        $tc = Teleconsulta::firstOrCreate(['cita_id' => $cita->id], [
            'paciente_id' => $cita->paciente_id,
            'medico_id' => $cita->medico_id,
            'fecha_hora' => $cita->fecha_hora,
            'duracion_min' => $cita->duracion_min,
            'plataforma' => $plataforma,
            'sala_url' => $plataforma === 'jitsi' ? $this->urlSala() : null,
            'token' => Str::random(48),
            'estado' => 'programada',
            'created_by' => $userId,
        ]);
        app(AuditoriaService::class)->registrar('crear', 'teleconsulta', $tc->id, $tc->paciente_id, 'Teleconsulta del ' . $tc->fecha_hora->format('d/m/Y H:i'));

        return $tc;
    }

    /** El paciente puede entrar desde X minutos antes hasta Y minutos después de la hora agendada */
    public function ventana(Teleconsulta $tc): array
    {
        return [
            $tc->fecha_hora->copy()->subMinutes((int) $this->ajustes->get('telemedicina.minutos_antes')),
            $tc->fecha_hora->copy()->addMinutes((int) $this->ajustes->get('telemedicina.minutos_despues')),
        ];
    }

    public function dentroDeVentana(Teleconsulta $tc, ?Carbon $ahora = null): bool
    {
        [$desde, $hasta] = $this->ventana($tc);

        return ($ahora ?? now())->between($desde, $hasta);
    }

    /** Enlace de WhatsApp (wa.me) con la invitación, o null si el paciente no tiene celular válido */
    public function enlaceWhatsapp(Teleconsulta $tc): ?string
    {
        $p = $tc->paciente;
        $telefono = WhatsappService::normalizarTelefono($p?->telefono) ?? WhatsappService::normalizarTelefono($p?->telefono_secundario);
        if (!$telefono) {
            return null;
        }
        $saludo = $p->identidad_reservada ? 'Hola' : 'Hola ' . strtok((string) $p->nombres, ' ');
        $fecha = $tc->fecha_hora->copy()->locale('es');
        $texto = "{$saludo}, su videoconsulta en {$this->marca->nombre()} con {$tc->medico?->nombre_completo} es el "
            . $fecha->translatedFormat('l j \d\e F') . ' a las ' . $fecha->format('H:i') . ". Ingrese a este enlace para aceptar el consentimiento y conectarse: {$tc->enlacePaciente()}";

        return "https://wa.me/{$telefono}?text=" . rawurlencode($texto);
    }
}
