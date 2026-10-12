<?php

namespace Tests\Feature\Clinica;

use App\Livewire\BusquedaGlobal;
use App\Livewire\ListaPacientes;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Factura;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FichaPacienteTest extends TestCase
{
    use RefreshDatabase;

    private Medico $medico;
    private Paciente $ana;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $gine = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $this->medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $gine->id]);
        $this->ana = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'alergias' => 'Penicilina', 'telefono' => '0991234567']);
        Paciente::create(['nombres' => 'Beto', 'apellidos' => 'Ríos', 'cedula' => '0911111111', 'sexo' => 'masculino']);
        ExpedienteConsulta::create(['paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $gine->id,
            'tipo_consulta' => 'ginecologica', 'fecha' => now(), 'motivo_consulta' => 'Dolor pélvico', 'diagnostico' => 'Quiste ovárico']);
    }

    public function test_medico_ve_la_ficha_completa(): void
    {
        $this->actingAs($this->medico->user)->get(route('pacientes.ver', $this->ana))
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('Penicilina')
            ->assertSee('Quiste ovárico')
            ->assertSee('Historia clínica');
    }

    public function test_enfermeria_no_ve_datos_clinicos(): void
    {
        $enf = User::factory()->create(); $enf->assignRole('enfermeria');

        $this->actingAs($enf)->get(route('pacientes.ver', $this->ana))
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('0991234567')
            ->assertDontSee('Penicilina')
            ->assertDontSee('Quiste ovárico')
            ->assertDontSee(route('pacientes.pdf', $this->ana));
    }

    public function test_sin_permiso_de_pacientes_no_entra(): void
    {
        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('pacientes.ver', $this->ana))->assertForbidden();
    }

    public function test_editar_desde_la_ficha_abre_el_formulario(): void
    {
        $this->actingAs($this->medico->user);
        Livewire::withQueryParams(['editar' => $this->ana->id])->test(ListaPacientes::class)
            ->assertSet('editMode', true)
            ->assertSet('nombres', 'Ana');

        $enf = User::factory()->create(); $enf->assignRole('enfermeria');
        Livewire::actingAs($enf)->withQueryParams(['editar' => $this->ana->id])->test(ListaPacientes::class)
            ->assertSet('editMode', false);
    }

    public function test_busqueda_global_encuentra_por_varias_palabras_y_cedula(): void
    {
        Livewire::actingAs($this->medico->user)->test(BusquedaGlobal::class)
            ->set('q', 'ana pér')
            ->assertSee('Ana Pérez')
            ->assertDontSee('Beto Ríos')
            ->set('q', '0911')
            ->assertSee('Beto Ríos')
            ->set('q', 'agenda')
            ->assertSee('Agenda');
    }

    public function test_busqueda_global_respeta_permisos(): void
    {
        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        Factura::create(['numero_factura' => '001-001-000000123', 'paciente_id' => $this->ana->id, 'medico_id' => $this->medico->id, 'especialidad_id' => $this->medico->especialidad_id, 'user_id' => $sec->id, 'fecha' => today(),
            'subtotal' => 30, 'descuento' => 0, 'total' => 30, 'forma_pago' => 'efectivo', 'estado' => 'pagada']);

        Livewire::actingAs($sec)->test(BusquedaGlobal::class)
            ->set('q', 'Ana')
            ->assertDontSee('0102030405')
            ->set('q', '000000123')
            ->assertSee('001-001-000000123')
            ->set('q', 'auditor')
            ->assertDontSee('Auditoría');
    }
}
