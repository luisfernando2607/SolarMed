<?php

namespace Tests\Feature\Clinica;

use App\Livewire\AtenderTurno;
use App\Livewire\PreparacionTurno;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Preparacion;
use App\Models\RegistroAcceso;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PreparacionTest extends TestCase
{
    use RefreshDatabase;

    private User $enfermera;
    private User $medico;
    private Turno $turno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $this->enfermera = User::factory()->create(['name' => 'Lic. Rosa']); $this->enfermera->assignRole('enfermeria');
        $this->medico = User::factory()->create(); $this->medico->assignRole('medico');
        Medico::create(['user_id' => $this->medico->id, 'nombres' => 'Luis', 'apellidos' => 'Mora', 'especialidad_id' => $esp->id]);
        $p = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'altura' => 1.6]);
        $this->turno = Turno::create(['numero_turno' => 1, 'prefijo' => 'G', 'especialidad_id' => $esp->id, 'paciente_id' => $p->id, 'cedula' => '0102030405',
            'motivo' => 'Cefalea', 'estado' => 'esperando', 'fecha' => today(), 'hora_registro' => now()]);
    }

    public function test_enfermeria_registra_signos_y_el_medico_los_recibe(): void
    {
        $this->actingAs($this->enfermera)->get(route('sala-espera'))->assertOk()->assertSee('Tomar signos');
        $this->actingAs($this->enfermera)->get(route('dashboard'))->assertSee('Por tomar signos');

        Livewire::actingAs($this->enfermera)->test(PreparacionTurno::class, ['turno' => $this->turno])
            ->assertSet('talla', 1.6)
            ->set('presion_sistolica', 150)->set('presion_diastolica', 95)
            ->set('frecuencia_cardiaca', 80)->set('temperatura', 36.8)->set('saturacion', 97)->set('peso', 64)
            ->call('guardar')->assertHasNoErrors()->assertRedirect(route('sala-espera'));

        $prep = Preparacion::sole();
        $this->assertSame('150/95', $prep->presion());
        $this->assertSame(25.0, $prep->imc());
        $this->assertContains('presion_sistolica', $prep->fueraDeRango());
        $this->assertSame('crear', RegistroAcceso::latest('id')->value('accion'));

        Livewire::actingAs($this->medico)->test(AtenderTurno::class, ['turno' => $this->turno->fresh()])
            ->assertSee('Preparación de enfermería')
            ->call('toggleForm')
            ->assertSet('examen_fisico_pa', '150/95')
            ->assertSet('examen_fisico_fc', 80)
            ->assertSet('examen_fisico_imc', 25.0);
    }

    public function test_validaciones_y_turno_cerrado(): void
    {
        Livewire::actingAs($this->enfermera)->test(PreparacionTurno::class, ['turno' => $this->turno])
            ->set('talla', null)
            ->call('guardar')->assertHasErrors('presion_sistolica')
            ->set('presion_sistolica', 120)->set('presion_diastolica', 130)
            ->call('guardar')->assertHasErrors('presion_diastolica')
            ->set('presion_diastolica', 80)->set('talla', 160)
            ->call('guardar')->assertHasErrors('talla');

        $this->turno->update(['estado' => 'completado']);
        Livewire::actingAs($this->enfermera)->test(PreparacionTurno::class, ['turno' => $this->turno->fresh()])
            ->set('presion_sistolica', 120)->set('presion_diastolica', 80)
            ->call('guardar')->assertForbidden();
    }

    public function test_permisos(): void
    {
        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('turnos.preparacion', $this->turno))->assertForbidden();
        $this->actingAs($this->medico)->get(route('turnos.preparacion', $this->turno))->assertOk();
    }
}
