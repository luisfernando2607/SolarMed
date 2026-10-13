<?php

namespace Tests\Feature\Clinica;

use App\Livewire\FormularioTurno;
use App\Models\ConsentimientoDatos;
use App\Models\Especialidad;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AvisoPrivacidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_aviso_publico_con_datos_de_la_clinica_y_enlaces(): void
    {
        $this->get(route('privacidad'))->assertOk()
            ->assertSee('Aviso de privacidad')
            ->assertSee('Ley Orgánica de Protección de Datos Personales')
            ->assertSee('15 días')
            ->assertSee(ConsentimientoDatos::VERSION_AVISO);

        $this->get(route('home'))->assertSee(route('privacidad'));
        $this->get(route('login'))->assertSee(route('privacidad'));
    }

    public function test_turno_publico_exige_y_registra_el_consentimiento(): void
    {
        $this->seed(RolesSeeder::class);
        $esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G', 'activo' => true, 'motivos' => ['Fiebre']]);

        $form = Livewire::test(FormularioTurno::class)
            ->set('especialidad_id', (string) $esp->id)
            ->set(['nombres' => 'Luis', 'apellidos' => 'Mora', 'cedula' => '0911111111', 'sexo' => 'masculino', 'telefono' => '0999999999', 'motivo' => 'Fiebre']);

        $form->call('registrar')->assertHasErrors(['acepta_privacidad' => 'accepted']);
        $this->assertSame(0, ConsentimientoDatos::count());

        $form->set('acepta_privacidad', true)->call('registrar')->assertHasNoErrors()->assertSet('turnoAsignado', 'G1');

        $c = ConsentimientoDatos::sole();
        $this->assertSame('turno_publico', $c->origen);
        $this->assertSame('0911111111', $c->cedula);
        $this->assertSame(ConsentimientoDatos::VERSION_AVISO, $c->version_aviso);
        $this->assertNotNull($c->paciente_id);
        $this->assertNotNull($c->ip);
    }
}
