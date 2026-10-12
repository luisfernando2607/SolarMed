<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ControlPrenatalPaciente;
use App\Models\ControlPrenatal;
use App\Models\Embarazo;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\PrenatalService;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ControlPrenatalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $especialidad = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->user = User::factory()->create();
        $this->user->assignRole('medico');
        Medico::create(['user_id' => $this->user->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $especialidad->id]);
        $this->paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
    }

    public function test_fpp_por_naegele_y_edad_gestacional(): void
    {
        $fum = Carbon::parse('2026-01-01');
        $this->assertSame('2026-10-08', app(PrenatalService::class)->fpp($fum)->format('Y-m-d'));

        $embarazo = new Embarazo(['fum' => $fum]);
        $this->assertSame(['semanas' => 20, 'dias' => 3], $embarazo->edadGestacional(Carbon::parse('2026-05-24')));
        $this->assertSame(1, $embarazo->trimestre(Carbon::parse('2026-03-01')));
        $this->assertSame(2, $embarazo->trimestre(Carbon::parse('2026-05-24')));
        $this->assertSame(3, $embarazo->trimestre(Carbon::parse('2026-08-01')));
    }

    public function test_alertas_clinicas(): void
    {
        $alertas = fn (array $datos) => collect(app(PrenatalService::class)->alertas(new ControlPrenatal($datos)))->pluck('mensaje')->implode(' | ');

        $this->assertSame('', $alertas(['semanas_gestacion' => 30, 'presion_arterial' => '110/70', 'fcf' => 140, 'altura_uterina' => 29, 'movimientos_fetales' => true]));
        $this->assertStringContainsString('descartar preeclampsia', $alertas(['presion_arterial' => '142/88']));
        $this->assertStringContainsString('severa', $alertas(['presion_arterial' => '165/112']));
        $this->assertStringContainsString('FCF 100', $alertas(['fcf' => 100]));
        $this->assertStringContainsString('no acorde', $alertas(['semanas_gestacion' => 32, 'altura_uterina' => 26]));
        $this->assertSame('', $alertas(['semanas_gestacion' => 16, 'altura_uterina' => 10])); // fuera del rango 20–36
        $this->assertStringContainsString('movimientos fetales', $alertas(['semanas_gestacion' => 30, 'movimientos_fetales' => false]));
        $this->assertStringContainsString('Edemas con hipertensión', $alertas(['presion_arterial' => '145/95', 'edemas' => '++']));
        $this->assertStringNotContainsString('Edemas', $alertas(['presion_arterial' => '145/95', 'edemas' => 'Ausentes']));
    }

    public function test_flujo_completo_del_embarazo(): void
    {
        $fum = today()->subWeeks(24)->subDays(2);

        $componente = Livewire::actingAs($this->user)->test(ControlPrenatalPaciente::class, ['paciente' => $this->paciente])
            ->assertSee('Sin embarazo activo')
            ->set('fum', $fum->format('Y-m-d'))
            ->call('iniciarEmbarazo')
            ->assertHasNoErrors()
            ->assertSee('24<span class="text-base font-medium">+2</span>', false);

        $embarazo = Embarazo::sole();
        $this->assertSame($fum->copy()->addDays(280)->format('Y-m-d'), $embarazo->fpp->format('Y-m-d'));
        $this->assertSame($fum->format('Y-m-d'), $this->paciente->fresh()->fum->format('Y-m-d'));

        $componente
            ->set('presion_arterial', '120 / 80')
            ->set('altura_uterina', 24)
            ->set('fcf', 145)
            ->set('movimientos_fetales', '1')
            ->call('registrarControl')
            ->assertHasNoErrors()
            ->set('presion_arterial', '150/95')
            ->call('registrarControl')
            ->assertDispatched('notify', type: 'warning')
            ->call('toggleExamen', 'eco_morfologica')
            ->call('toggleExamen', 'clave_inventada');

        $controles = ControlPrenatal::orderBy('id')->get();
        $this->assertCount(2, $controles);
        $this->assertSame([24, 2], [$controles[0]->semanas_gestacion, $controles[0]->dias_gestacion]);
        $this->assertSame('120/80', $controles[0]->presion_arterial);
        $this->assertTrue($controles[0]->movimientos_fetales);
        $this->assertSame($embarazo->id, $controles[0]->embarazo_id);
        $this->assertSame(['eco_morfologica'], array_keys($embarazo->fresh()->checklist));

        $componente
            ->call('cerrarEmbarazo')
            ->assertHasErrors(['desenlace' => 'required'])
            ->set('desenlace', 'cesarea')
            ->call('cerrarEmbarazo')
            ->assertHasNoErrors()
            ->assertSee('Sin embarazo activo')
            ->assertSee('Embarazos anteriores');

        $this->assertSame('finalizado', $embarazo->fresh()->estado);
    }

    public function test_validaciones(): void
    {
        Livewire::actingAs($this->user)->test(ControlPrenatalPaciente::class, ['paciente' => $this->paciente])
            ->set('fum', today()->addDay()->format('Y-m-d'))
            ->call('iniciarEmbarazo')
            ->assertHasErrors(['fum' => 'before_or_equal'])
            ->set('fum', today()->subWeeks(10)->format('Y-m-d'))
            ->call('iniciarEmbarazo')
            ->set('presion_arterial', '120-80')
            ->call('registrarControl')
            ->assertHasErrors(['presion_arterial' => 'regex'])
            ->set('fum', today()->subWeeks(5)->format('Y-m-d'))
            ->call('iniciarEmbarazo');

        $this->assertSame(1, Embarazo::count(), 'No debe haber dos embarazos activos');
    }

    public function test_permisos(): void
    {
        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');
        $this->actingAs($secretaria)->get(route('prenatal', $this->paciente))->assertForbidden();
        $this->actingAs($this->user)->get(route('prenatal', $this->paciente))->assertOk();
    }
}
