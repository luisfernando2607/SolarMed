<?php

namespace Tests\Feature\Clinica;

use App\Livewire\VerFactura;
use App\Models\Especialidad;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\NotaCredito;
use App\Models\Paciente;
use App\Models\ServicioTarifario;
use App\Models\User;
use App\Services\FacturaService;
use App\Services\NotaCreditoService;
use App\Services\SriSignService;
use App\Services\SriXmlService;
use Database\Seeders\RolesSeeder;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class NotaCreditoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Medico $medico;
    private Factura $factura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Storage::fake('private');

        $especialidad = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        // Certificado de prueba del médico
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $cert = openssl_csr_sign(openssl_csr_new(['commonName' => 'Prueba', 'countryName' => 'EC'], $key), null, $key, 30);
        openssl_pkcs12_export($cert, $p12, $key, 'clave');
        Storage::disk('private')->put('certificados/medico.p12', $p12);

        $medicoUser = User::factory()->create();
        $this->medico = Medico::create([
            'user_id' => $medicoUser->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $especialidad->id,
            'ruc' => '0912345678001', 'razon_social' => 'GARCIA CARLOS', 'direccion_matriz' => 'Guayaquil',
            'p12_path' => 'certificados/medico.p12', 'p12_password_encrypted' => SriSignService::encryptPassword('clave'),
        ]);
        $paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        $servicio = ServicioTarifario::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Consulta', 'precio' => 30, 'activo' => true]);

        $this->factura = app(FacturaService::class)->crear([
            'paciente_id' => $paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $especialidad->id,
            'user_id' => $this->admin->id, 'fecha' => now()->subDay(), 'forma_pago' => 'efectivo', 'descuento' => 5,
        ], [['servicio_id' => $servicio->id, 'cantidad' => 2]]);
        $this->factura->update(['estado_sri' => 'autorizado', 'numero_autorizacion' => str_repeat('1', 49)]);
    }

    public function test_solo_se_emite_sobre_factura_autorizada_y_una_vez(): void
    {
        $svc = app(NotaCreditoService::class);

        $this->factura->update(['estado_sri' => 'enviado']);
        try {
            $svc->crear($this->factura, 'Error en el cobro', $this->admin->id);
            $this->fail('Debió rechazar una factura no autorizada');
        } catch (ValidationException) {
        }

        $this->factura->update(['estado_sri' => 'autorizado']);
        $nota = $svc->crear($this->factura, 'Error en el cobro', $this->admin->id);
        $this->assertSame('001-001-000000001', $nota->numero);
        $this->assertSame('0912345678001', $nota->ruc_emisor);
        $this->assertEquals(55.00, (float) $nota->valor_modificacion);

        $this->expectException(ValidationException::class);
        $svc->crear($this->factura->fresh(), 'Otra vez', $this->admin->id);
    }

    public function test_xml_de_la_nota_de_credito(): void
    {
        $nota = app(NotaCreditoService::class)->crear($this->factura, 'Servicio no prestado', $this->admin->id);
        $r = app(SriXmlService::class)->generarNotaCredito($nota->load('factura.paciente', 'factura.items', 'medico'));

        $dom = new DOMDocument();
        $dom->loadXML($r['xml']);
        $xp = new DOMXPath($dom);

        $this->assertSame('notaCredito', $dom->documentElement->nodeName);
        $this->assertSame('04', $xp->evaluate('string(//infoTributaria/codDoc)'));
        $this->assertSame('0912345678001', $xp->evaluate('string(//infoTributaria/ruc)'));
        $this->assertSame('01', $xp->evaluate('string(//codDocModificado)'));
        $this->assertSame($this->factura->numero_factura, $xp->evaluate('string(//numDocModificado)'));
        $this->assertSame($this->factura->fecha->format('d/m/Y'), $xp->evaluate('string(//fechaEmisionDocSustento)'));
        $this->assertSame('55.00', $xp->evaluate('string(//valorModificacion)'));
        $this->assertSame('55.00', $xp->evaluate('string(//infoNotaCredito/totalSinImpuestos)'));
        $this->assertSame('Servicio no prestado', $xp->evaluate('string(//infoNotaCredito/motivo)'));
        $this->assertEquals(55.0, $xp->evaluate('sum(//detalle/precioTotalSinImpuesto)'));
        $this->assertSame('04', substr($r['clave_acceso'], 8, 2));
        $this->assertTrue(app(SriXmlService::class)->claveAccesoValida($r['clave_acceso']));
    }

    public function test_flujo_completo_anula_la_factura(): void
    {
        Http::fakeSequence()
            ->push($this->soap('recepcion', '<RespuestaRecepcionComprobante><estado>RECIBIDA</estado><comprobantes/></RespuestaRecepcionComprobante>'))
            ->push($this->soap('autorizacion', '<RespuestaAutorizacionComprobante><autorizaciones><autorizacion><estado>AUTORIZADO</estado>'
                . '<numeroAutorizacion>' . str_repeat('2', 49) . '</numeroAutorizacion><fechaAutorizacion>2026-10-14T10:00:00-05:00</fechaAutorizacion>'
                . '<ambiente>PRUEBAS</ambiente><comprobante><![CDATA[<notaCredito/>]]></comprobante></autorizacion></autorizaciones></RespuestaAutorizacionComprobante>'));

        Livewire::actingAs($this->admin)->test(VerFactura::class, ['factura' => $this->factura])
            ->call('emitirNotaCredito')
            ->assertHasErrors(['motivo_nota_credito' => 'required'])
            ->set('motivo_nota_credito', 'Error en el cobro')
            ->call('emitirNotaCredito')
            ->assertHasNoErrors()
            ->call('autorizarNotaCredito');

        $nota = NotaCredito::sole();
        $this->assertSame('autorizado', $nota->estado_sri);
        $this->assertSame(str_repeat('2', 49), $nota->numero_autorizacion);
        Storage::disk('private')->assertExists($nota->xml_firmado_path);
        Storage::disk('private')->assertExists($nota->xml_autorizado_path);
        $this->assertStringContainsString('ds:Signature', Storage::disk('private')->get($nota->xml_firmado_path));
        $this->assertSame('anulada', $this->factura->fresh()->estado);

        $this->actingAs($this->admin)->get(route('notas-credito.pdf', $nota))->assertOk();
        $this->actingAs($this->admin)->get(route('notas-credito.xml', $nota))->assertOk();
    }

    public function test_secretaria_no_puede_emitir_nota_de_credito(): void
    {
        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');

        Livewire::actingAs($secretaria)->test(VerFactura::class, ['factura' => $this->factura])
            ->set('motivo_nota_credito', 'Error en el cobro')
            ->call('emitirNotaCredito')
            ->assertForbidden();

        $this->assertSame(0, NotaCredito::count());
    }

    private function soap(string $servicio, string $cuerpo): string
    {
        $op = $servicio === 'recepcion' ? 'validarComprobanteResponse' : 'autorizacionComprobanteResponse';
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . "<ns2:{$op} xmlns:ns2=\"http://ec.gob.sri.ws.{$servicio}\">{$cuerpo}</ns2:{$op}>"
            . '</soap:Body></soap:Envelope>';
    }
}
