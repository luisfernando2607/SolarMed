<?php

namespace App\Livewire;

use App\Models\WhatsappMensaje;
use App\Services\AjustesService;
use App\Services\AuditoriaService;
use App\Services\WhatsappCloudService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Configuración del envío por la API oficial de WhatsApp (Meta). El token y el app secret se guardan cifrados
 * y nunca se vuelven a mostrar: solo se pueden reemplazar.
 */
class ConfiguracionWhatsapp extends Component
{
    /** Plantillas que el administrador debe crear en Meta (categoría Utilidad, idioma español) */
    public const PLANTILLAS = [
        'recordatorio' => [
            'titulo' => 'Recordatorio de cita',
            'variables' => ['paciente', 'fecha', 'hora', 'médico', 'clínica'],
            'texto' => "Hola {{1}}, le recordamos su cita del {{2}} a las {{3}} con {{4}} en {{5}}. ¿Nos confirma su asistencia?",
            'botones' => ['Confirmar', 'Cancelar'],
        ],
        'confirmacion' => [
            'titulo' => 'Confirmación de reserva en línea',
            'variables' => ['paciente', 'fecha', 'hora', 'médico', 'clínica'],
            'texto' => "Hola {{1}}, su cita del {{2}} a las {{3}} con {{4}} en {{5}} quedó confirmada. Si no puede asistir, por favor avísenos.",
            'botones' => [],
        ],
        'encuesta' => [
            'titulo' => 'Encuesta de satisfacción',
            'variables' => ['paciente', 'enlace de la encuesta'],
            'texto' => "Hola {{1}}, gracias por atenderse con nosotros. ¿Nos ayuda con un minuto para calificar la atención? Ingrese aquí: {{2}} ¡Muchas gracias!",
            'botones' => [],
        ],
    ];

    public bool $activo = false;
    public string $api_version = '';
    public string $phone_number_id = '';
    public string $waba_id = '';
    public string $idioma = '';
    public string $token_nuevo = '';
    public string $app_secret_nuevo = '';
    public array $usar = [];
    public array $plantillas = [];
    public string $telefono_prueba = '';
    public ?array $resultadoPrueba = null;

