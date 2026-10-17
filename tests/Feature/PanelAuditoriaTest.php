<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    private function auditor(): User
    {
        $this->seed(RolesSeeder::class);
        $u = User::factory()->create(['confidencialidad_aceptada_at' => now(), 'debe_cambiar_password' => false]);
        $u->assignRole('auditor');

        return $u->fresh();
    }

    public function test_el_panel_de_auditoria_carga_para_el_auditor(): void
    {
        $this->actingAs($this->auditor())->get(route('auditoria.panel'))
            ->assertOk()
            ->assertSee('Panel de auditoría')
            ->assertSee('Calidad de la historia clínica');
    }

    public function test_sin_permiso_de_auditoria_no_entra(): void
    {
        $this->seed(RolesSeeder::class);
        $u = User::factory()->create(['confidencialidad_aceptada_at' => now(), 'debe_cambiar_password' => false]);
        $u->assignRole('secretaria');

        $this->actingAs($u->fresh())->get(route('auditoria.panel'))->assertForbidden();
    }
}
