<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ReporteMorbilidad;
use App\Models\Cie10Code;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReporteMorbilidadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create(); $this->admin->assignRole('admin');
        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia', 'prefijo_turno' => 'O']);
        $u = User::factory()->create(); $u->assignRole('medico');
        $medico = Medico::create(['user_id' => $u->id, 'nombres' => 'María', 'apellidos' => 'Cuadros', 'especialidad_id' => $esp->id]);
        Cie10Code::create(['codigo' => 'N39.0', 'descripcion' => 'Infección de vías urinarias', 'categoria' => 'N39', 'capitulo' => 'XIV']);

        $ana = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino', 'fecha_nacimiento' => now()->subYears(30)]);
        $nino = Paciente::create(['nombres' => 'Beto', 'apellidos' => 'Ríos', 'cedula' => '0911111111', 'sexo' => 'masculino', 'fecha_nacimiento' => now()->subYears(3)]);
        $base = ['medico_id' => $medico->id, 'especialidad_id' => $esp->id, 'tipo_consulta' => 'general', 'motivo_consulta' => 'x'];
        ExpedienteConsulta::create($base + ['paciente_id' => $ana->id, 'fecha' => now()->subDays(2), 'codigo_cie10' => 'N39.0', 'diagnostico' => 'IVU', 'tipo_atencion' => 'primera']);
        ExpedienteConsulta::create($base + ['paciente_id' => $ana->id, 'fecha' => now()->subDay(), 'codigo_cie10' => 'n39.0', 'diagnostico' => 'IVU', 'tipo_atencion' => 'subsecuente']);
        ExpedienteConsulta::create($base + ['paciente_id' => $nino->id, 'fecha' => now()->subDay(), 'diagnostico' => 'Control']);
        ExpedienteConsulta::create($base + ['paciente_id' => $nino->id, 'fecha' => now()->subMonths(3), 'diagnostico' => 'Fuera del período']);
    }

    public function test_cuenta_atenciones_por_tipo_edad_sexo_y_diagnostico(): void
    {
        $c = Livewire::actingAs($this->admin)->test(ReporteMorbilidad::class)
            ->set('desde', now()->subDays(10)->toDateString())->set('hasta', now()->toDateString());

        $c->assertViewHas('kpis', fn ($k) => $k['total'] === 3 && $k['pacientes'] === 2 && $k['primera'] === 1 && $k['subsecuente'] === 1 && $k['sin_tipo'] === 1 && $k['con_cie'] === 2)
            ->assertViewHas('matriz', fn ($m) => $m['20 a 49']['M'] === 2 && $m['1 a 4']['H'] === 1)
            ->assertViewHas('top', fn ($t) => $t->count() === 1 && $t[0]['codigo'] === 'N39.0' && $t[0]['total'] === 2)
            ->assertSee('Infección de vías urinarias');
    }

    public function test_exporta_sin_nombres_y_queda_registrado(): void
    {
        $this->actingAs($this->admin);
        $r = Livewire::test(ReporteMorbilidad::class)->set('desde', now()->subDays(10)->toDateString())->call('exportar');
        $r->assertFileDownloaded();
        $this->assertSame('exportar', RegistroAcceso::latest('id')->value('accion'));

        $csv = (string) base64_decode($r->effects['download']['content'] ?? '');
        $this->assertStringContainsString('0102030405', $csv);
        $this->assertStringNotContainsString('Pérez', $csv);
    }

    public function test_permisos(): void
    {
        $auditor = User::factory()->create(); $auditor->assignRole('auditor');
        $this->actingAs($auditor)->get(route('reportes.morbilidad'))->assertOk();

        $sec = User::factory()->create(); $sec->assignRole('secretaria');
        $this->actingAs($sec)->get(route('reportes.morbilidad'))->assertForbidden();
    }
}
