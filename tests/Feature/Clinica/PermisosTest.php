<?php

namespace Tests\Feature\Clinica;

use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermisosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    private function usuarioConRol(?string $rol): User
    {
        $user = User::factory()->create();
        if ($rol) {
            $user->assignRole($rol);
        }
        return $user;
    }

    public function test_invitado_es_redirigido_al_login(): void
    {
        $this->get('/pacientes')->assertRedirect('/login');
        $this->get('/expedientes')->assertRedirect('/login');
    }

    public function test_usuario_sin_rol_no_accede_a_datos_clinicos(): void
    {
        $paciente = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
        ]);
        $user = $this->usuarioConRol(null);

        foreach (['/pacientes', '/expedientes', '/facturas', '/usuarios', "/pacientes/pdf/{$paciente->id}"] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_secretaria_no_ve_expedientes_pero_si_la_sala_de_espera(): void
    {
        $user = $this->usuarioConRol('secretaria');

        $this->actingAs($user)->get('/expedientes')->assertForbidden();
        $this->actingAs($user)->get('/pacientes')->assertForbidden();
        $this->actingAs($user)->get('/sala-espera')->assertOk();
    }

    public function test_medico_ve_pacientes_y_expedientes(): void
    {
        $user = $this->usuarioConRol('medico');

        $this->actingAs($user)->get('/pacientes')->assertOk();
        $this->actingAs($user)->get('/expedientes')->assertOk();
        $this->actingAs($user)->get('/sri/configuracion')->assertForbidden();
    }
}
