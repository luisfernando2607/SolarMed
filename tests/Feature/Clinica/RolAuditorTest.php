<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ArchivosPaciente;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolAuditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_auditor_lee_historias_y_auditoria_pero_no_modifica_ni_factura(): void
    {
        $this->seed(RolesSeeder::class);
        $auditor = User::factory()->create(); $auditor->assignRole('auditor');
        $p = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);

        $this->actingAs($auditor)->get(route('auditoria'))->assertOk();
        $this->actingAs($auditor)->get(route('pacientes.ver', $p))->assertOk()->assertSee('Historia clínica');
        $this->actingAs($auditor)->get(route('pacientes.pdf', $p))->assertOk();
        $this->actingAs($auditor)->get(route('facturas'))->assertForbidden();
        $this->actingAs($auditor)->get(route('configuracion'))->assertForbidden();

        Livewire::actingAs($auditor)->test(ArchivosPaciente::class, ['paciente' => $p])
            ->call('subir')->assertForbidden();
    }

    public function test_medico_no_ve_la_auditoria(): void
    {
        $this->seed(RolesSeeder::class);
        $medico = User::factory()->create(); $medico->assignRole('medico');
        $this->actingAs($medico)->get(route('auditoria'))->assertForbidden();
    }
}
