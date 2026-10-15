<?php

namespace Tests\Feature\Clinica;

use App\Jobs\EnviarWhatsapp;
use App\Livewire\Agenda;
use App\Livewire\ConfiguracionWhatsapp;
use App\Livewire\WhatsappMensajes;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Models\WhatsappMensaje;
use App\Services\AjustesService;
use App\Services\WhatsappCloudService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsappCloudTest extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'secreto-de-la-app-de-meta';

    private Cita $cita;
    private Paciente $ana;
    private User $recepcion;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $esp->id]);
        $this->ana = Paciente::create(['nombres' => 'Ana Lucía', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'telefono' => '099 123 4567']);
        $this->cita = Cita::create(['paciente_id' => $this->ana->id, 'medico_id' => $medico->id, 'especialidad_id' => $esp->id, 'tipo' => 'Control',
            'fecha_hora' => now()->addDay()->setTime(10, 30), 'duracion_min' => 20, 'estado' => 'pendiente']);
        $this->recepcion = User::factory()->create(); $this->recepcion->assignRole('secretaria');
    }

    private function configurar(array $extra = []): void
    {
        app(AjustesService::class)->guardar($extra + [
            'whatsapp.activo' => true,
            'whatsapp.phone_number_id' => '123456789012345',
            'whatsapp.token' => Crypt::encryptString('EAAG-token-de-prueba-123456'),
            'whatsapp.app_secret' => Crypt::encryptString(self::SECRETO),
            'whatsapp.verify_token' => 'verificar-123',
        ]);
    }

    /** Respuesta simulada de Meta; cada prueba la cambia con fakeOk() o fakeError() (Http::fake acumula stubs, por eso uno solo) */
    private ?\Closure $respuesta = null;
    private bool $falsoRegistrado = false;

    private function registrarFalso(): void
    {
        if (!$this->falsoRegistrado) {
            Http::fake(fn () => ($this->respuesta)());
            $this->falsoRegistrado = true;
        }
    }

    /** Sin wamid fijo, cada envío recibe uno distinto (como en Meta) */
    private function fakeOk(?string $wamid = null): void
    {
        $this->respuesta = fn () => Http::response(['messaging_product' => 'whatsapp', 'contacts' => [['wa_id' => '593991234567']],
            'messages' => [['id' => $wamid ?? 'wamid.' . \Illuminate\Support\Str::random(10)]]]);
        $this->registrarFalso();
    }

    private function fakeError(int $codigo, int $http = 400): void
    {
        $this->respuesta = fn () => Http::response(['error' => ['code' => $codigo, 'message' => "Error {$codigo}"]], $http);
        $this->registrarFalso();
    }

    private function webhook(array $valor)
    {
        $cuerpo = json_encode(['object' => 'whatsapp_business_account', 'entry' => [['id' => '1', 'changes' => [['field' => 'messages', 'value' => $valor]]]]]);

        return $this->call('POST', '/whatsapp/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256=' . hash_hmac('sha256', $cuerpo, self::SECRETO),
        ], $cuerpo);
    }

    public function test_apagado_no_envia_y_la_agenda_mantiene_el_enlace(): void
    {
        Http::fake();
        $this->assertFalse(app(WhatsappCloudService::class)->usoActivo('recordatorio'));
        $this->artisan('citas:recordatorios')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertSame(0, WhatsappMensaje::count());

        Livewire::actingAs($this->recepcion)->test(Agenda::class)->call('verCita', $this->cita->id)
            ->assertSee('wa.me/593991234567')->assertDontSee('wire:click="enviarWhatsapp"', false);
    }

    public function test_recordatorio_envia_la_plantilla_correcta_una_sola_vez(): void
    {
        $this->configurar();
        $this->fakeOk('wamid.ABC');

        $this->artisan('citas:recordatorios')->expectsOutputToContain('1 por WhatsApp')->assertSuccessful();
        $this->artisan('citas:recordatorios')->expectsOutputToContain('0 por WhatsApp');

        Http::assertSentCount(1);
        Http::assertSent(function (Request $r) {
            $d = $r->data();
            return $r->url() === 'https://graph.facebook.com/v23.0/123456789012345/messages'
                && $r->hasHeader('Authorization', 'Bearer EAAG-token-de-prueba-123456')
                && $d['to'] === '593991234567' && $d['type'] === 'template'
                && $d['template']['name'] === 'recordatorio_cita' && $d['template']['language']['code'] === 'es'
                && $d['template']['components'][0]['parameters'][0]['text'] === 'Ana'
                && $d['template']['components'][0]['parameters'][2]['text'] === '10:30'
                && $d['template']['components'][0]['parameters'][3]['text'] === 'Dr. María Cuadros'
                && $d['template']['components'][1]['sub_type'] === 'quick_reply'
                && $d['template']['components'][1]['parameters'][0]['payload'] === "CONFIRMAR_CITA:{$this->cita->id}"
                && $d['template']['components'][2]['index'] === '1'
                && $d['template']['components'][2]['parameters'][0]['payload'] === "CANCELAR_CITA:{$this->cita->id}";
        });

        $m = WhatsappMensaje::sole();
        $this->assertSame('enviado', $m->estado);
        $this->assertSame('wamid.ABC', $m->wamid);
        $this->assertNotNull($this->cita->fresh()->whatsapp_recordatorio_at);
    }

    public function test_identidad_reservada_y_baja_se_respetan(): void
    {
        $this->configurar();
        $this->fakeOk();

        $this->ana->update(['identidad_reservada' => true]);
        app(WhatsappCloudService::class)->recordatorioCita($this->cita->fresh());
        Http::assertSent(fn (Request $r) => $r->data()['template']['components'][0]['parameters'][0]['text'] === 'Paciente');

        $this->ana->forceFill(['whatsapp_baja' => true])->save();
        [$mensaje, $motivo] = app(WhatsappCloudService::class)->recordatorioCita($this->cita->fresh());
        $this->assertNull($mensaje);
        $this->assertStringContainsString('no recibir', $motivo);
        Http::assertSentCount(1);
    }

    public function test_errores_permanentes_y_reintentables(): void
    {
        $this->configurar();
        $this->fakeError(132001, 404);
        app(WhatsappCloudService::class)->recordatorioCita($this->cita);
        $m = WhatsappMensaje::sole();
        $this->assertSame('fallido', $m->estado);
        $this->assertSame('132001', $m->error_codigo);
        $this->assertStringContainsString('plantilla no existe', $m->error_mensaje);

        $r = app(WhatsappCloudService::class);
        $this->fakeError(130429);
        $this->assertTrue($r->enviarPlantilla('593991234567', 'x', 'es')['reintentar']);
        $this->fakeError(190, 401);
        $res = $r->enviarPlantilla('593991234567', 'x', 'es');
        $this->assertFalse($res['reintentar']);
        $this->assertStringContainsString('token', $res['error']);

        // Reintento manual desde la pantalla
        $this->fakeOk('wamid.REINTENTO');
        Livewire::actingAs($this->recepcion)->test(WhatsappMensajes::class)->call('reintentar', $m->id);
        $this->assertSame('enviado', $m->fresh()->estado);
    }

    public function test_el_job_reintenta_solo_errores_temporales(): void
    {
        $this->configurar();
        $m = WhatsappMensaje::create(['paciente_id' => $this->ana->id, 'tipo' => 'recordatorio_cita', 'telefono' => '593991234567', 'plantilla' => 'recordatorio_cita', 'parametros' => ['cuerpo' => ['Ana'], 'botones' => []]]);

        $this->respuesta = fn () => Http::response([], 503);
        $this->registrarFalso();
        $job = (new EnviarWhatsapp($m->id))->withFakeQueueInteractions();
        $job->handle(app(WhatsappCloudService::class));
        $job->assertReleased(60);
        $this->assertSame('pendiente', $m->fresh()->estado);

        $this->fakeError(131026);
        $job = (new EnviarWhatsapp($m->id))->withFakeQueueInteractions();
        $job->handle(app(WhatsappCloudService::class));
        $job->assertNotReleased();
        $this->assertSame('fallido', $m->fresh()->estado);
        $this->assertStringContainsString('no tiene WhatsApp', $m->fresh()->error_mensaje);
    }

    public function test_webhook_verificacion_y_firma(): void
    {
        $this->configurar();
        $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=verificar-123&hub.challenge=987654')->assertOk()->assertSeeText('987654');
        $this->get('/whatsapp/webhook?hub.mode=subscribe&hub.verify_token=otro&hub.challenge=987654')->assertForbidden();

        $this->call('POST', '/whatsapp/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => 'sha256=falsa'], '{"entry":[]}')
            ->assertForbidden();
        $this->webhook(['statuses' => []])->assertOk();
    }

    public function test_estados_no_retroceden_y_se_toleran_duplicados(): void
    {
        $this->configurar();
        $this->fakeOk('wamid.X1');
        app(WhatsappCloudService::class)->recordatorioCita($this->cita);

        $estado = fn ($s) => ['statuses' => [['id' => 'wamid.X1', 'status' => $s, 'timestamp' => (string) now()->timestamp, 'recipient_id' => '593991234567']]];
        $this->webhook($estado('read'))->assertOk();
        $this->webhook($estado('delivered'))->assertOk();
        $this->webhook($estado('sent'))->assertOk();
        $this->webhook($estado('read'))->assertOk();
        $this->webhook(['statuses' => [['id' => 'wamid.X1', 'status' => 'failed', 'errors' => [['code' => 131026, 'title' => 'x']]]]])->assertOk();
        $this->webhook(['statuses' => [['id' => 'wamid.NO-EXISTE', 'status' => 'read']], 'otra_cosa' => true])->assertOk();

        $m = WhatsappMensaje::sole();
        $this->assertSame('leido', $m->estado);
        $this->assertNotNull($m->entregado_at);
        $this->assertNotNull($m->leido_at);
    }

    public function test_boton_confirmar_y_cancelar_actualizan_la_cita_una_vez(): void
    {
        $this->configurar();
        $this->fakeOk('wamid.R1');
        app(WhatsappCloudService::class)->recordatorioCita($this->cita);

        $boton = fn ($id, $payload) => ['messages' => [['from' => '593991234567', 'id' => $id, 'timestamp' => '1', 'type' => 'button',
            'context' => ['id' => 'wamid.R1'], 'button' => ['payload' => $payload, 'text' => 'Confirmar']]]];

        $this->webhook($boton('wamid.IN1', "CONFIRMAR_CITA:{$this->cita->id}"))->assertOk();
        $this->webhook($boton('wamid.IN1', "CONFIRMAR_CITA:{$this->cita->id}"))->assertOk(); // duplicado de Meta
        $this->assertSame('confirmada', $this->cita->fresh()->estado);
        $this->assertSame(1, RegistroAcceso::where('detalle', 'like', 'Respuesta por WhatsApp%')->count());
        $this->assertSame("CONFIRMAR_CITA:{$this->cita->id}", WhatsappMensaje::sole()->respuesta_payload);

        // Otro número no puede tocar la cita
        $otro = ['messages' => [['from' => '593987654321', 'id' => 'wamid.IN2', 'type' => 'button', 'button' => ['payload' => "CANCELAR_CITA:{$this->cita->id}", 'text' => 'Cancelar']]]];
        $this->webhook($otro)->assertOk();
        $this->assertSame('confirmada', $this->cita->fresh()->estado);

        $this->webhook($boton('wamid.IN3', "CANCELAR_CITA:{$this->cita->id}"))->assertOk();
        $this->assertSame('cancelada', $this->cita->fresh()->estado);
    }

    public function test_responder_baja_desactiva_los_mensajes(): void
    {
        $this->configurar();
        $this->fakeOk('wamid.B1');
        app(WhatsappCloudService::class)->recordatorioCita($this->cita);

        $this->webhook(['messages' => [['from' => '593991234567', 'id' => 'wamid.IN9', 'type' => 'text', 'text' => ['body' => ' Baja ']]]])->assertOk();
        $this->assertTrue($this->ana->fresh()->whatsapp_baja);
        $this->assertSame('Baja', trim(WhatsappMensaje::sole()->respuesta_texto));

        $this->webhook(['messages' => [['from' => '593991234567', 'id' => 'wamid.IN10', 'type' => 'text', 'text' => ['body' => 'No puedo ir mañana']]]])->assertOk();
        $this->assertSame('No puedo ir mañana', WhatsappMensaje::sole()->respuesta_texto);
    }

    public function test_configuracion_guarda_secretos_cifrados_y_no_los_muestra(): void
    {
        $admin = User::factory()->create(); $admin->assignRole('admin');
        Livewire::actingAs($admin)->test(ConfiguracionWhatsapp::class)
            ->set('activo', true)->call('guardar')->assertHasErrors('activo')
            ->set('phone_number_id', '123456789012345')->set('token_nuevo', 'EAAG-token-muy-secreto-0001')->set('app_secret_nuevo', 'secreto-app-0123456789')
            ->call('guardar')->assertHasNoErrors()
            ->assertSet('token_nuevo', '')
            ->assertDontSee('EAAG-token-muy-secreto-0001')
            ->assertSee('configurado ✓');

        $guardado = (string) app(AjustesService::class)->get('whatsapp.token');
        $this->assertStringNotContainsString('EAAG', $guardado);
        $this->assertSame('EAAG-token-muy-secreto-0001', app(WhatsappCloudService::class)->token());
        $this->assertNotSame('', (string) app(AjustesService::class)->get('whatsapp.verify_token'));

        $this->fakeOk('wamid.PRUEBA');
        Livewire::actingAs($admin)->test(ConfiguracionWhatsapp::class)->set('telefono_prueba', '0991234567')->call('probar')
            ->assertSee('Meta aceptó el mensaje de prueba');
        Http::assertSent(fn (Request $r) => $r->data()['template']['name'] === 'hello_world' && $r->data()['template']['language']['code'] === 'en_US');
    }

    public function test_agenda_envia_por_api_y_avisa_al_confirmar_reserva_web(): void
    {
        $this->configurar();
        $this->fakeOk();

        Livewire::actingAs($this->recepcion)->test(Agenda::class)->call('verCita', $this->cita->id)
            ->assertSee('wire:click="enviarWhatsapp"', false)->call('enviarWhatsapp');
        $this->assertSame('recordatorio_cita', WhatsappMensaje::sole()->tipo);

        $this->cita->update(['origen' => 'web']);
        Livewire::actingAs($this->recepcion)->test(Agenda::class)->call('verCita', $this->cita->id)->call('cambiarEstado', 'confirmada');
        $this->assertSame(1, WhatsappMensaje::where('tipo', 'confirmacion_reserva')->count());
    }

    public function test_permisos(): void
    {
        $enf = User::factory()->create(); $enf->assignRole('enfermeria');
        $this->actingAs($enf)->get(route('whatsapp.mensajes'))->assertForbidden();
        $this->actingAs($this->recepcion)->get(route('whatsapp.mensajes'))->assertOk()->assertSee('WhatsApp');

        $medico = User::factory()->create(); $medico->assignRole('medico');
        Livewire::actingAs($medico)->test(ConfiguracionWhatsapp::class)->call('guardar')->assertForbidden();
    }
}
