<?php

namespace Tests\Feature\Clinica;

use App\Livewire\RolesPermisos;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_el_admin_ve_la_matriz_de_permisos(): void
    {
        $this->actingAs($this->admin)->get(route('roles'))
            ->assertOk()
            ->assertSee('Roles y permisos')
            ->assertSee('Emitir facturas');
    }

    public function test_el_admin_puede_dar_y_quitar_permisos_a_un_rol(): void
    {
        $enfermeria = Role::findByName('enfermeria');
        $this->assertFalse($enfermeria->hasPermissionTo('expediente.ver'));

        $componente = Livewire::actingAs($this->admin)->test(RolesPermisos::class)
            ->call('alternar', $enfermeria->id, 'expediente.ver');
        $this->assertTrue($enfermeria->fresh()->hasPermissionTo('expediente.ver'));

        $componente->call('alternar', $enfermeria->id, 'expediente.ver');
        $this->assertFalse($enfermeria->fresh()->hasPermissionTo('expediente.ver'));

        $this->assertSame(2, RegistroAcceso::where('recurso', 'rol')->where('recurso_id', $enfermeria->id)->count());
    }

    public function test_el_cambio_se_aplica_a_los_usuarios_del_rol(): void
    {
        $enfermera = User::factory()->create();
        $enfermera->assignRole('enfermeria');
        $this->assertFalse($enfermera->can('expediente.ver'));

        Livewire::actingAs($this->admin)->test(RolesPermisos::class)
            ->call('alternar', Role::findByName('enfermeria')->id, 'expediente.ver');

        $this->assertTrue($enfermera->fresh()->can('expediente.ver'));
    }

    public function test_el_rol_admin_no_se_puede_modificar(): void
    {
        $admin = Role::findByName('admin');

        Livewire::actingAs($this->admin)->test(RolesPermisos::class)
            ->call('alternar', $admin->id, 'configuracion.editar');

        $this->assertTrue($admin->fresh()->hasPermissionTo('configuracion.editar'));
        $this->assertSame(Permission::count(), $admin->fresh()->permissions()->count());
        $this->assertTrue($this->admin->fresh()->can('configuracion.editar'));
    }

    public function test_quien_no_es_admin_no_puede_entrar_ni_cambiar_permisos(): void
    {
        $medico = User::factory()->create();
        $medico->assignRole('medico');

        $this->actingAs($medico)->get(route('roles'))->assertForbidden();

        Livewire::actingAs($medico)->test(RolesPermisos::class)
            ->call('alternar', Role::findByName('medico')->id, 'configuracion.editar')
            ->assertForbidden();
        $this->assertFalse(Role::findByName('medico')->hasPermissionTo('configuracion.editar'));
    }
}
