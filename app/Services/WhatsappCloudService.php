<?php

namespace App\Services;

use App\Jobs\EnviarWhatsapp;
use App\Models\Cita;
use App\Models\Encuesta;
use App\Models\Paciente;
use App\Models\WhatsappMensaje;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Envío de WhatsApp por la API oficial de Meta (WhatsApp Business Cloud API).
 * Los mensajes que inicia la clínica deben ser plantillas aprobadas por Meta.
 * Mientras el módulo esté apagado o sin configurar, la app sigue usando los enlaces wa.me.
 */
class WhatsappCloudService
{
    /** Errores de Meta más comunes, en palabras del personal */
    public const ERRORES = [
        '131047' => 'Pasaron más de 24 horas desde el último mensaje del paciente: solo se pueden enviar plantillas aprobadas.',
        '131026' => 'No se pudo entregar: el número no tiene WhatsApp o no acepta mensajes de empresas.',
        '132000' => 'La cantidad de datos no coincide con la plantilla aprobada en Meta.',
        '132001' => 'La plantilla no existe en Meta o no está aprobada en ese idioma.',
        '132005' => 'El texto de la plantilla con los datos es demasiado largo.',
        '132012' => 'El formato de los datos no coincide con la plantilla.',
        '131056' => 'Se enviaron demasiados mensajes seguidos a este número: se reintentará.',
        '130429' => 'Se alcanzó el límite de envíos de Meta: se reintentará.',
        '131048' => 'Meta limitó el envío por calidad del número: revise las plantillas y bajas.',
        '131051' => 'Tipo de mensaje no admitido.',
        '190' => 'El token de acceso de Meta es inválido o venció: actualícelo en Configuración.',
        '100' => 'Solicitud inválida: revise el ID del número de teléfono y las plantillas en Configuración.',
        '10' => 'La aplicación de Meta no tiene permiso para enviar mensajes con este número.',
    ];

    /** Se reintentan: límites de envío, errores temporales de Meta y fallas de conexión */
    private const REINTENTABLES = ['131056', '130429', '131000', '131016', '2', '1', 'conexion', 'http5xx'];

    public const USOS = [
        'recordatorio' => 'recordatorio_cita',
        'confirmacion' => 'confirmacion_reserva',
        'encuesta' => 'encuesta',
    ];

    public function __construct(private AjustesService $ajustes, private MarcaService $marca) {}

    public function activo(): bool
    {
        return (bool) $this->ajustes->get('whatsapp.activo');
    }

    public function configurado(): bool
    {
        return $this->ajustes->get('whatsapp.phone_number_id') !== '' && $this->token() !== '';
    }

    /** ¿Se envía este uso (recordatorio | confirmacion | encuesta) por la API? */
    public function usoActivo(string $uso): bool
    {
        return $this->activo() && $this->configurado() && (bool) $this->ajustes->get("whatsapp.usar_{$uso}");
    }

    public function token(): string
    {
        return $this->secreto('whatsapp.token');
    }

    public function appSecret(): string
    {
        return $this->secreto('whatsapp.app_secret');
    }

    private function secreto(string $clave): string
    {
        $cifrado = (string) $this->ajustes->get($clave);
        if ($cifrado === '') {
            return '';
        }
        try {
            return Crypt::decryptString($cifrado);
        } catch (\Throwable) {
            return '';
        }
    }

    public static function mensajeError(?string $codigo, ?string $original = null): string
    {
        return self::ERRORES[(string) $codigo] ?? ($original ? "Error de WhatsApp: {$original}" : 'No se pudo enviar el mensaje.');
    }

    /**
     * Llama a la API. Devuelve ['ok' => bool, 'wamid' => ?string, 'codigo' => ?string, 'error' => ?string, 'reintentar' => bool].
     *
     * @param string[] $parametros  textos del cuerpo, en el orden {{1}}, {{2}}…
     * @param string[] $botones     payload de cada botón de respuesta rápida, en orden
     */
    public function enviarPlantilla(string $telefono, string $plantilla, string $idioma, array $parametros = [], array $botones = []): array
    {
        $componentes = [];
        if ($parametros) {
            $componentes[] = [
                'type' => 'body',
                'parameters' => array_map(fn ($t) => ['type' => 'text', 'text' => (string) $t], array_values($parametros)),
            ];
        }
        foreach (array_values($botones) as $i => $payload) {
            $componentes[] = [
                'type' => 'button',
                'sub_type' => 'quick_reply',
                'index' => (string) $i,
                'parameters' => [['type' => 'payload', 'payload' => $payload]],
            ];
        }

        $cuerpo = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $telefono,
            'type' => 'template',
            'template' => array_filter([
                'name' => $plantilla,
                'language' => ['code' => $idioma],
                'components' => $componentes ?: null,
            ]),
        ];

        $url = sprintf('https://graph.facebook.com/%s/%s/messages',
            $this->ajustes->get('whatsapp.api_version'), $this->ajustes->get('whatsapp.phone_number_id'));

