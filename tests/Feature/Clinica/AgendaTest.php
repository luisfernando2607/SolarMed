<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Agenda;
use App\Models\Cita;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    private User $secretaria;
    private Medico $medico;
    private Paciente $paciente;
    private Carbon $manana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'color_agenda' => '#EC4899']);
        $this->secretaria = User::factory()->create();
        $this->secretaria->assignRole('secretaria');
        $medicoUser = User::factory()->create();
        $medicoUser->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $medicoUser->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);
        $this->paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'telefono' => '0999999999']);
        $this->manana = today()->addDay()->setTime(9, 0);
    }

    private function cita(Carbon $inicio, int $duracion = 30, string $estado = 'pendiente'): Cita
    {
        return Cita::create([
            'paciente_id' => $this->paciente->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->medico->especialidad_id,
            'tipo' => 'Control prenatal', 'fecha_hora' => $inicio, 'duracion_min' => $duracion, 'estado' => $estado,
        ]);
    }

    public function test_agenda_cita_con_paciente_existente_y_la_muestra_en_el_calendario(): void
    {
        $c = Livewire::actingAs($this->secretaria)->test(Agenda::class)
            ->call('nuevaCita', $this->manana->toIso8601String())
            ->assertSet('modal', 'crear')
            ->set('busquedaPaciente', '0102')
            ->assertSee('Ana Pérez')
            ->call('seleccionarPaciente', $this->paciente->id)
            ->set('medico_id', $this->medico->id)
            ->set('tipo', 'Control prenatal')
            ->set('duracion_min', 30)
            ->call('guardarCita')
            ->assertHasNoErrors()
            ->assertSet('modal', null)
            ->assertDispatched('agenda-actualizada');

        $cita = Cita::sole();
        $this->assertSame($this->manana->format('Y-m-d H:i'), $cita->fecha_hora->format('Y-m-d H:i'));
        $this->assertSame($this->medico->especialidad_id, $cita->especialidad_id);

        $eventos = $c->instance()->eventos(today()->toIso8601String(), today()->addWeek()->toIso8601String());
        $this->assertCount(1, $eventos);
        $this->assertSame('#EC4899', $eventos[0]['backgroundColor']);
        $this->assertStringContainsString('Ana Pérez', $eventos[0]['title']);
    }

    public function test_agenda_con_paciente_nuevo(): void
    {
        Livewire::actingAs($this->secretaria)->test(Agenda::class)
            ->call('nuevaCita', $this->manana->toIso8601String())
            ->set('pacienteNuevo', true)
            ->set('nuevo_nombres', 'Lucía')
            ->set('nuevo_apellidos', 'Mora')
            ->set('nuevo_cedula', '0911111111')
            ->set('nuevo_telefono', '0988888888')
            ->set('medico_id', $this->medico->id)
            ->call('guardarCita')
            ->assertHasNoErrors();

        $this->assertSame('0911111111', Cita::sole()->paciente->cedula);
    }

    public function test_no_permite_citas_superpuestas_del_mismo_medico(): void
    {
        $this->cita($this->manana, 30);

        Livewire::actingAs($this->secretaria)->test(Agenda::class)
            ->call('nuevaCita', $this->manana->copy()->addMinutes(15)->toIso8601String())
            ->call('seleccionarPaciente', $this->paciente->id)
            ->set('medico_id', $this->medico->id)
            ->call('guardarCita')
            ->assertHasErrors(['fecha_hora'])
            ->call('nuevaCita', $this->manana->copy()->addMinutes(30)->toIso8601String())
            ->call('seleccionarPaciente', $this->paciente->id)
            ->set('medico_id', $this->medico->id)
            ->call('guardarCita')
            ->assertHasNoErrors();

        $this->assertSame(2, Cita::count());
    }

    public function test_reprogramar_arrastrando_respeta_choques(): void
    {
        $a = $this->cita($this->manana, 30);
        $b = $this->cita($this->manana->copy()->addHour(), 30);
        $c = Livewire::actingAs($this->secretaria)->test(Agenda::class);

        $this->assertFalse($c->instance()->mover($b->id, $this->manana->copy()->addMinutes(10)->toIso8601String()));
        $this->assertTrue($c->instance()->mover($b->id, $this->manana->copy()->addHours(2)->toIso8601String()));
        $this->assertSame('11:00', $b->fresh()->fecha_hora->format('H:i'));
    }

    public function test_registrar_llegada_crea_turno_en_sala_de_espera(): void
    {
        $cita = $this->cita(now()->addMinutes(5), 20, 'confirmada');

        Livewire::actingAs($this->secretaria)->test(Agenda::class)
            ->call('verCita', $cita->id)
            ->assertSee('Registrar llegada')
            ->call('registrarLlegada');

        $turno = Turno::sole();
        $this->assertSame($this->paciente->id, $turno->paciente_id);
        $this->assertSame('esperando', $turno->estado);
        $this->assertSame('completada', $cita->fresh()->estado);
    }

    public function test_permisos(): void
    {
        $medicoUser = $this->medico->user;
        Livewire::actingAs($medicoUser)->test(Agenda::class)
            ->assertSet('filtroMedico', (string) $this->medico->id)
            ->call('nuevaCita', $this->manana->toIso8601String())
            ->assertForbidden();

        $sinRol = User::factory()->create();
        $this->actingAs($sinRol)->get(route('agenda'))->assertForbidden();
        $this->actingAs($this->secretaria)->get(route('agenda'))->assertOk()->assertSee('calendario-citas', false);
    }
}
