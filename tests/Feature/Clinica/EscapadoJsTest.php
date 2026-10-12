<?php

namespace Tests\Feature\Clinica;

use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscapadoJsTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_nombre_con_comillas_no_rompe_el_script_de_confirmacion(): void
    {
        $this->seed(RolesSeeder::class);
        $admin = User::factory()->create(); $admin->assignRole('admin');
        // Nombre malicioso que podría llegar desde el formulario público de turnos
        Paciente::create(['nombres' => "x'});alert(1);({'", 'apellidos' => "O'Brien", 'cedula' => '0102030405', 'sexo' => 'otro']);

        $html = $this->actingAs($admin)->get(route('pacientes'))->assertOk()->getContent();

        // El texto debe ir como literal JS escapado (Js::from), nunca dentro de comillas simples sin escapar
        $this->assertStringNotContainsString("text: 'x&#039;});alert(1)", $html);
        $this->assertStringContainsString('\\u0027', html_entity_decode($html));
    }
}
