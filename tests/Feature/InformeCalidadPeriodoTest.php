<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InformeCalidadPeriodoTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_informe_de_calidad_del_periodo_se_genera_en_pdf(): void
    {
        $this->seed(RolesSeeder::class);
        $u = User::factory()->create(['confidencialidad_aceptada_at' => now(), 'debe_cambiar_password' => false]);
        $u->assignRole('auditor');

        $res = $this->actingAs($u->fresh())->get(route('auditoria.informe-periodo', [
            'desde' => now()->subDays(30)->toDateString(),
            'hasta' => now()->toDateString(),
        ]));

        $res->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('content-type'));
    }

    public function test_sin_permiso_de_auditoria_no_genera_el_informe(): void
    {
        $this->seed(RolesSeeder::class);
        $u = User::factory()->create(['confidencialidad_aceptada_at' => now(), 'debe_cambiar_password' => false]);
        $u->assignRole('secretaria');

        $this->actingAs($u->fresh())->get(route('auditoria.informe-periodo'))->assertForbidden();
    }
}
