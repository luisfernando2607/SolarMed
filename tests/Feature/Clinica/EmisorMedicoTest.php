<?php

namespace Tests\Feature\Clinica;

use App\Livewire\CrearFactura;
use App\Livewire\GestionUsuarios;
use App\Models\Especialidad;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\ServicioTarifario;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmisorMedicoTest extends TestCase
{
    use RefreshDatabase;

    private Especialidad $especialidad;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->especialidad = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
    }

    private function medico(string $nombre, ?string $ruc): Medico
    {
        $user = User::factory()->create(['name' => $nombre]);
        $user->assignRole('medico');
        return Medico::create([
            'user_id' => $user->id, 'nombres' => $nombre, 'apellidos' => 'Test', 'especialidad_id' => $this->especialidad->id,
            'ruc' => $ruc, 'razon_social' => $ruc ? "{$nombre} Test" : null, 'direccion_matriz' => $ruc ? 'Guayaquil' : null,
        ]);
    }

    public function test_admin_guarda_datos_tributarios_del_medico(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $medico = $this->medico('Carlos', null);

        Livewire::actingAs($admin)->test(GestionUsuarios::class)
            ->call('editar', $medico->user_id)
            ->assertSet('rol', 'medico')
            ->set('ruc', '0912345678001')
            ->set('razon_social', 'GARCIA CARLOS')
            ->set('direccion_matriz', 'Av. 9 de Octubre')
            ->set('regimen_rimpe', 'CONTRIBUYENTE RÉGIMEN RIMPE')
            ->call('guardar')
            ->assertHasNoErrors();

        $medico->refresh();
        $this->assertSame('0912345678001', $medico->ruc);
        $this->assertSame('CONTRIBUYENTE RÉGIMEN RIMPE', $medico->regimen_rimpe);
    }

    public function test_ruc_invalido_o_sin_razon_social_no_se_guarda(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $medico = $this->medico('Carlos', null);

        Livewire::actingAs($admin)->test(GestionUsuarios::class)
            ->call('editar', $medico->user_id)
            ->set('ruc', '12345')
            ->call('guardar')
            ->assertHasErrors(['ruc' => 'regex'])
            ->set('ruc', '0912345678001')
            ->set('razon_social', '')
            ->call('guardar')
            ->assertHasErrors(['razon_social' => 'required_with']);
    }

    public function test_factura_se_emite_con_el_medico_elegido(): void
    {
        $carlos = $this->medico('Carlos', '0912345678001');
        $jorge = $this->medico('Jorge', '0998765432001');
        $servicio = ServicioTarifario::create(['especialidad_id' => $this->especialidad->id, 'nombre' => 'Consulta ginecológica', 'precio' => 30, 'activo' => true]);
        $paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);

        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');

        Livewire::actingAs($secretaria)->test(CrearFactura::class)
            ->call('seleccionarPaciente', $paciente->id)
            ->assertSet('medico_id', null)
            ->call('agregarItem', $servicio->id)
            ->call('guardar')
            ->assertHasErrors(['medico_id' => 'required'])
            ->set('medico_id', $jorge->id)
            ->call('guardar')
            ->assertHasNoErrors();

        $factura = Factura::sole();
        $this->assertSame($jorge->id, $factura->medico_id);
        $this->assertSame('0998765432001', $factura->ruc_emisor);
        $this->assertSame($this->especialidad->id, $factura->especialidad_id);
    }
}
