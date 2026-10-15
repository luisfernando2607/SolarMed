<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Paciente;
use App\Models\WhatsappMensaje;
use App\Services\AjustesService;
use App\Services\AuditoriaService;
use App\Services\WhatsappCloudService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Webhook de la API oficial de WhatsApp (Meta): estados de entrega y respuestas de los pacientes.
 * Meta reintenta y puede repetir avisos: todo el procesamiento es idempotente.
 */
class WhatsappWebhookController extends Controller
{
    /** Palabras que dan de baja al paciente (mensaje completo, sin importar mayúsculas ni tildes) */
    private const BAJA = ['BAJA', 'STOP', 'NO'];

    public function __construct(private AjustesService $ajustes, private WhatsappCloudService $wa) {}

    /** Verificación del webhook al registrarlo en Meta */
    public function verificar(Request $request): Response
    {
        $esperado = (string) $this->ajustes->get('whatsapp.verify_token');

        if ($request->query('hub_mode') === 'subscribe' && $esperado !== ''
            && hash_equals($esperado, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }

        return response('Prohibido', 403);
    }

    public function recibir(Request $request): Response
    {
        // Firma de Meta sobre el cuerpo tal como llegó: sin app secret configurado no se acepta nada
        $secreto = $this->wa->appSecret();
        $firma = (string) $request->header('X-Hub-Signature-256');
        if ($secreto === '' || !hash_equals('sha256=' . hash_hmac('sha256', $request->getContent(), $secreto), $firma)) {
            return response('Firma inválida', 403);
        }

        $datos = json_decode($request->getContent(), true);
        foreach ((array) ($datos['entry'] ?? []) as $entrada) {
            foreach ((array) ($entrada['changes'] ?? []) as $cambio) {
                $valor = (array) ($cambio['value'] ?? []);
                foreach ((array) ($valor['statuses'] ?? []) as $estado) {
                    $this->estado((array) $estado);
                }
                foreach ((array) ($valor['messages'] ?? []) as $mensaje) {
                    $this->mensajeEntrante((array) $mensaje);
                }
            }
        }

        return response('OK', 200);
    }

    private function estado(array $e): void
    {
        $mensaje = isset($e['id']) ? WhatsappMensaje::where('wamid', $e['id'])->first() : null;
        if (!$mensaje) {
            return;
        }
        $cuando = isset($e['timestamp']) ? Carbon::createFromTimestamp((int) $e['timestamp'], config('app.timezone')) : now();

        match ($e['status'] ?? null) {
            'sent' => $mensaje->avanzarEstado('enviado', $cuando),
            'delivered' => $mensaje->avanzarEstado('entregado', $cuando),
            'read' => $mensaje->avanzarEstado('leido', $cuando),
            'failed' => $mensaje->avanzarEstado('fallido', $cuando, (string) ($e['errors'][0]['code'] ?? ''),
                WhatsappCloudService::mensajeError((string) ($e['errors'][0]['code'] ?? ''), $e['errors'][0]['title'] ?? $e['errors'][0]['message'] ?? null)),
            default => null,
        };
    }

    private function mensajeEntrante(array $m): void
    {
        // Meta puede repetir el mismo aviso: cada mensaje entrante se procesa una sola vez
        if (empty($m['id']) || empty($m['from']) || !Cache::add('whatsapp-entrante:' . $m['id'], true, now()->addDays(8))) {
            return;
        }

        $telefono = preg_replace('/\D/', '', (string) $m['from']);
        $payload = $m['button']['payload'] ?? $m['interactive']['button_reply']['id'] ?? null;
        $texto = $m['text']['body'] ?? $m['button']['text'] ?? $m['interactive']['button_reply']['title'] ?? null;

        $original = !empty($m['context']['id']) ? WhatsappMensaje::where('wamid', $m['context']['id'])->first() : null;
        $original ??= WhatsappMensaje::where('telefono', $telefono)->latest('id')->first();

        if ($payload && preg_match('/^(CONFIRMAR|CANCELAR)_CITA:(\d+)$/', $payload, $p)) {
            $this->respuestaCita($p[1], (int) $p[2], $telefono);
        } elseif ($texto !== null && in_array(Str::upper(Str::ascii(trim($texto))), self::BAJA, true)) {
            $this->darDeBaja($telefono);
        }

        if ($original && ($texto !== null || $payload)) {
            $original->update([
                'respuesta_texto' => $texto !== null ? mb_substr($texto, 0, 1000) : null,
                'respuesta_payload' => $payload ? mb_substr($payload, 0, 100) : null,
                'respondido_at' => now(),
                'respuesta_revisada_at' => null,
            ]);
        }
    }

    /** El paciente pulsó Confirmar o Cancelar en el recordatorio: se aplica una sola vez y solo a citas por venir */
    private function respuestaCita(string $accion, int $citaId, string $telefono): void
    {
        DB::transaction(function () use ($accion, $citaId, $telefono) {
            $cita = Cita::with('paciente')->lockForUpdate()->find($citaId);
            if (!$cita || !$cita->fecha_hora->isFuture() || !in_array($cita->estado, ['pendiente', 'confirmada'], true)
                || $this->wa->telefonoDe($cita->paciente) !== $telefono) {
                return;
            }

            $nuevo = $accion === 'CONFIRMAR' ? 'confirmada' : 'cancelada';
            if ($cita->estado === $nuevo) {
                return;
            }
            $cita->update(['estado' => $nuevo]);
            app(AuditoriaService::class)->registrar('editar', 'cita', $cita->id, $cita->paciente_id,
                'Respuesta por WhatsApp: ' . ($nuevo === 'confirmada' ? 'confirmó' : 'canceló') . ' la cita');
        });
    }

    /** El paciente pidió no recibir más mensajes: se marca en todas las fichas con ese celular */
    private function darDeBaja(string $telefono): void
    {
        // Los teléfonos pueden estar guardados con espacios o guiones: se buscan los 9 dígitos en orden y se confirma al normalizar
        $patron = '%' . implode('%', str_split(substr($telefono, -9))) . '%';
        Paciente::where(fn ($q) => $q->where('telefono', 'like', $patron)->orWhere('telefono_secundario', 'like', $patron))
            ->get()
            ->filter(fn (Paciente $p) => in_array($telefono, [WhatsappService::normalizarTelefono($p->telefono), WhatsappService::normalizarTelefono($p->telefono_secundario)], true))
            ->each(function (Paciente $p) {
                if (!$p->whatsapp_baja) {
                    $p->forceFill(['whatsapp_baja' => true, 'whatsapp_baja_at' => now()])->save();
                    app(AuditoriaService::class)->registrar('editar', 'paciente', $p->id, $p->id, 'Respuesta por WhatsApp: pidió no recibir más mensajes');
                }
            });
    }
}
