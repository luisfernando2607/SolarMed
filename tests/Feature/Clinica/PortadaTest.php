<?php

namespace Tests\Feature\Clinica;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\User;
use App\Services\AjustesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortadaTest extends TestCase
{
    use RefreshDatabase;

    public function test_portada_publica_con_especialidades_medicos_y_horario(): void
    {
        $gine = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'color_agenda' => '#EC4899', 'motivos' => ['Control prenatal', 'Otro'], 'activo' => true]);
        Especialidad::create(['nombre' => 'Traumatología', 'codigo' => 'trauma', 'motivos' => ['Fractura'], 'activo' => false]);
        Medico::create(['user_id' => User::factory()->create()->id, 'nombres' => 'Jorge', 'apellidos' => 'Bury', 'especialidad_id' => $gine->id]);
        app(AjustesService::class)->guardar([
            'marca.nombre' => 'Clínica Santa Ana', 'marca.telefono' => '07-2834567',
            'agenda.hora_inicio' => '08:00', 'agenda.hora_fin' => '17:00', 'agenda.dias_laborables' => [1, 2, 3, 4, 5],
        ]);

        $this->get('/home')
            ->assertOk()
            ->assertSee('Clínica Santa Ana')
            ->assertSee('Ginecología')
            ->assertSee('Dr. Jorge Bury')
            ->assertSee('Control prenatal')
            ->assertDontSee('Traumatología')      // inactiva
            ->assertSee('Lunes a viernes')
            ->assertSee('08:00 – 17:00')
            ->assertSee('tel:072834567', false);
    }

    public function test_dias_no_consecutivos_se_listan(): void
    {
        app(AjustesService::class)->guardar(['agenda.dias_laborables' => [1, 3, 5]]);
        $this->get('/home')->assertSee('Lunes, miércoles, viernes');
    }

    public function test_formulario_de_verificacion_redirige_al_codigo(): void
    {
        $this->get('/verificar?codigo=ab-cd 234')->assertRedirect(route('certificados.verificar', 'ABCD234'));
        $this->get('/verificar?codigo=')->assertRedirect(route('home'));
    }
}
