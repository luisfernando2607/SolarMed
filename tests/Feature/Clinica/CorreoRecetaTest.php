<?php

namespace Tests\Feature\Clinica;

use App\Jobs\EnviarComprobantePorCorreo;
use App\Livewire\CrearReceta;
use App\Livewire\VerFactura;
use App\Mail\ComprobanteElectronicoMail;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Receta;
use App\Models\RegistroAcceso;
use App\Models\ServicioTarifario;
use App\Models\Turno;
use App\Models\User;
use App\Services\ComprobanteSriService;
use App\Services\FacturaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CorreoRecetaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $medicoUser;
    private Medico $medico;
    private Paciente $paciente;
    private Factura $factura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Storage::fake('private');

        $especialidad = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->medicoUser = User::factory()->create();
        $this->medicoUser->assignRole('medico');
        $this->medico = Medico::create([
            'user_id' => $this->medicoUser->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $especialidad->id,
            'ruc' => '0912345678001', 'razon_social' => 'GARCIA CARLOS', 'direccion_matriz' => 'Guayaquil', 'colegiatura' => 'MSP-1234',
        ]);
        $this->paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'email' => 'ana@example.com', 'alergias' => 'Penicilina',
        ]);
        $servicio = ServicioTarifario::create(['especialidad_id' => $especialidad->id, 'nombre' => 'Consulta', 'precio' => 30, 'activo' => true]);

        $this->factura = app(FacturaService::class)->crear([
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $especialidad->id,
            'user_id' => $this->admin->id, 'fecha' => now(), 'forma_pago' => 'efectivo',
        ], [['servicio_id' => $servicio->id, 'cantidad' => 1]]);
    }

    private function autorizarFactura(): void
    {
        $this->factura->update(['estado_sri' => 'enviado', 'clave_acceso' => '2110201101179214673900110020010000000011234567813']);
        Http::fake(['*' => Http::response(
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . '<ns2:autorizacionComprobanteResponse xmlns:ns2="http://ec.gob.sri.ws.autorizacion"><RespuestaAutorizacionComprobante>'
            . '<autorizaciones><autorizacion><estado>AUTORIZADO</estado><numeroAutorizacion>2110201101179214673900110020010000000011234567813</numeroAutorizacion>'
            . '<fechaAutorizacion>2026-10-16T10:00:00-05:00</fechaAutorizacion><ambiente>PRUEBAS</ambiente><comprobante><![CDATA[<factura/>]]></comprobante>'
            . '</autorizacion></autorizaciones></RespuestaAutorizacionComprobante></ns2:autorizacionComprobanteResponse></soap:Body></soap:Envelope>'
        )]);
        app(ComprobanteSriService::class)->autorizar($this->factura);
    }

    public function test_al_autorizarse_se_envia_al_correo_del_paciente_con_pdf_y_xml(): void
    {
        Mail::fake();
        $this->autorizarFactura();

        Mail::assertSent(ComprobanteElectronicoMail::class, function (ComprobanteElectronicoMail $mail) {
            return $mail->hasTo('ana@example.com')
                && count($mail->attachments()) === 2
                && str_contains($mail->envelope()->subject, $this->factura->numero_factura);
        });
        $this->factura->refresh();
        $this->assertSame('ana@example.com', $this->factura->email_enviado_a);
        $this->assertNotNull($this->factura->email_enviado_at);
        $this->assertSame('enviar', RegistroAcceso::where('accion', 'enviar')->sole()->accion);
    }

    public function test_el_correo_se_renderiza(): void
    {
        $this->autorizarFactura();
        $html = (new ComprobanteElectronicoMail($this->factura->fresh()))->render();

        $this->assertStringContainsString('Ana Pérez', $html);
        $this->assertStringContainsString('2110201101179214673900110020010000000011234567813', $html);
    }

    public function test_sin_correo_no_se_envia_y_se_puede_enviar_manualmente(): void
    {
        Mail::fake();
        $this->paciente->update(['email' => null]);
        $this->autorizarFactura();
        Mail::assertNothingSent();

        Livewire::actingAs($this->admin)->test(VerFactura::class, ['factura' => $this->factura->fresh()])
            ->set('correo_envio', 'no-es-correo')
            ->call('enviarCorreo')
            ->assertHasErrors(['correo_envio' => 'email'])
            ->set('correo_envio', 'ana.otro@example.com')
            ->call('enviarCorreo')
            ->assertHasNoErrors();

        Mail::assertSent(ComprobanteElectronicoMail::class, fn ($m) => $m->hasTo('ana.otro@example.com'));
    }

    public function test_no_se_envia_un_comprobante_no_autorizado(): void
    {
        Mail::fake();
        (new EnviarComprobantePorCorreo($this->factura))->handle();
        Mail::assertNothingSent();
    }

    public function test_receta_se_vincula_a_la_consulta_e_imprime_en_pdf(): void
    {
        $turno = Turno::create([
            'numero_turno' => 1, 'prefijo' => 'O', 'especialidad_id' => $this->medico->especialidad_id, 'paciente_id' => $this->paciente->id,
            'nombre_temporal' => 'Ana Pérez', 'cedula' => '0102030405', 'estado' => 'en_atencion', 'fecha' => today(), 'hora_registro' => now(),
        ]);
        $consulta = ExpedienteConsulta::create([
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->medico->especialidad_id,
            'turno_id' => $turno->id, 'tipo_consulta' => 'ginecologica', 'fecha' => now(), 'motivo_consulta' => 'Control', 'diagnostico' => 'Vaginosis',
            'codigo_cie10' => 'N76.0',
        ]);
        \App\Models\Cie10Code::create(['codigo' => 'N76.0', 'descripcion' => 'Vaginitis aguda', 'categoria' => 'N76', 'capitulo' => 'XIV']);

        Livewire::actingAs($this->medicoUser)->test(CrearReceta::class, ['turno' => $turno])
            ->assertSet('diagnostico', 'Vaginosis')
            ->assertSet('codigo_cie10', 'N76.0')
            ->assertSet('alergias', 'Penicilina')
            ->set('ciudad', 'Guayaquil')
            ->set('items.0.dci', 'Metronidazol')
            ->set('items.0.concentracion', '500 mg')
            ->set('items.0.forma_farmaceutica', 'Tableta')
            ->set('items.0.cantidad', 14)
            ->set('items.0.dosis', '1 tableta')
            ->set('items.0.frecuencia', 'Cada 12 horas')
            ->set('items.0.duracion', '7 días')
            ->call('guardar')
            ->assertHasNoErrors();

        $receta = Receta::sole();
        $this->assertSame($consulta->id, $receta->consulta_id);

        $this->actingAs($this->medicoUser)->get(route('recetas.pdf', $receta))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');
        $this->actingAs($secretaria)->get(route('recetas.pdf', $receta))->assertForbidden();
    }
}
