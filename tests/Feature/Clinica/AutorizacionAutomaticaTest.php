<?php

namespace Tests\Feature\Clinica;

use App\Jobs\AutorizarComprobanteSri;
use App\Livewire\VerFactura;
use App\Models\Especialidad;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\ServicioTarifario;
use App\Models\User;
use App\Services\ComprobanteSriService;
use App\Services\FacturaService;
use App\Services\SriSignService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AutorizacionAutomaticaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Factura $factura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Storage::fake('private');

        $especialidad = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $cert = openssl_csr_sign(openssl_csr_new(['commonName' => 'Prueba', 'countryName' => 'EC'], $key), null, $key, 30);
        openssl_pkcs12_export($cert, $p12, $key, 'clave');
        Storage::disk('private')->put('certificados/medico.p12', $p12);

        $medico = Medico::create([
            'user_id' => User::factory()->create()->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $especialidad->id,
            'ruc' => '0912345678001', 'razon_social' => 'GARCIA CARLOS', 'direccion_matriz' => 'Guayaquil',
            'p12_path' => 'certificados/medico.p12', 'p12_password_encrypted' => SriSignService::encryptPassword('clave'),
        ]);
        $paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        $servicio = ServicioTarifario::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Consulta', 'precio' => 30, 'activo' => true]);

        $this->factura = app(FacturaService::class)->crear([
            'paciente_id' => $paciente->id, 'medico_id' => $medico->id, 'especialidad_id' => $especialidad->id,
            'user_id' => $this->admin->id, 'fecha' => now(), 'forma_pago' => 'efectivo',
        ], [['servicio_id' => $servicio->id, 'cantidad' => 1]]);
    }

    public function test_emitir_genera_firma_envia_y_programa_la_autorizacion(): void
    {
        Queue::fake();
        Http::fake(['*' => Http::response($this->recepcion('RECIBIDA'))]);

        Livewire::actingAs($this->admin)->test(VerFactura::class, ['factura' => $this->factura])
            ->call('emitirSri');

        $this->factura->refresh();
        $this->assertSame('enviado', $this->factura->estado_sri);
        $this->assertTrue(app(\App\Services\SriXmlService::class)->claveAccesoValida($this->factura->clave_acceso));
        $this->assertStringContainsString('ds:Signature', Storage::disk('private')->get($this->factura->xml_firmado_path));
        Queue::assertPushed(AutorizarComprobanteSri::class, fn ($job) => $job->comprobante->is($this->factura));
    }

    public function test_job_reintenta_mientras_el_sri_esta_en_proceso_y_luego_autoriza(): void
    {
        $this->factura->update(['estado_sri' => 'enviado', 'clave_acceso' => '2110201101179214673900110020010000000011234567813']);

        Http::fakeSequence()
            ->push($this->autorizacionVacia())
            ->push($this->autorizado());

        $job = new AutorizarComprobanteSri($this->factura);
        try {
            $job->handle(app(ComprobanteSriService::class));
            $this->fail('Debió lanzar excepción para reintentar');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('aún procesa', $e->getMessage());
        }
        $this->assertSame('enviado', $this->factura->fresh()->estado_sri);

        $job->handle(app(ComprobanteSriService::class));
        $this->assertSame('autorizado', $this->factura->fresh()->estado_sri);
    }

    public function test_job_ignora_facturas_anuladas(): void
    {
        Http::fake();
        $this->factura->update(['estado_sri' => 'error_envio', 'estado' => 'anulada']);

        (new AutorizarComprobanteSri($this->factura))->handle(app(ComprobanteSriService::class));

        Http::assertNothingSent();
    }

    public function test_tarea_programada_retoma_solo_comprobantes_ya_enviados(): void
    {
        Queue::fake();
        $base = Factura::query()->whereKey($this->factura->id);

        $this->factura->update(['estado_sri' => 'error_autorizacion']);
        $base->update(['updated_at' => now()->subHour()]);
        $this->artisan('sri:reintentar')->assertSuccessful();
        Queue::assertPushed(AutorizarComprobanteSri::class, 1);

        // Un comprobante solo firmado no se envía sin que alguien lo decida
        Queue::fake();
        $this->factura->update(['estado_sri' => 'firmado']);
        $base->update(['updated_at' => now()->subHour()]);
        $this->artisan('sri:reintentar')->assertSuccessful();
        Queue::assertNothingPushed();

        // Uno recién actualizado espera al job que ya está en curso
        $this->factura->update(['estado_sri' => 'enviado']);
        $this->artisan('sri:reintentar')->assertSuccessful();
        Queue::assertNothingPushed();
    }

    private function recepcion(string $estado): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . '<ns2:validarComprobanteResponse xmlns:ns2="http://ec.gob.sri.ws.recepcion"><RespuestaRecepcionComprobante>'
            . "<estado>{$estado}</estado><comprobantes/></RespuestaRecepcionComprobante></ns2:validarComprobanteResponse></soap:Body></soap:Envelope>";
    }

    private function autorizacionVacia(): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . '<ns2:autorizacionComprobanteResponse xmlns:ns2="http://ec.gob.sri.ws.autorizacion"><RespuestaAutorizacionComprobante>'
            . '<numeroComprobantes>0</numeroComprobantes><autorizaciones/></RespuestaAutorizacionComprobante></ns2:autorizacionComprobanteResponse></soap:Body></soap:Envelope>';
    }

    private function autorizado(): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . '<ns2:autorizacionComprobanteResponse xmlns:ns2="http://ec.gob.sri.ws.autorizacion"><RespuestaAutorizacionComprobante>'
            . '<autorizaciones><autorizacion><estado>AUTORIZADO</estado><numeroAutorizacion>2110201101179214673900110020010000000011234567813</numeroAutorizacion>'
            . '<fechaAutorizacion>2026-10-15T10:00:00-05:00</fechaAutorizacion><ambiente>PRUEBAS</ambiente><comprobante><![CDATA[<factura/>]]></comprobante>'
            . '</autorizacion></autorizaciones></RespuestaAutorizacionComprobante></ns2:autorizacionComprobanteResponse></soap:Body></soap:Envelope>';
    }
}
