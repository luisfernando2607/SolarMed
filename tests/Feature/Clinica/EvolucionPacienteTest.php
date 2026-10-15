<?php

namespace Tests\Feature\Clinica;

use App\Models\ControlPrenatal;
use App\Models\Embarazo;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Preparacion;
use App\Models\Turno;
use App\Models\User;
use App\Services\EvolucionPacienteService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvolucionPacienteTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Paciente $ana;
    private Especialidad $esp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $this->esp->id]);
        $this->ana = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
    }

    private function consulta(string $fecha, array $ef, ?int $turnoId = null): ExpedienteConsulta
    {
        return ExpedienteConsulta::create(['paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->esp->id,
            'tipo_consulta' => 'general', 'fecha' => $fecha, 'motivo_consulta' => 'Control', 'diagnostico' => 'x', 'turno_id' => $turnoId, 'examen_fisico' => $ef]);
    }

    private function turno(string $fecha): Turno
    {
        static $n = 0;
        return Turno::create(['numero_turno' => ++$n, 'prefijo' => 'O', 'especialidad_id' => $this->esp->id, 'paciente_id' => $this->ana->id,
            'cedula' => '0102030405', 'estado' => 'completado', 'fecha' => $fecha, 'hora_registro' => $fecha . ' 08:00']);
    }

    public function test_presion_se_lee_solo_si_es_valida(): void
    {
        $this->assertSame([120, 80], EvolucionPacienteService::presion(' 120 / 80 '));
        $this->assertSame([null, null], EvolucionPacienteService::presion('80/120'));
        $this->assertSame([null, null], EvolucionPacienteService::presion('alta'));
        $this->assertSame([null, null], EvolucionPacienteService::presion(null));
    }

    public function test_une_fuentes_sin_duplicar_y_prefiere_enfermeria(): void
    {
        // Mismo turno: preparación y consulta → solo cuenta enfermería
        $t = $this->turno('2026-08-01');
        Preparacion::create(['turno_id' => $t->id, 'paciente_id' => $this->ana->id, 'fecha' => '2026-08-01 08:10',
            'presion_sistolica' => 130, 'presion_diastolica' => 85, 'peso' => 70, 'talla' => 1.60, 'saturacion' => 97]);
        $this->consulta('2026-08-01 09:00', ['pa' => '125/80', 'peso' => '69.5', 'fc' => '80'], $t->id);

        // Otro día solo consulta (con texto inválido en la PA) y dos consultas el mismo día: queda la última
        $this->consulta('2026-09-01 09:00', ['pa' => 'normal', 'peso' => '71']);
        $this->consulta('2026-10-01 08:00', ['pa' => '118/76', 'peso' => '72', 'talla' => '1.60']);
        $this->consulta('2026-10-01 11:00', ['pa' => '122/78', 'peso' => '72.4']);

        $m = app(EvolucionPacienteService::class)->medidas($this->ana);

        $this->assertSame([130.0, 122.0], array_column($m['sistolica'], 'valor'));
        $this->assertSame(['Enfermería', 'Consulta'], array_column($m['sistolica'], 'fuente'));
        $this->assertSame([70.0, 71.0, 72.4], array_column($m['peso'], 'valor'));
        $this->assertSame(27.3, $m['imc'][0]['valor']);
        $this->assertSame([], $m['fc'], 'la FC de la consulta del turno con preparación no se usa');
        $this->assertSame('01/08/2026', $m['saturacion'][0]['fecha']);
    }

    public function test_prenatal_ganancia_y_altura_uterina(): void
    {
        $e = Embarazo::create(['paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'fum' => '2026-03-01', 'fpp' => '2026-12-06', 'estado' => 'activo']);
        foreach ([[20, 60.0, 19], [24, 62.5, 24], [28, 64.0, 27]] as [$sem, $peso, $au]) {
            ControlPrenatal::create(['embarazo_id' => $e->id, 'paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id,
                'fecha' => now()->subWeeks(30 - $sem), 'semanas_gestacion' => $sem, 'dias_gestacion' => 0,
                'peso_materno' => $peso, 'presion_arterial' => '110/70', 'altura_uterina' => $au]);
        }

        $s = app(EvolucionPacienteService::class);
        $emb = $s->embarazo($this->ana);
        $this->assertSame([0.0, 2.5, 4.0], array_column($emb['ganancia'], 'valor'));
        $this->assertSame([20.0, 24.0, 28.0], array_column($emb['altura_uterina'], 'semana'));
        $this->assertSame(['Control prenatal'], array_values(array_unique(array_column($s->medidas($this->ana)['peso'], 'fuente'))));

        $e->update(['estado' => 'finalizado']);
        $this->assertNull($s->embarazo($this->ana));
    }

    public function test_pestana_visible_solo_con_historia_clinica(): void
    {
        $this->consulta('2026-10-01 08:00', ['pa' => '118/76']);

        $this->actingAs($this->medico->user)->get(route('pacientes.ver', $this->ana))->assertOk()
            ->assertSee('Evolución')->assertSee('evolucion-datos', false)->assertSee('Presión arterial');

        $enf = User::factory()->create(); $enf->assignRole('enfermeria');
        $this->actingAs($enf)->get(route('pacientes.ver', $this->ana))->assertOk()
            ->assertDontSee('evolucion-datos', false)->assertDontSee('Presión arterial');
    }
}
