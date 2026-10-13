<?php

namespace Tests\Feature\Clinica;

use App\Livewire\FormularioContrarreferencia;
use App\Livewire\FormularioReferencia;
use App\Livewire\ListaReferencias;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Referencia;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class ReferenciaTest extends TestCase
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
        $this->medicoUser = User::factory()->create(['cedula' => '0911223344']);
        $this->medicoUser->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $this->medicoUser->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);
        $this->paciente = Paciente::create(['nombres' => 'Ana María', 'apellidos' => 'Pérez Vera', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'fecha_nacimiento' => '1990-05-10', 'telefono' => '0991234567', 'provincia' => 'Guayas', 'canton' => 'Guayaquil', 'parroquia' => 'Tarqui']);
        $this->consulta = ExpedienteConsulta::create([
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $esp->id,
            'tipo_consulta' => 'ginecologica', 'fecha' => now(), 'motivo_consulta' => 'Sangrado uterino anormal',
            'anamnesis' => 'Sangrado de 3 meses de evolución', 'examen_fisico' => ['notas' => 'Útero aumentado de tamaño'],
            'diagnostico' => 'Leiomioma del útero', 'codigo_cie10' => 'D25.9', 'tipo_diagnostico' => 'definitivo',
            'requiere_derivacion' => true, 'derivacion_especialidad' => 'Ginecología oncológica',
        ]);
        $this->actingAs($this->medicoUser);
    }

    private function crearReferencia(array $extra = []): Referencia
    {
        return Referencia::create($extra + [
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'user_id' => $this->medicoUser->id,
            'tipo' => 'derivacion', 'motivo' => 9, 'institucion_destino' => 'IESS', 'establecimiento_destino' => 'Hospital Teodoro Maldonado Carbo',
            'especialidad' => 'Ginecología oncológica', 'resumen_cuadro_clinico' => 'Resumen', 'fecha' => now(),
            'diagnosticos' => [['descripcion' => 'Leiomioma del útero', 'cie10' => 'D25.9', 'tipo' => 'def']],
        ]);
    }

    public function test_prellena_desde_la_consulta_que_requiere_derivacion(): void
    {
        $c = Livewire::withQueryParams(['consulta' => $this->consulta->id])
            ->test(FormularioReferencia::class, ['paciente' => $this->paciente])
            ->assertSet('especialidad', 'Ginecología oncológica')
            ->assertSet('hallazgos', 'Útero aumentado de tamaño')
            ->assertSet('diagnosticos', [['descripcion' => 'Leiomioma del útero', 'cie10' => 'D25.9', 'tipo' => 'def']]);
        $this->assertStringContainsString('Enfermedad actual: Sangrado de 3 meses', $c->get('resumen_cuadro_clinico'));
        $this->assertStringContainsString('Motivo de consulta: Sangrado uterino anormal', $c->get('resumen_cuadro_clinico'));

        $c->set('motivo', 9)
            ->set('tipo', 'derivacion')
            ->set('institucion_destino', 'IESS')
            ->set('establecimiento_destino', 'Hospital Teodoro Maldonado Carbo')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('referencias', ['paciente' => $this->paciente->id]));

        $r = Referencia::sole();
        $this->assertSame($this->consulta->id, $r->consulta_id);
        $this->assertSame('enviada', $r->estado);
        $this->assertSame('saliente', $r->direccion);
        $this->assertSame('crear', RegistroAcceso::latest('id')->value('accion'));
    }

    public function test_valida_motivo_maximo_cuatro_diagnosticos_y_cie10(): void
    {
        $c = Livewire::test(FormularioReferencia::class, ['paciente' => $this->paciente])
            ->set('especialidad', 'Cardiología')->set('establecimiento_destino', 'Hospital X')
            ->set('resumen_cuadro_clinico', 'Resumen')
            ->set('diagnosticos', [['descripcion' => 'Algo', 'cie10' => 'XX', 'tipo' => 'pre']])
            ->call('guardar')
            ->assertHasErrors(['motivo', 'diagnosticos.0.cie10']);

        foreach (range(1, 6) as $i) {
            $c->call('agregarDiagnostico');
        }
        $this->assertCount(Referencia::MAX_DIAGNOSTICOS, $c->get('diagnosticos'));

        $c->set('motivo', 1)->set('diagnosticos', array_fill(0, 5, ['descripcion' => 'Dx', 'cie10' => 'I10', 'tipo' => 'pre']))
            ->call('guardar')->assertHasErrors('diagnosticos');
        $this->assertSame(0, Referencia::count());
    }

    public function test_registrar_contrarreferencia_recibida_y_la_referencia_queda_fija(): void
    {
        $r = $this->crearReferencia();

        Livewire::test(FormularioContrarreferencia::class, ['referencia' => $r])
            ->assertSet('contra_establecimiento', 'Hospital Teodoro Maldonado Carbo')
            ->set('contra_resumen', 'Paciente valorada, se realizó histerectomía')
            ->set('contra_tratamientos_realizados', 'Histerectomía total')
            ->set('contra_diagnosticos', [['descripcion' => 'Leiomioma', 'cie10' => 'D25.9', 'tipo' => 'def']])
            ->set('contra_tratamiento_recomendado', 'Control en 1 mes')
            ->set('contra_profesional', 'Dra. Externa')
            ->set('derivacion_justificada', 'si')
            ->set('documento', UploadedFile::fake()->create('respuesta.pdf', 100, 'application/pdf'))
            ->call('guardar')
            ->assertHasNoErrors();

        $r->refresh();
        $this->assertSame('contrarreferida', $r->estado);
        $this->assertTrue($r->derivacion_justificada);
        $this->assertNull($r->referencia_justificada);
        Storage::disk('private')->assertExists($r->documento_respuesta_ruta);
        $this->get(route('referencias.documento', $r))->assertOk();

        // La parte clínica ya no cambia ni se puede reeditar, y no se registra dos veces
        $this->assertThrows(fn () => $r->update(['resumen_cuadro_clinico' => 'otro']), LogicException::class);
        $this->assertThrows(fn () => $r->delete(), LogicException::class);
        $this->get(route('referencias.editar', $r))->assertForbidden();
        $this->get(route('referencias.contrarreferencia', $r))->assertForbidden();

        Livewire::test(ListaReferencias::class)->call('cerrar', $r->id);
        $this->assertSame('cerrada', $r->refresh()->estado);
    }

    public function test_referencia_entrante_y_contrarreferencia_emitida_por_la_clinica(): void
    {
        Livewire::test(FormularioReferencia::class, ['paciente' => $this->paciente])
            ->set('direccion', 'entrante')
            ->set('establecimiento_origen', 'Centro de Salud Tipo C Nueva Prosperina')
            ->set('profesional_origen', 'Dr. Origen')
            ->set('institucion_destino', 'Red Privada Complementaria')
            ->set('motivo', 7)
            ->set('especialidad', 'Ginecología')
            ->set('resumen_cuadro_clinico', 'Referida por masa pélvica')
            ->set('diagnosticos', [['descripcion' => 'Masa pélvica', 'cie10' => 'R19.0', 'tipo' => 'pre']])
            ->call('guardar')->assertHasNoErrors();

        $r = Referencia::sole();
        $this->assertSame('entrante', $r->direccion);

        Livewire::test(FormularioContrarreferencia::class, ['referencia' => $r])
            ->assertSet('contra_profesional', 'Dr. Carlos García')
            ->assertSet('contra_profesional_cedula', '0911223344')
            ->set('contra_resumen', 'Ecografía con mioma de 4 cm')
            ->set('contra_diagnosticos', [['descripcion' => 'Leiomioma', 'cie10' => 'D25.9', 'tipo' => 'def']])
            ->set('contra_tratamiento_recomendado', 'Control ecográfico en 6 meses')
            ->set('referencia_justificada', 'si')
            ->call('guardar')->assertHasNoErrors();

        $r->refresh();
        $this->assertSame($this->medico->id, $r->contra_medico_id);

        $html = view('pdf.referencia', ['r' => $r->load('paciente', 'medico.user', 'contraMedico.user'),
            'est' => app(\App\Services\MarcaService::class)->establecimiento()])->render();
        $this->assertStringContainsString('Centro de Salud Tipo C Nueva Prosperina', $html);
        $this->assertStringContainsString('CONTRARREFERENCIA', $html);
        $this->assertStringContainsString('Control ecográfico en 6 meses', $html);
        $this->assertStringContainsString('0911223344', $html);
        $this->actingAs($this->medicoUser)->get(route('referencias.pdf', $r))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_pdf_marca_el_motivo_y_lleva_confidencial(): void
    {
        $r = $this->crearReferencia();
        $html = view('pdf.referencia', ['r' => $r->load('paciente', 'medico.user'), 'est' => app(\App\Services\MarcaService::class)->establecimiento()])->render();

        $this->assertStringContainsString('CONFIDENCIAL', $html);
        $this->assertStringContainsString('SNS-MSP/HCU-form.053/2021', $html);
        $this->assertMatchesRegularExpression('/9\. Ausencia de la prestación en la cartera de servicios<\/td><td class="caja">X</', $html);
        $this->assertMatchesRegularExpression('/1\. Accesibilidad geográfica<\/td><td class="caja"><\/td>/', $html);
        $this->assertStringContainsString('Tarqui', $html);
        $this->assertStringContainsString('D25.9', $html);
        // Paciente de 36 años: condición de edad "A"
        $this->assertMatchesRegularExpression('/<td class="v">\d+<\/td>\s*<td class="v caja"><\/td><td class="v caja"><\/td><td class="v caja"><\/td><td class="v caja">X<\/td>/', $html);
    }

    public function test_lugar_de_residencia_se_edita_en_la_ficha(): void
    {
        Livewire::test(\App\Livewire\ListaPacientes::class)
            ->call('editar', $this->paciente->id)
            ->assertSet('canton', 'Guayaquil')
            ->set('provincia', 'Manabí')->set('canton', 'Portoviejo')->set('parroquia', 'Andrés de Vera')
            ->call('guardarPaciente')->assertHasNoErrors();

        $p = $this->paciente->refresh();
        $this->assertSame(['Manabí', 'Portoviejo', 'Andrés de Vera'], [$p->provincia, $p->canton, $p->parroquia]);
    }

    public function test_permisos(): void
    {
        $r = $this->crearReferencia();

        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('referencias'))->assertForbidden();
        $this->actingAs($sec)->get(route('referencias.pdf', $r))->assertForbidden();

        $auditor = User::factory()->create(); $auditor->assignRole('auditor');
        $this->actingAs($auditor)->get(route('referencias'))->assertOk()->assertSee($r->numero());
        $this->actingAs($auditor)->get(route('referencias.pdf', $r))->assertOk();
        $this->actingAs($auditor)->get(route('referencias.crear', $this->paciente))->assertForbidden();
        $this->actingAs($auditor)->get(route('referencias.contrarreferencia', $r))->assertForbidden();
        Livewire::actingAs($auditor)->test(ListaReferencias::class)->call('cerrar', $r->id)->assertForbidden();
    }

    public function test_lista_resalta_las_que_llevan_mas_de_30_dias_sin_respuesta(): void
    {
        $vieja = $this->crearReferencia(['fecha' => now()->subDays(45)]);
        $nueva = $this->crearReferencia(['especialidad' => 'Urología']);

        $this->assertTrue($vieja->sinRespuesta());
        $this->assertFalse($nueva->sinRespuesta());

        Livewire::test(ListaReferencias::class)
            ->assertSee('45 días sin respuesta')
            ->set('sinRespuesta', true)
            ->assertSee($vieja->numero())
            ->assertDontSee($nueva->numero());

        $this->get(route('pacientes.ver', $this->paciente))->assertOk()->assertSee($nueva->numero());
    }
}
