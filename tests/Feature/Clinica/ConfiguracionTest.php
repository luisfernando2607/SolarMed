<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Agenda;
use App\Livewire\ConfiguracionClinica;
use App\Livewire\FormularioTurno;
use App\Livewire\GestionEspecialidades;
use App\Models\ControlPrenatal;
use App\Models\Especialidad;
use App\Models\Turno;
use App\Models\User;
use App\Services\AjustesService;
use App\Services\PrenatalService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_crear_especialidad_y_usar_sus_motivos_y_prefijo_en_el_formulario_publico(): void
    {
        Livewire::actingAs($this->admin)->test(GestionEspecialidades::class)
            ->call('crear')
            ->set('nombre', 'Pediatría')
            ->assertSet('codigo', 'pediatria')
            ->set('prefijo_turno', 'p')
            ->set('color_agenda', '#10B981')
            ->set('motivosTexto', "Control de niño sano\nVacunación\n\nFiebre\nVacunación")
            ->call('guardar')
            ->assertHasNoErrors();

        $esp = Especialidad::where('codigo', 'pediatria')->sole();
        $this->assertSame('P', $esp->prefijo_turno);
        $this->assertSame(['Control de niño sano', 'Vacunación', 'Fiebre'], $esp->motivos);

        auth()->logout();
        $form = Livewire::test(FormularioTurno::class)
            ->set('especialidad_id', (string) $esp->id)
            ->assertSet('motivosDisponibles', ['Control de niño sano' => 'Control de niño sano', 'Vacunación' => 'Vacunación', 'Fiebre' => 'Fiebre']);

        $form->set(['nombres' => 'Luis', 'apellidos' => 'Mora', 'cedula' => '0911111111', 'sexo' => 'masculino', 'telefono' => '0999999999'])
            ->set('motivo', 'Motivo inventado')
            ->call('registrar')
            ->assertHasErrors(['motivo' => 'in'])
            ->set('motivo', 'Fiebre')
            ->call('registrar')
            ->assertHasNoErrors();

        $turno = Turno::sole();
        $this->assertSame('P', $turno->prefijo);
        $this->assertSame('Fiebre', $turno->motivo);
    }

    public function test_validaciones_y_especialidad_inactiva(): void
    {
        $existente = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G', 'motivos' => ['Consulta'], 'activo' => true]);

        Livewire::actingAs($this->admin)->test(GestionEspecialidades::class)
            ->call('crear')
            ->set('nombre', 'Gastro')
            ->set('prefijo_turno', 'G')
            ->set('motivosTexto', "\n  \n")
            ->call('guardar')
            ->assertHasErrors(['prefijo_turno' => 'unique', 'motivosTexto'])
            ->call('editar', $existente->id)
            ->set('activo', false)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame('general', $existente->fresh()->codigo);
        auth()->logout();
        Livewire::test(FormularioTurno::class)->set('especialidad_id', (string) $existente->id)->assertSet('motivosDisponibles', []);
    }

    public function test_solo_admin_configura(): void
    {
        $medico = User::factory()->create();
        $medico->assignRole('medico');
        $this->actingAs($medico)->get(route('especialidades'))->assertForbidden();
        $this->actingAs($medico)->get(route('configuracion'))->assertForbidden();
        $this->actingAs($this->admin)->get(route('especialidades'))->assertOk();
        $this->actingAs($this->admin)->get(route('configuracion'))->assertOk()->assertSee('Alertas del control prenatal');
    }

    public function test_ajustes_por_defecto_y_guardados(): void
    {
        $ajustes = app(AjustesService::class);
        $this->assertSame(140, $ajustes->get('prenatal.pa_sistolica'));
        $this->assertSame('17:00', $ajustes->get('tareas.hora_recordatorios'));

        Livewire::actingAs($this->admin)->test(ConfiguracionClinica::class)
            ->set('tiposTexto', "Primera vez\nControl\nPrimera vez")
            ->set('duracionesTexto', '30, 15, abc, 60')
            ->set('duracion_defecto', 30)
            ->set('hora_inicio', '08:00')
            ->set('hora_fin', '13:00')
            ->set('dias', ['1', '3', '5'])
            ->set('pa_sistolica', 135)
            ->set('hora_recordatorios', '18:30')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertSame(['Primera vez', 'Control'], $ajustes->get('agenda.tipos'));
        $this->assertSame([15, 30, 60], $ajustes->get('agenda.duraciones'));
        $this->assertSame([1, 3, 5], $ajustes->get('agenda.dias_laborables'));
        $this->assertSame(135, $ajustes->get('prenatal.pa_sistolica'));
        $this->assertSame('18:30', $ajustes->get('tareas.hora_recordatorios'));
        $this->assertSame(90, $ajustes->get('prenatal.pa_diastolica'), 'Los no editados mantienen su valor');

        // La agenda usa los nuevos tipos, duraciones y horario
        $agenda = Livewire::actingAs($this->admin)->test(Agenda::class);
        $this->assertSame(['Primera vez', 'Control'], $agenda->viewData('tipos'));
        $this->assertSame(['inicio' => '08:00:00', 'fin' => '13:00:00', 'dias' => [1, 3, 5]], $agenda->viewData('calendario'));
        $agenda->call('nuevaCita', now()->addDay()->setTime(9, 0)->toIso8601String())
            ->assertSet('tipo', 'Primera vez')
            ->assertSet('duracion_min', 30);

        // Las alertas prenatales usan el nuevo umbral
        $alertas = app(PrenatalService::class)->alertas(new ControlPrenatal(['presion_arterial' => '136/80']));
        $this->assertStringContainsString('hipertensión', $alertas[0]['mensaje'] ?? '');
    }

    public function test_validaciones_de_configuracion(): void
    {
        Livewire::actingAs($this->admin)->test(ConfiguracionClinica::class)
            ->set('hora_inicio', '14:00')
            ->set('hora_fin', '08:00')
            ->set('dias', [])
            ->set('pa_severa_sistolica', 130)
            ->call('guardar')
            ->assertHasErrors(['hora_fin' => 'after', 'dias' => 'required', 'pa_severa_sistolica' => 'gt']);

        Livewire::actingAs($this->admin)->test(ConfiguracionClinica::class)
            ->set('duracionesTexto', '15, 30')
            ->set('duracion_defecto', 20)
            ->call('guardar')
            ->assertHasErrors('duracion_defecto');

        $this->assertSame(20, app(AjustesService::class)->get('agenda.duracion_defecto'), 'Nada se guardó');
    }

    public function test_datos_del_establecimiento_msp(): void
    {
        Livewire::actingAs($this->admin)->test(\App\Livewire\ConfiguracionClinica::class)
            ->set('est_unicodigo', 'ABC')->call('guardar')->assertHasErrors('est_unicodigo')
            ->set('est_unicodigo', '12345')->set('est_institucion', 'Red Privada Complementaria')
            ->set('est_provincia', 'Guayas')->set('est_canton', 'Guayaquil')
            ->call('guardar')->assertHasNoErrors();

        $e = app(\App\Services\MarcaService::class)->establecimiento();
        $this->assertSame('12345', $e['unicodigo']);
        $this->assertSame('Guayas', $e['provincia']);
        $this->assertSame('Red Privada Complementaria', $e['institucion']);
    }

    public function test_mensajes_en_espanol(): void
    {
        $this->assertSame('Siguiente &raquo;', __('pagination.next'));
        $this->assertSame('Mostrando', __('Showing'));
        $this->assertSame('El campo correo electrónico es obligatorio.',
            \Illuminate\Support\Facades\Validator::make([], ['email' => 'required'])->errors()->first('email'));
    }
}
