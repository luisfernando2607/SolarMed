<?php

namespace Tests\Feature\Clinica;

use App\Livewire\CrearConsentimiento;
use App\Livewire\VerConsentimiento;
use App\Models\Consentimiento;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PlantillaConsentimiento;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class ConsentimientoTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Paciente $ana;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $this->seed(RolesSeeder::class);
        $gine = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(['cedula' => '0912345678']); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $gine->id]);
        $this->ana = Paciente::create(['nombres' => 'Ana Lucía', 'apellidos' => 'Pérez Mora', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'fecha_nacimiento' => now()->subYears(30), 'alergias' => 'Penicilina']);
    }

    private function crear(array $extra = []): Consentimiento
    {
        return Consentimiento::create($extra + [
            'paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'user_id' => $this->medico->user_id,
            'servicio' => 'Ginecología', 'tipo_atencion' => 'ambulatorio', 'diagnostico' => 'Anticoncepción', 'codigo_cie10' => 'Z30.1',
            'procedimiento' => 'Colocación de DIU', 'en_que_consiste' => 'Texto del profesional', 'fecha_informacion' => today(),
            'estado' => 'pendiente_firma',
        ]);
    }

    public function test_crear_desde_plantilla_con_riesgos_del_paciente(): void
    {
        $plantilla = PlantillaConsentimiento::create([
            'procedimiento' => 'Colocación de DIU', 'en_que_consiste' => 'Explicación A', 'como_se_realiza' => 'Explicación B',
            'duracion_estimada' => '20 minutos', 'beneficios' => 'B', 'riesgos_frecuentes' => 'RF', 'riesgos_graves' => 'RG',
            'alternativas' => 'Alt', 'manejo_posterior' => 'MP', 'consecuencias_no_realizar' => 'C', 'activo' => true,
        ]);

        Livewire::actingAs($this->medico->user)->test(CrearConsentimiento::class, ['paciente' => $this->ana])
            ->assertSet('riesgos_especificos', 'Alergias: Penicilina')
            ->set('plantillaId', (string) $plantilla->id)
            ->assertSet('procedimiento', 'Colocación de DIU')
            ->assertSet('duracion_estimada', '20 minutos')
            ->call('guardar')->assertHasErrors('diagnostico')
            ->set('diagnostico', 'Anticoncepción')
            ->call('guardar')->assertHasNoErrors()->assertRedirect();

        $c = Consentimiento::sole();
        $this->assertSame('pendiente_firma', $c->estado);
        $this->assertSame('Explicación B', $c->como_se_realiza);
        $this->assertSame('crear', RegistroAcceso::where('recurso', 'consentimiento')->value('accion'));
    }

    public function test_guardar_como_plantilla(): void
    {
        Livewire::actingAs($this->medico->user)->test(CrearConsentimiento::class, ['paciente' => $this->ana])
            ->set('procedimiento', 'Biopsia de endometrio')->set('en_que_consiste', 'Se toma una muestra')
            ->call('guardarComoPlantilla')->assertHasNoErrors();

        $this->assertSame('Biopsia de endometrio', PlantillaConsentimiento::sole()->procedimiento);
    }

    public function test_aceptado_no_se_puede_modificar_ni_eliminar(): void
    {
        $c = $this->crear();
        Livewire::actingAs($this->medico->user)->test(VerConsentimiento::class, ['consentimiento' => $c])
            ->call('aceptar')->assertHasNoErrors();

        $c->refresh();
        $this->assertSame('aceptado', $c->estado);
        $this->assertNotNull($c->fecha_aceptacion);
        $this->assertThrows(fn () => $c->update(['procedimiento' => 'Otro']), LogicException::class);
        $this->assertThrows(fn () => $c->delete(), LogicException::class);
        $this->assertThrows(fn () => $c->update(['estado' => 'negado']), LogicException::class);

        $this->actingAs($this->medico->user)->get(route('consentimientos.editar', $c))->assertForbidden();
        Livewire::actingAs($this->medico->user)->test(VerConsentimiento::class, ['consentimiento' => $c])
            ->call('aceptar')->assertHasErrors('accion');
    }

    public function test_negativa_sin_firma_del_paciente_exige_testigo(): void
    {
        $c = $this->crear();
        Livewire::actingAs($this->medico->user)->test(VerConsentimiento::class, ['consentimiento' => $c])
            ->set('negativaSinFirma', true)
            ->call('negar')->assertHasErrors(['testigo_nombre', 'testigo_cedula'])
            ->set('testigo_nombre', 'Luis Mora')->set('testigo_cedula', '0911111111')
            ->call('negar')->assertHasNoErrors();

        $c->refresh();
        $this->assertSame('negado', $c->estado);
        $this->assertTrue($c->negativa_sin_firma);
        $this->assertSame('Luis Mora', $c->testigo_nombre);
    }

    public function test_revocatoria_solo_de_un_consentimiento_aceptado(): void
    {
        $c = $this->crear();
        $componente = Livewire::actingAs($this->medico->user)->test(VerConsentimiento::class, ['consentimiento' => $c]);
        $componente->call('revocar')->assertHasErrors('accion');

        $componente->call('aceptar')->set('motivo_revocatoria', 'Cambió de opinión')->call('revocar')->assertHasNoErrors();

        $c->refresh();
        $this->assertSame('revocado', $c->estado);
        $this->assertNotNull($c->fecha_revocatoria);
        $this->assertSame('Cambió de opinión', $c->motivo_revocatoria);
    }

    public function test_menor_de_edad_requiere_representante(): void
    {
        $nina = Paciente::create(['nombres' => 'Sofía', 'apellidos' => 'Ríos', 'cedula' => '0922222222', 'sexo' => 'femenino', 'fecha_nacimiento' => now()->subYears(15)]);
        $c = $this->crear(['paciente_id' => $nina->id]);

        Livewire::actingAs($this->medico->user)->test(VerConsentimiento::class, ['consentimiento' => $c])
            ->assertSet('firmaRepresentante', true)
            ->set('firmaRepresentante', false)
            ->call('aceptar')->assertHasErrors(['representante_nombre', 'representante_cedula', 'representante_parentesco'])
            ->set('representante_nombre', 'Carmen Ríos')->set('representante_cedula', '0933333333')->set('representante_parentesco', 'Madre')
            ->call('aceptar')->assertHasNoErrors();

        $this->assertSame('Madre', $c->refresh()->representante_parentesco);
    }

    public function test_adjuntar_formulario_firmado_valida_el_tipo(): void
    {
        $c = $this->crear();
        $componente = Livewire::actingAs($this->medico->user)->test(VerConsentimiento::class, ['consentimiento' => $c]);

        $componente->set('documento', UploadedFile::fake()->create('firmado.pdf', 100, 'application/pdf'))
            ->call('subirDocumento')->assertHasErrors('documento');

        $componente->call('aceptar')
            ->set('documento', UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'))
            ->call('subirDocumento')->assertHasErrors('documento')
            ->set('documento', UploadedFile::fake()->image('firmado.png'))
            ->call('subirDocumento')->assertHasNoErrors();

        $c->refresh();
        Storage::disk('private')->assertExists($c->documento_firmado_ruta);
        $this->actingAs($this->medico->user)->get(route('consentimientos.documento', $c))->assertOk();
    }

    public function test_pdf_tiene_confidencial_y_los_textos_legales_del_formulario(): void
    {
        $c = $this->crear();
        $html = view('pdf.consentimiento', ['c' => $c->load('paciente', 'medico.user'), 'grafico' => null])->render();

        $this->assertStringContainsString('CONFIDENCIAL', $html);
        $this->assertStringContainsString(e(Consentimiento::TEXTO_DECLARACION), $html);
        $this->assertStringContainsString(e(Consentimiento::TEXTO_NEGATIVA), $html);
        $this->assertStringContainsString(e(Consentimiento::TEXTO_REVOCATORIA), $html);
        $this->assertStringContainsString('SNS-MSP / HCU-form.024', $html);
        $this->assertStringContainsString('Pérez', $html);

        $this->actingAs($this->medico->user)->get(route('consentimientos.pdf', $c))->assertOk();
    }

    public function test_permisos(): void
    {
        $c = $this->crear();

        foreach (['enfermeria', 'secretaria'] as $rol) {
            $u = User::factory()->create(); $u->assignRole($rol);
            $this->actingAs($u)->get(route('consentimientos.ver', $c))->assertForbidden();
            $this->actingAs($u)->get(route('consentimientos.pdf', $c))->assertForbidden();
            $this->actingAs($u)->get(route('consentimientos.crear', $this->ana))->assertForbidden();
        }

        $auditor = User::factory()->create(); $auditor->assignRole('auditor');
        $this->actingAs($auditor)->get(route('consentimientos.ver', $c))->assertOk()->assertDontSee('Registrar aceptación');
        $this->actingAs($auditor)->get(route('consentimientos.pdf', $c))->assertOk();
        $this->actingAs($auditor)->get(route('consentimientos.crear', $this->ana))->assertForbidden();
        $this->actingAs($auditor)->get(route('consentimientos.plantillas'))->assertForbidden();
        Livewire::actingAs($auditor)->test(VerConsentimiento::class, ['consentimiento' => $c])->call('aceptar')->assertForbidden();

        // Otro médico no corrige un borrador ajeno
        $u2 = User::factory()->create(); $u2->assignRole('medico');
        Medico::create(['user_id' => $u2->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $this->medico->especialidad_id]);
        $this->actingAs($u2)->get(route('consentimientos.editar', $c))->assertForbidden();
    }

    public function test_ficha_lista_el_consentimiento(): void
    {
        $this->crear();
        $this->actingAs($this->medico->user)->get(route('pacientes.ver', $this->ana))
            ->assertOk()->assertSee('Consentimiento informado')->assertSee('Colocación de DIU')->assertSee('Pendiente de firma');
    }
}
