<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ConfiguracionClinica;
use App\Livewire\CrearCertificado;
use App\Livewire\CrearPedidoExamenes;
use App\Livewire\ListaPacientes;
use App\Models\CertificadoMedico;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PedidoExamen;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DocumentosMedicosTest extends TestCase
{
    use RefreshDatabase;

    private User $medicoUser;
    private Medico $medico;
    private Paciente $paciente;
    private ExpedienteConsulta $consulta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Storage::fake('private');

        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->medicoUser = User::factory()->create();
        $this->medicoUser->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $this->medicoUser->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id, 'colegiatura' => 'MSP-1234']);
        $this->paciente = Paciente::create(['nombres' => 'Ana María', 'apellidos' => 'Pérez Vera', 'cedula' => '0102030405', 'sexo' => 'femenino', 'fecha_nacimiento' => '1990-05-10']);
        $this->consulta = ExpedienteConsulta::create([
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $esp->id,
            'tipo_consulta' => 'ginecologica', 'fecha' => now(), 'motivo_consulta' => 'Dolor', 'diagnostico' => 'Infección de vías urinarias', 'codigo_cie10' => 'N39.0',
        ]);
        $this->actingAs($this->medicoUser);
    }

    public function test_medico_sube_su_firma_y_se_imprime_en_la_receta(): void
    {
        $this->get('/profile')->assertSeeVolt('profile.firma-medico-form');

        Volt::test('profile.firma-medico-form')
            ->set('firma', UploadedFile::fake()->image('firma.png', 400, 150))
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertDispatched('firma-guardada');

        $this->medico->refresh();
        Storage::disk('private')->assertExists($this->medico->firma_path);
        $this->assertNotNull($this->medico->firmaRuta());
        $this->get(route('firma.ver'))->assertOk();

        $receta = \App\Models\Receta::create(['paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'fecha' => today(), 'medicamentos' => [['medicamento' => 'Nitrofurantoína']]]);
        $html = view('pdf.receta', ['receta' => $receta->load('paciente', 'medico.especialidad', 'consulta')])->render();
        $this->assertStringContainsString($this->medico->firmaRuta(), $html);
        $this->assertStringContainsString('MSP-1234', $html);

        Volt::test('profile.firma-medico-form')->call('quitar');
        $this->assertNull($this->medico->fresh()->firma_path);
    }

    public function test_un_usuario_sin_perfil_medico_no_ve_ni_sube_firma(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin)->get('/profile')->assertDontSeeVolt('profile.firma-medico-form');
        $this->actingAs($admin)->get(route('firma.ver'))->assertNotFound();
    }

    public function test_certificado_de_reposo_con_verificacion_publica(): void
    {
        Livewire::withQueryParams(['consulta' => $this->consulta->id])
            ->test(CrearCertificado::class, ['paciente' => $this->paciente])
            ->assertSet('diagnostico', 'Infección de vías urinarias')
            ->assertSet('codigo_cie10', 'N39.0')
            ->set('tipo', 'reposo')
            ->set('dias_reposo', 3)
            // Fechas relativas a hoy: el reposo no puede empezar antes de la atención
            ->set('reposo_desde', today()->toDateString())
            ->assertSee(today()->addDays(2)->format('d/m/Y'))
            ->call('emitir')
            ->assertHasNoErrors();

        $cert = CertificadoMedico::sole();
        $this->assertSame(today()->addDays(2)->toDateString(), $cert->reposo_hasta->format('Y-m-d'));
        $this->assertSame($this->consulta->id, $cert->consulta_id);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{10}$/', $cert->codigo_verificacion);

        $pdf = $this->get(route('certificados.pdf', $cert));
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');

        auth()->logout();
        $this->get(route('certificados.verificar', strtolower($cert->codigo_verificacion)))
            ->assertOk()
            ->assertSee('Certificado auténtico')
            ->assertSee('A** M**** P**** V***')
            ->assertSee('*******405')
            ->assertSee('3 día(s)')
            ->assertDontSee('Infección')
            ->assertDontSee('0102030405');
        $this->get(route('certificados.verificar', 'NOEXISTE22'))->assertOk()->assertSee('Certificado no encontrado');
    }

    public function test_la_consulta_asociada_no_se_puede_cambiar_desde_el_navegador(): void
    {
        // Se toma solo de la URL al abrir la pantalla
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
        Livewire::test(CrearCertificado::class, ['paciente' => $this->paciente])->set('consultaId', 999);
    }

    public function test_validaciones_y_permisos_del_certificado(): void
    {
        Livewire::test(CrearCertificado::class, ['paciente' => $this->paciente])
            ->set('tipo', 'reposo')
            ->set('dias_reposo', 120)
            ->call('emitir')
            ->assertHasErrors(['dias_reposo' => 'max', 'diagnostico' => 'required_if']);

        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');
        $this->actingAs($secretaria)->get(route('certificados.crear', $this->paciente))->assertForbidden();
    }

    public function test_pedido_de_examenes(): void
    {
        Livewire::withQueryParams(['consulta' => $this->consulta->id])
            ->test(CrearPedidoExamenes::class, ['paciente' => $this->paciente])
            ->call('emitir')
            ->assertHasErrors('laboratorio')
            ->set('laboratorio', ['EMO (examen de orina)', 'Urocultivo', 'Examen inventado'])
            ->call('emitir')
            ->assertHasErrors('laboratorio.2')
            ->set('laboratorio', ['Urocultivo', 'EMO (examen de orina)'])
            ->set('imagen', ['Ecografía pélvica'])
            ->call('agregarIndicacion', 1)
            ->call('agregarIndicacion', 1)
            ->call('emitir')
            ->assertHasNoErrors();

        $pedido = PedidoExamen::sole();
        $this->assertSame(['EMO (examen de orina)', 'Urocultivo'], $pedido->laboratorio, 'Respeta el orden del catálogo');
        $this->assertSame('Recolectar la primera orina de la mañana.', $pedido->indicaciones);
        $this->assertSame('N39.0', $pedido->codigo_cie10);
        $this->get(route('pedidos.pdf', $pedido))->assertOk()->assertHeader('content-type', 'application/pdf');

        $html = view('pdf.pedido-examenes', ['p' => $pedido->load('paciente', 'medico.especialidad')])->render();
        $this->assertStringContainsString('Ecografía pélvica', $html);
    }

    public function test_catalogo_de_examenes_editable_y_paciente_con_documentos_no_se_elimina(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Livewire::actingAs($admin)->test(ConfiguracionClinica::class)
            ->set('examenesLaboratorio', "Ferritina\nVitamina D")
            ->call('guardar')
            ->assertHasNoErrors();

        Livewire::actingAs($this->medicoUser)->test(CrearPedidoExamenes::class, ['paciente' => $this->paciente])
            ->assertSee('Ferritina')
            ->assertDontSee('Biometría hemática')
            ->set('laboratorio', ['Vitamina D'])
            ->call('emitir')
            ->assertHasNoErrors();

        Livewire::actingAs($admin)->test(ListaPacientes::class)->call('eliminarPaciente', $this->paciente->id);
        $this->assertNotNull($this->paciente->fresh());
    }
}