    public function mount(): void
    {
        $a = app(AjustesService::class);
        // Token de verificación del webhook: se genera una vez para copiarlo en Meta
        if ($a->get('whatsapp.verify_token') === '') {
            $a->guardar(['whatsapp.verify_token' => Str::random(32)]);
        }
        $this->activo = (bool) $a->get('whatsapp.activo');
        $this->api_version = (string) $a->get('whatsapp.api_version');
        $this->phone_number_id = (string) $a->get('whatsapp.phone_number_id');
        $this->waba_id = (string) $a->get('whatsapp.waba_id');
        $this->idioma = (string) $a->get('whatsapp.idioma');
        foreach (array_keys(self::PLANTILLAS) as $uso) {
            $this->usar[$uso] = (bool) $a->get("whatsapp.usar_{$uso}");
            $this->plantillas[$uso] = (string) $a->get("whatsapp.plantilla_{$uso}");
        }
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');
        $this->validate([
            'api_version' => ['required', 'regex:/^v\d{1,3}\.\d$/'],
            'phone_number_id' => ['nullable', 'regex:/^\d{5,25}$/'],
            'waba_id' => ['nullable', 'regex:/^\d{5,25}$/'],
            'idioma' => ['required', 'regex:/^[a-z]{2}(_[A-Z]{2})?$/'],
            'token_nuevo' => 'nullable|string|min:20|max:1000',
            'app_secret_nuevo' => 'nullable|string|min:16|max:200',
            'plantillas.*' => ['required', 'regex:/^[a-z0-9_]{1,512}$/'],
            'usar.*' => 'boolean',
        ], [
            'api_version.regex' => 'Use el formato de Meta, por ejemplo v23.0.',
            'phone_number_id.regex' => 'El identificador del número tiene solo dígitos (lo da Meta en WhatsApp → Configuración de la API).',
            'waba_id.regex' => 'El identificador de la cuenta tiene solo dígitos.',
            'idioma.regex' => 'Use el código de idioma de la plantilla en Meta, por ejemplo es o es_EC.',
            'plantillas.*.regex' => 'El nombre de la plantilla va en minúsculas, números y guiones bajos, igual que en Meta.',
        ]);

        $wa = app(WhatsappCloudService::class);
        $tendraToken = $this->token_nuevo !== '' || $wa->token() !== '';
        if ($this->activo && ($this->phone_number_id === '' || !$tendraToken)) {
            $this->addError('activo', 'Para activar el envío ingrese el identificador del número y el token de acceso.');
            return;
        }

        $valores = [
            'whatsapp.activo' => $this->activo,
            'whatsapp.api_version' => $this->api_version,
            'whatsapp.phone_number_id' => $this->phone_number_id,
            'whatsapp.waba_id' => $this->waba_id,
            'whatsapp.idioma' => $this->idioma,
        ];
        foreach (array_keys(self::PLANTILLAS) as $uso) {
            $valores["whatsapp.usar_{$uso}"] = (bool) ($this->usar[$uso] ?? false);
            $valores["whatsapp.plantilla_{$uso}"] = $this->plantillas[$uso];
        }
        if ($this->token_nuevo !== '') {
            $valores['whatsapp.token'] = Crypt::encryptString(trim($this->token_nuevo));
        }
        if ($this->app_secret_nuevo !== '') {
            $valores['whatsapp.app_secret'] = Crypt::encryptString(trim($this->app_secret_nuevo));
        }

        app(AjustesService::class)->guardar($valores);
        app(AuditoriaService::class)->registrar('editar', 'configuracion', null, null,
            'WhatsApp ' . ($this->activo ? 'activado' : 'desactivado') . ($this->token_nuevo !== '' ? ' · token reemplazado' : '') . ($this->app_secret_nuevo !== '' ? ' · app secret reemplazado' : ''));
        $this->reset('token_nuevo', 'app_secret_nuevo');
        $this->dispatch('notify', message: 'Configuración de WhatsApp guardada.', type: 'success');
    }

    /** Envía la plantilla hello_world de Meta para comprobar número, token y permisos */
    public function probar(): void
    {
        $this->authorize('configuracion.editar');
        $this->validate(['telefono_prueba' => ['required', 'regex:/^\+?[\d\s-]{9,16}$/']], ['telefono_prueba.regex' => 'Ingrese un celular, por ejemplo 0991234567.']);

        $wa = app(WhatsappCloudService::class);
        if (!$wa->configurado()) {
            $this->resultadoPrueba = ['ok' => false, 'texto' => 'Guarde primero el identificador del número y el token de acceso.'];
            return;
        }

        $r = $wa->probar($this->telefono_prueba);
        WhatsappMensaje::create([
            'user_id' => auth()->id(), 'tipo' => 'prueba', 'telefono' => mb_substr(preg_replace('/\D/', '', $this->telefono_prueba), 0, 15),
            'plantilla' => 'hello_world', 'estado' => $r['ok'] ? 'enviado' : 'fallido', 'wamid' => $r['wamid'], 'intentos' => 1,
            'enviado_at' => $r['ok'] ? now() : null, 'error_codigo' => $r['codigo'], 'error_mensaje' => $r['error'] ? mb_substr($r['error'], 0, 255) : null,
        ]);
        $this->resultadoPrueba = $r['ok']
            ? ['ok' => true, 'texto' => 'Meta aceptó el mensaje de prueba. Debería llegar en unos segundos.']
            : ['ok' => false, 'texto' => $r['error']];
    }

    public function render()
    {
        $wa = app(WhatsappCloudService::class);

        return view('livewire.configuracion-whatsapp', [
            'tieneToken' => $wa->token() !== '',
            'tieneSecreto' => $wa->appSecret() !== '',
            'verifyToken' => (string) app(AjustesService::class)->get('whatsapp.verify_token'),
            'urlWebhook' => route('whatsapp.webhook'),
            'esHttps' => str_starts_with(route('whatsapp.webhook'), 'https://'),
        ]);
    }
}
