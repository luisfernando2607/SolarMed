<?php

namespace Tests\Feature\Clinica;

use App\Livewire\PanelPendientes;
use App\Models\Cita;
use App\Models\ControlPrenatal;
use App\Models\Embarazo;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\ServicioTarifario;
use App\Models\User;
use App\Services\FacturaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelPendientesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $medicoUser;
    private Medico $medico;
    private Medico $otroMedico;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->medicoUser = User::factory()->create();
        $this->medicoUser->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $this->medicoUser->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);
        $this->otroMedico = Medico::create(['user_id' => User::factory()->create()->id, 'nombres' => 'Jorge', 'apellidos' => 'Bury', 'especialidad_id' => $esp->id]);

        $paciente = fn (string $n, string $ced) => Paciente::create(['nombres' => $n, 'apellidos' => 'Test', 'cedula' => $ced, 'sexo' => 'femenino']);

        // Embarazo con PA alta en el último control (del médico)
        $ana = $paciente('Ana', '0100000001');
        $e1 = Embarazo::create(['paciente_id' => $ana->id, 'medico_id' => $this->medico->id, 'fum' => today()->subWeeks(30), 'fpp' => today()->subWeeks(30)->addDays(280)]);
        ControlPrenatal::create(['embarazo_id' => $e1->id, 'paciente_id' => $ana->id, 'medico_id' => $this->medico->id, 'fecha' => today()->subDays(3), 'semanas_gestacion' => 29, 'presion_arterial' => '150/95']);

        // Embarazo al día, sin alertas (del médico)
        $bea = $paciente('Bea', '0100000002');
        $e2 = Embarazo::create(['paciente_id' => $bea->id, 'medico_id' => $this->medico->id, 'fum' => today()->subWeeks(20), 'fpp' => today()->subWeeks(20)->addDays(280)]);
        ControlPrenatal::create(['embarazo_id' => $e2->id, 'paciente_id' => $bea->id, 'medico_id' => $this->medico->id, 'fecha' => today()->subDays(7), 'semanas_gestacion' => 19, 'presion_arterial' => '110/70']);

        // Embarazo de otro médico, FPP en 5 días y sin controles
        $cami = $paciente('Cami', '0100000003');
        Embarazo::create(['paciente_id' => $cami->id, 'medico_id' => $this->otroMedico->id, 'fum' => today()->subDays(275), 'fpp' => today()->addDays(5)]);

        // Citas y facturas
        Cita::create(['paciente_id' => $ana->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $esp->id, 'tipo' => 'Control prenatal', 'fecha_hora' => today()->setTime(15, 0), 'estado' => 'pendiente']);
        $servicio = ServicioTarifario::create(['especialidad_id' => $esp->id, 'nombre' => 'Consulta', 'precio' => 40, 'activo' => true]);
        $crear = fn (Medico $m) => app(FacturaService::class)->crear([
            'paciente_id' => $ana->id, 'medico_id' => $m->id, 'especialidad_id' => $esp->id, 'user_id' => $this->admin->id,
            'fecha' => now(), 'forma_pago' => 'efectivo',
        ], [['servicio_id' => $servicio->id, 'cantidad' => 1]]);
        $crear($this->medico);
        $crear($this->medico)->update(['estado_sri' => 'rechazado']);
        $crear($this->otroMedico)->update(['estado_sri' => 'enviado']);
    }

    public function test_admin_ve_todo(): void
    {
        $c = Livewire::actingAs($this->admin)->test(PanelPendientes::class);

        $this->assertSame(1, $c->viewData('hoy')['citas']);
        $this->assertSame(1, $c->viewData('hoy')['sin_confirmar']);
        $this->assertSame(1, $c->viewData('sri')['sin_emitir']);
        $this->assertSame(1, $c->viewData('sri')['en_proceso']);
        $this->assertCount(1, $c->viewData('sri')['con_problema']);

        $facturacion = $c->viewData('facturacion');
        $this->assertEqualsWithDelta(80.0, (float) $facturacion->firstWhere('medico_id', $this->medico->id)->monto, 0.01);

        $embarazos = $c->viewData('embarazos');
        $this->assertSame(3, $embarazos['activos']);
        $this->assertSame(['Ana', 'Cami'], $embarazos['atencion']->map(fn ($x) => $x['embarazo']->paciente->nombres)->sort()->values()->all());
        $c->assertSee('hipertensión')->assertSee('FPP en 5 días');
    }

    public function test_medico_ve_sus_embarazos_y_no_la_facturacion_por_medico(): void
    {
        $c = Livewire::actingAs($this->medicoUser)->test(PanelPendientes::class);

        $this->assertNull($c->viewData('facturacion'));
        $this->assertSame(2, $c->viewData('embarazos')['activos']);
        $this->assertSame(['Ana'], $c->viewData('embarazos')['atencion']->map(fn ($x) => $x['embarazo']->paciente->nombres)->all());
    }

    public function test_secretaria_no_ve_datos_clinicos(): void
    {
        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');

        $c = Livewire::actingAs($secretaria)->test(PanelPendientes::class);
        $this->assertNull($c->viewData('embarazos'));
        $this->assertNull($c->viewData('facturacion'));
        $this->assertNotNull($c->viewData('hoy'));
        $c->assertDontSee('Ana Test');
    }

    public function test_dashboards_cargan_para_cada_rol(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Embarazos que requieren atención');
        $this->actingAs($this->medicoUser)->get(route('dashboard'))->assertOk()->assertSee('Agenda');

        foreach (['secretaria', 'enfermeria'] as $rol) {
            $u = User::factory()->create();
            $u->assignRole($rol);
            $this->actingAs($u)->get(route('dashboard'))->assertOk();
        }
    }
}
