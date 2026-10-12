<?php

namespace Tests\Feature\Clinica;

use App\Livewire\CrearConsulta;
use App\Livewire\InicioPersonal;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InicioPersonalTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Medico $otro;
    private Paciente $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $gine = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $general = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $u = User::factory()->create(['name' => 'Dra. María']); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $gine->id]);
        $u2 = User::factory()->create(); $u2->assignRole('medico');
        $this->otro = Medico::create(['user_id' => $u2->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $general->id]);
        $this->ana = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
        $beto = Paciente::create(['nombres' => 'Beto', 'apellidos' => 'Ríos', 'cedula' => '0911111111', 'sexo' => 'masculino']);

        Cita::create(['paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $gine->id, 'tipo' => 'Control prenatal', 'fecha_hora' => today()->setTime(10, 0), 'estado' => 'confirmada']);
        Cita::create(['paciente_id' => $beto->id, 'medico_id' => $this->otro->id, 'especialidad_id' => $general->id, 'tipo' => 'Consulta general', 'fecha_hora' => today()->setTime(11, 0), 'estado' => 'pendiente']);
        Turno::create(['numero_turno' => 1, 'prefijo' => 'O', 'especialidad_id' => $gine->id, 'paciente_id' => $this->ana->id, 'nombre_temporal' => 'Ana Pérez', 'cedula' => '0102030405', 'motivo' => 'Control prenatal', 'estado' => 'esperando', 'fecha' => today(), 'hora_registro' => now()->subMinutes(12)]);
        Turno::create(['numero_turno' => 1, 'prefijo' => 'G', 'especialidad_id' => $general->id, 'paciente_id' => $beto->id, 'nombre_temporal' => 'Beto Ríos', 'cedula' => '0911111111', 'motivo' => 'Fiebre', 'estado' => 'esperando', 'fecha' => today(), 'hora_registro' => now()]);
    }

    public function test_medico_ve_solo_su_agenda_y_su_especialidad_en_espera(): void
    {
        $this->actingAs($this->medico->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mi agenda de hoy')
            ->assertSee('Ana Pérez')
            ->assertSee('O1')
            ->assertDontSee('Beto Ríos')
            ->assertSee('Mis consultas del mes');
    }

    public function test_secretaria_ve_toda_la_agenda_y_lo_cobrado(): void
    {
        $sec = User::factory()->create(); $sec->assignRole('secretaria');

        $this->actingAs($sec)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Agenda de hoy')
            ->assertSee('Ana Pérez')
            ->assertSee('Beto Ríos')
            ->assertSee('Cobrado hoy')
            ->assertSee('Registrar turno')
            ->assertDontSee('Embarazos que requieren atención');
    }

    public function test_admin_es_redirigido_a_su_dashboard(): void
    {
        $admin = User::factory()->create(); $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_el_aviso_sobrevive_a_la_redireccion(): void
    {
        $turno = Turno::where('prefijo', 'O')->first();
        Livewire::actingAs($this->medico->user)->test(CrearConsulta::class, ['turno' => $turno])
            ->set('motivo_consulta', 'Control')->set('diagnostico', 'Embarazo normal')
            ->call('guardar')
            ->assertRedirect(route('turno.atender', $turno->id));

        $this->assertSame(['message' => 'Consulta registrada exitosamente.', 'type' => 'success'], session('notify'));
        $this->actingAs($this->medico->user)->get(route('turno.atender', $turno->id))->assertSee('Consulta registrada exitosamente.');
    }
}
