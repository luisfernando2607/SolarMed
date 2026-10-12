<?php

namespace Tests\Feature\Clinica;

use App\Livewire\CalidadHistorias;
use App\Livewire\CrearConsultaSinTurno;
use App\Livewire\EvaluarConsulta;
use App\Livewire\PlanesAccion;
use App\Models\AuditoriaHc;
use App\Models\Cie10Code;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PlanAccion;
use App\Models\User;
use App\Services\CalidadHistoriaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalidadHistoriaTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private User $auditor;
    private Paciente $paciente;
    private Especialidad $esp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $u = User::factory()->create(['cedula' => '0912345678']); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $this->esp->id, 'firma_path' => 'firmas/1.png']);
        $this->auditor = User::factory()->create(['name' => 'Auditora Externa']); $this->auditor->assignRole('auditor');
        $this->paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'fecha_nacimiento' => '1990-05-01',
            'antecedentes' => 'Asma', 'enfermedades_familiares' => 'Niega',
        ]);
        Cie10Code::create(['codigo' => 'J06.9', 'descripcion' => 'Infección aguda de las vías respiratorias superiores']);
    }

    private function consulta(array $cambios = []): ExpedienteConsulta
    {
        return ExpedienteConsulta::create($cambios + [
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->esp->id,
            'tipo_consulta' => 'general', 'tipo_atencion' => 'primera', 'fecha' => now(),
            'motivo_consulta' => 'Tos y fiebre', 'anamnesis' => 'Cuadro de 3 días de evolución con tos seca y alza térmica.',
            'revision_sistemas' => 'Respiratorio: tos. Resto sin alteraciones.',
            'examen_fisico' => ['pa' => '120/80', 'fc' => 80, 'fr' => 18, 'temp' => 38.2, 'peso' => 60, 'talla' => 1.6, 'notas' => 'Faringe eritematosa'],
            'diagnostico' => 'Rinofaringitis aguda', 'codigo_cie10' => 'J06.9', 'tipo_diagnostico' => 'definitivo',
            'tratamiento' => 'Paracetamol 500 mg cada 8 horas',
        ]);
    }

    public function test_consulta_completa_obtiene_cien(): void
    {
        $ev = app(CalidadHistoriaService::class)->evaluar($this->consulta());

        $this->assertSame(100, $ev['puntaje']);
        $this->assertTrue(collect($ev['criterios'])->every(fn ($c) => $c['ok']));
    }

    public function test_consulta_incompleta_marca_los_bloques_que_faltan(): void
    {
        $c = $this->consulta([
            'tipo_atencion' => null, 'anamnesis' => 'Tos', 'revision_sistemas' => null,
            'examen_fisico' => ['pa' => '120/80', 'notas' => null], 'codigo_cie10' => 'X99.99', 'tipo_diagnostico' => null,
        ]);
        $this->medico->update(['firma_path' => null]);

        $ev = app(CalidadHistoriaService::class)->evaluar($c->fresh());
        $faltan = collect($ev['criterios'])->reject(fn ($x) => $x['ok'])->keys()->all();

        $this->assertSame(['B', 'E', 'F', 'G', 'H', 'I', 'K'], $faltan);
        // Pesos: 12 criterios, G y T valen 0,5 → total 11; cumplen A, C, D, J (4) + T (0,5) = 4,5 / 11
        $this->assertSame(41, $ev['puntaje']);
    }

    public function test_sugiere_primera_o_subsecuente_y_exige_tipo_de_diagnostico(): void
    {
        $this->actingAs($this->medico->user);
        $form = Livewire::test(CrearConsultaSinTurno::class)
            ->call('seleccionarPaciente', $this->paciente->id)
            ->call('iniciarConsulta')
            ->assertSet('tipo_atencion', 'primera');

        $form->set('motivo_consulta', 'Control')->set('diagnostico', 'Sano')->set('tipo_diagnostico', '')
            ->call('guardar')->assertHasErrors('tipo_diagnostico');
        $form->set('tipo_diagnostico', 'definitivo')->call('guardar')->assertHasNoErrors();
        $this->assertSame('definitivo', ExpedienteConsulta::sole()->tipo_diagnostico);

        Livewire::test(CrearConsultaSinTurno::class)
            ->call('seleccionarPaciente', $this->paciente->id)
            ->call('iniciarConsulta')
            ->assertSet('tipo_atencion', 'subsecuente');
    }

    public function test_permisos_del_area_de_calidad(): void
    {
        $c = $this->consulta();

        $this->actingAs($this->medico->user)->get(route('auditoria.calidad'))->assertForbidden();
        $this->actingAs($this->auditor)->get(route('auditoria.calidad'))->assertOk()->assertSee('Calidad de la historia clínica');
        $this->actingAs($this->auditor)->get(route('auditoria.evaluar', $c))->assertOk()->assertSee('Rinofaringitis aguda');
        $this->actingAs($this->auditor)->get(route('auditoria.planes'))->assertOk();
        // El auditor lee pero no corrige la historia clínica
        $this->actingAs($this->auditor)->get(route('consultas.editar', $c))->assertForbidden();

        Livewire::actingAs($this->medico->user)->test(EvaluarConsulta::class, ['consulta' => $c])
            ->call('guardar', true)->assertForbidden();
    }

    public function test_muestra_aleatoria_y_tablero(): void
    {
        foreach (range(1, 5) as $i) {
            $this->consulta(['fecha' => now()->subDays($i)]);
        }

        Livewire::actingAs($this->auditor)->test(CalidadHistorias::class)
            ->assertSee('100%')
            ->set('tamanoMuestra', 3)
            ->call('generarMuestra')
            ->assertCount('muestra', 3)
            ->assertSee('Muestra aleatoria para auditoría');
    }

    public function test_evaluacion_cerrada_es_inmutable(): void
    {
        $c = $this->consulta();

        Livewire::actingAs($this->auditor)->test(EvaluarConsulta::class, ['consulta' => $c])
            ->set('resultado.F.ok', false)
            ->set('resultado.F.obs', 'No registra frecuencia respiratoria')
            ->set('observacion', 'Revisar registro de constantes vitales')
            ->call('guardar', true)
            ->assertSet('hallazgo', 'Constantes vitales y antropometría: No registra frecuencia respiratoria');

        $a = AuditoriaHc::sole();
        $this->assertTrue($a->cerrada());
        $this->assertSame(91, $a->puntaje);
        $this->assertSame(100, $a->puntaje_automatico);

        $this->assertFalse($a->update(['puntaje' => 100]));
        $this->assertSame(91, $a->fresh()->puntaje);
        $this->assertFalse($a->delete());
        $this->assertDatabaseHas('auditorias_hc', ['id' => $a->id]);
        $this->assertDatabaseHas('registro_accesos', ['accion' => 'auditar', 'recurso' => 'consulta', 'recurso_id' => $c->id]);

        $this->actingAs($this->auditor)->get(route('auditoria.informe', $a))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_ciclo_del_plan_de_accion(): void
    {
        $c = $this->consulta();
        $vista = Livewire::actingAs($this->auditor)->test(EvaluarConsulta::class, ['consulta' => $c])->call('guardar', true);
        $a = AuditoriaHc::sole();

        $vista->set('hallazgo', 'Falta frecuencia respiratoria')
            ->set('accion', 'Capacitación en registro de constantes vitales')
            ->set('responsable', (string) $this->medico->user_id)
            ->set('fechaLimite', today()->addDays(15)->toDateString())
            ->call('agregarPlan', $a->id)
            ->assertHasNoErrors();

        $plan = PlanAccion::sole();
        $this->assertSame('pendiente', $plan->estado);
        $this->assertSame($this->medico->id, $plan->medico_id);
        $this->assertFalse($plan->vencido());

        $planes = Livewire::actingAs($this->auditor)->test(PlanesAccion::class)
            ->call('editar', $plan->id)
            ->set('estado', 'cumplido')
            ->call('guardar')
            ->assertHasErrors('evidencia');

        $planes->set('evidencia', 'Acta de capacitación del 12/10')->call('guardar')->assertHasNoErrors();
        $plan->refresh();
        $this->assertSame('cumplido', $plan->estado);
        $this->assertNotNull($plan->cerrado_at);

        // Cumplido queda cerrado: ya no se puede editar
        Livewire::actingAs($this->auditor)->test(PlanesAccion::class)
            ->call('editar', $plan->id)->assertSet('editandoId', null);

        $plan->forceFill(['estado' => 'pendiente', 'fecha_limite' => today()->subDay()])->save();
        $this->assertTrue($plan->fresh()->vencido());
    }
}