        try {
            $r = Http::withToken($this->token())->acceptJson()->timeout(20)->post($url, $cuerpo);
        } catch (ConnectionException $e) {
            return ['ok' => false, 'wamid' => null, 'codigo' => 'conexion', 'error' => 'No hubo conexión con WhatsApp: se reintentará.', 'reintentar' => true];
        }

        if ($r->successful() && $wamid = $r->json('messages.0.id')) {
            return ['ok' => true, 'wamid' => $wamid, 'codigo' => null, 'error' => null, 'reintentar' => false];
        }

        $codigo = (string) ($r->json('error.code') ?? ($r->serverError() ? 'http5xx' : $r->status()));
        $detalle = $r->json('error.error_data.details') ?? $r->json('error.message');

        return [
            'ok' => false,
            'wamid' => null,
            'codigo' => $codigo,
            'error' => self::mensajeError($codigo, $detalle),
            'reintentar' => in_array($codigo, self::REINTENTABLES, true) || $r->serverError(),
        ];
    }

    /** Celular del paciente en formato internacional, o null si no puede recibir WhatsApp de la clínica */
    public function telefonoDe(?Paciente $p): ?string
    {
        if (!$p || $p->whatsapp_baja) {
            return null;
        }

        return WhatsappService::normalizarTelefono($p->telefono) ?? WhatsappService::normalizarTelefono($p->telefono_secundario);
    }

    /** Nombre para el mensaje: sin nombre si el paciente tiene identidad reservada (AM 5216-A Art. 13) */
    public function nombreDe(?Paciente $p): string
    {
        if (!$p || $p->identidad_reservada) {
            return 'Paciente';
        }

        return trim((string) strtok((string) $p->nombres, ' ')) ?: 'Paciente';
    }

    /**
     * Crea el mensaje y lo encola. Devuelve [mensaje|null, motivo si no se envía].
     *
     * @return array{0: ?WhatsappMensaje, 1: ?string}
     */
    public function encolar(string $tipo, ?Paciente $paciente, string $plantilla, array $parametros, array $botones = [], array $relaciones = []): array
    {
        if (!$this->activo() || !$this->configurado()) {
            return [null, 'El envío por WhatsApp no está configurado.'];
        }
        if ($paciente?->whatsapp_baja) {
            return [null, 'El paciente pidió no recibir mensajes por WhatsApp.'];
        }
        $telefono = $this->telefonoDe($paciente);
        if (!$telefono) {
            return [null, 'El paciente no tiene un celular válido.'];
        }

        $mensaje = WhatsappMensaje::create($relaciones + [
            'paciente_id' => $paciente?->id,
            'user_id' => auth()->id(),
            'tipo' => $tipo,
            'telefono' => $telefono,
            'plantilla' => $plantilla,
            'parametros' => ['cuerpo' => array_values($parametros), 'botones' => array_values($botones)],
        ]);
        EnviarWhatsapp::dispatch($mensaje->id);

        return [$mensaje, null];
    }

    /** {{1}} paciente, {{2}} fecha, {{3}} hora, {{4}} médico, {{5}} clínica */
    private function datosCita(Cita $cita): array
    {
        $fecha = $cita->fecha_hora->copy()->locale('es');

        return [
            $this->nombreDe($cita->paciente),
            $fecha->translatedFormat('l j \d\e F'),
            $fecha->format('H:i'),
            (string) ($cita->medico?->nombre_completo ?: 'su médico'),
            $this->marca->nombre(),
        ];
    }

    public function recordatorioCita(Cita $cita): array
    {
        $cita->loadMissing('paciente', 'medico');

        return $this->encolar('recordatorio_cita', $cita->paciente, (string) $this->ajustes->get('whatsapp.plantilla_recordatorio'),
            $this->datosCita($cita), ["CONFIRMAR_CITA:{$cita->id}", "CANCELAR_CITA:{$cita->id}"], ['cita_id' => $cita->id]);
    }

    public function confirmacionReserva(Cita $cita): array
    {
        $cita->loadMissing('paciente', 'medico');

        return $this->encolar('confirmacion_reserva', $cita->paciente, (string) $this->ajustes->get('whatsapp.plantilla_confirmacion'),
            $this->datosCita($cita), [], ['cita_id' => $cita->id]);
    }

    public function encuesta(Encuesta $e): array
    {
        $e->loadMissing('paciente');

        return $this->encolar('encuesta', $e->paciente, (string) $this->ajustes->get('whatsapp.plantilla_encuesta'),
            [$this->nombreDe($e->paciente), $e->url()], [], ['encuesta_id' => $e->id]);
    }

    /** Prueba de conexión con la plantilla hello_world que Meta crea en toda cuenta nueva */
    public function probar(string $telefono): array
    {
        $numero = WhatsappService::normalizarTelefono($telefono) ?? preg_replace('/\D/', '', $telefono);

        return $this->enviarPlantilla($numero, 'hello_world', 'en_US');
    }
}
