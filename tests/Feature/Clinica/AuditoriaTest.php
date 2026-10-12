<?php

namespace Tests\Feature\Clinica;

use App\Livewire\Auditoria;
use App\Livewire\ListaPacientes;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private User $medico;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->medico = User::factory()->create(['name' => 'Dr. Carlos']);
        $this->medico->assignRole('medico');
        $this->paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
    }

    public function test_ver_ficha_y_descargar_historia_quedan_registrados(): void
    {
        Livewire::actingAs($this->medico)->test(ListaPacientes::class)
            ->call('seleccionar', $this->paciente->id);

        $this->actingAs($this->medico)->get(route('pacientes.pdf', $this->paciente))->assertOk();

        $registros = RegistroAcceso::orderBy('id')->get();
        $this->assertSame(['ver', 'descargar'], $registros->pluck('accion')->all());
        $this->assertSame(['paciente', 'historia_clinica'], $registros->pluck('recurso')->all());
        $this->assertTrue($registros->every(fn ($r) => $r->user_id === $this->medico->id && $r->paciente_id === $this->paciente->id));
        $this->assertNotNull($registros->first()->ip);
    }

    public function test_eliminar_paciente_queda_registrado_con_sus_datos(): void
    {
        Livewire::actingAs($this->medico)->test(ListaPacientes::class)
            ->call('eliminarPaciente', $this->paciente->id);

        $registro = RegistroAcceso::sole();
        $this->assertSame('eliminar', $registro->accion);
        $this->assertStringContainsString('0102030405', $registro->detalle);
    }

    public function test_solo_admin_ve_la_auditoria_y_puede_filtrar(): void
    {
        $this->actingAs($this->medico)->get(route('auditoria'))->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $otro = Paciente::create(['nombres' => 'Luis', 'apellidos' => 'Mora', 'cedula' => '0911111111', 'sexo' => 'masculino']);
        RegistroAcceso::create(['user_id' => $this->medico->id, 'accion' => 'ver', 'recurso' => 'paciente', 'paciente_id' => $this->paciente->id]);
        RegistroAcceso::create(['user_id' => $this->medico->id, 'accion' => 'descargar', 'recurso' => 'historia_clinica', 'paciente_id' => $otro->id]);

        $this->actingAs($admin)->get(route('auditoria'))->assertOk();

        Livewire::actingAs($admin)->test(Auditoria::class)
            ->assertSee('0911111111')
            ->set('paciente', '0102030405')
            ->assertSee('Ana Pérez')
            ->assertDontSee('0911111111')
            ->set('paciente', '')
            ->set('accion', 'descargar')
            ->assertSee('0911111111')
            ->assertDontSee('0102030405');
    }
}
