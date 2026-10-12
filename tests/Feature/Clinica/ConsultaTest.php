<?php

namespace Tests\Feature\Clinica;

use App\Livewire\AtenderTurno;
use App\Livewire\CrearConsulta;
use App\Livewire\CrearConsultaSinTurno;
use App\Livewire\EditarConsulta;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConsultaTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Medico $medico;
    private Paciente $paciente;
    private Turno $turno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $especialidad = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->user = User::factory()->create();
        $this->user->assignRole('medico');
        $this->medico = Medico::create([
            'user_id' => $this->user->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $especialidad->id,
        ]);
        $this->paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
        ]);
        $this->turno = Turno::create([
            'numero_turno' => 1, 'prefijo' => 'O', 'especialidad_id' => $especialidad->id, 'paciente_id' => $this->paciente->id,
            'nombre_temporal' => 'Ana Pérez', 'cedula' => '0102030405', 'estado' => 'en_atencion', 'fecha' => today(), 'hora_registro' => now(),
        ]);

        $this->actingAs($this->user);
    }

    private function llenar($componente)
    {
        return $componente
            ->set('tipo_consulta', 'ginecologica')
            ->set('motivo_consulta', 'Control anual')
            ->set('diagnostico', 'Paciente sana')
            ->set('examen_fisico_peso', 64)
            ->set('examen_fisico_talla', 1.6);
    }

    public function test_atender_turno_registra_consulta_con_imc(): void
    {
        $this->llenar(Livewire::test(AtenderTurno::class, ['turno' => $this->turno]))
            ->call('guardarConsulta')
            ->assertHasNoErrors();

        $consulta = ExpedienteConsulta::sole();
        $this->assertSame($this->paciente->id, $consulta->paciente_id);
        $this->assertSame($this->turno->id, $consulta->turno_id);
        $this->assertEquals(25.0, $consulta->examen_fisico['imc']);
    }

    public function test_crear_consulta_desde_turno(): void
    {
        $this->llenar(Livewire::test(CrearConsulta::class, ['turno' => $this->turno]))
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect(route('turno.atender', $this->turno->id));

        $this->assertSame($this->paciente->id, ExpedienteConsulta::sole()->paciente_id);
    }

    public function test_crear_consulta_sin_turno_usa_la_especialidad_del_medico(): void
    {
        $this->llenar(Livewire::test(CrearConsultaSinTurno::class))
            ->call('seleccionarPaciente', $this->paciente->id)
            ->call('iniciarConsulta')
            ->call('guardar')
            ->assertHasNoErrors();

        $consulta = ExpedienteConsulta::sole();
        $this->assertSame($this->medico->especialidad_id, $consulta->especialidad_id);
        $this->assertNull($consulta->turno_id);
    }

    public function test_editar_consulta_conserva_y_actualiza_datos(): void
    {
        $this->llenar(Livewire::test(AtenderTurno::class, ['turno' => $this->turno]))->call('guardarConsulta');
        $consulta = ExpedienteConsulta::sole();

        Livewire::test(EditarConsulta::class, ['consulta' => $consulta])
            ->assertSet('motivo_consulta', 'Control anual')
            ->assertSet('examen_fisico_talla', 1.6)
            ->set('diagnostico', 'Quiste ovárico simple')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('Quiste ovárico simple', $consulta->fresh()->diagnostico);
        $this->assertEquals(25.0, $consulta->fresh()->examen_fisico['imc']);
    }

    public function test_validacion_obligatoria(): void
    {
        Livewire::test(AtenderTurno::class, ['turno' => $this->turno])
            ->call('guardarConsulta')
            ->assertHasErrors(['motivo_consulta' => 'required', 'diagnostico' => 'required']);

        $this->assertSame(0, ExpedienteConsulta::count());
    }
}
