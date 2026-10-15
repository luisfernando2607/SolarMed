<?php

namespace Tests\Feature\Clinica;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AyudaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_rol_ve_solo_sus_secciones(): void
    {
        $this->seed(RolesSeeder::class);
        $enf = User::factory()->create(); $enf->assignRole('enfermeria');
        $this->actingAs($enf)->get(route('ayuda'))->assertOk()
            ->assertSee('Primeros pasos')->assertSee('Enfermería: preparación')
            ->assertDontSee('Médicos: consulta y documentos')->assertDontSee('Auditoría y cumplimiento')->assertDontSee('id="administracion"', false);

        $admin = User::factory()->create(); $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('ayuda'))->assertOk()
            ->assertSee('Médicos: consulta y documentos')->assertSee('Auditoría y cumplimiento')->assertSee('id="administracion"', false);

        auth()->logout();
        $this->get(route('ayuda'))->assertRedirect(route('login'));
    }
}
