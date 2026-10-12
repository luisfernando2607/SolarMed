<?php

namespace Tests\Feature\Clinica;

use App\Livewire\SalaEspera;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Las pantallas de recepción y atención se dibujan con datos reales (colas, historial, ficha y expediente).
 */
class PantallasRecepcionTest extends TestCase
{
    use RefreshDatabase;

    private Turno $enAtencion;
    private Turno $esperando;
    private User $medico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);

        $esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G', 'color_agenda' => '#3B82F6', 'activo' => true]);
        $this->medico = User::factory()->create(['name' => 'Carlos García']);
        $this->medico->assignRole('medico');
        Medico::create(['user_id' => $this->medico->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);

        $ana = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'alergias' => 'Penicilina']);

        $this->enAtencion = Turno::create([
            'numero_turno' => 1, 'prefijo' => 'G', 'especialidad_id' => $esp->id, 'paciente_id' => $ana->id,
            'nombre_temporal' => 'Ana Pérez', 'cedula' => '0102030405', 'motivo' => 'Control', 'estado' => 'en_atencion', 'fecha' => today(), 'hora_registro' => now(),
        ]);
        $this->esperando = Turno::create([
            'numero_turno' => 2, 'prefijo' => 'G', 'especialidad_id' => $esp->id,
            'nombre_temporal' => "Luis O'Brien", 'cedula' => '0911111111', 'motivo' => 'Fiebre', 'estado' => 'esperando', 'fecha' => today(), 'hora_registro' => now(),
        ]);
    }

    public function test_sala_de_espera_muestra_colas_historial_y_detalle(): void
    {
        $this->actingAs($this->medico);

        $this->get('/sala-espera')->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('En atención ahora')
            ->assertSee("Luis O'Brien");

        // Recepción ve el botón cancelar; el nombre va escapado dentro del diálogo de confirmación
        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');
        $this->actingAs($secretaria)->get('/sala-espera')->assertOk()
            ->assertSee('¿Cancelar turno?')
            ->assertSee('O\\u0027Brien', false);

        $this->actingAs($this->medico);

        Livewire::test(SalaEspera::class)
            ->set('vista', 'historial')->assertSee('Esperando')->assertSee('Ver ficha')
            ->call('verDetalle', $this->enAtencion->id)->assertSee('Datos clínicos')->assertSee('Penicilina');
    }

    public function test_atender_turno_destaca_alergias(): void
    {
        $this->actingAs($this->medico)
            ->get(route('turno.atender', $this->enAtencion->id))
            ->assertOk()
            ->assertSee('Alergias:')
            ->assertSee('Penicilina')
            ->assertSee('Nueva consulta');
    }

    public function test_registrar_turno_y_expedientes_cargan(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('turnos.crear'))->assertOk()->assertSee('Asignar turno');
        $this->actingAs($admin)->get(route('expedientes'))->assertOk()->assertSee('Aún no hay consultas registradas');
        $this->actingAs($admin)->get(route('usuarios'))->assertOk()->assertSee('Sin RUC')->assertSee('(usted)');
    }
}
