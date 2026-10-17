<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ReporteMorbilidad;
use App\Models\Especialidad;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\RdacaaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RdacaaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $esp = Especialidad::create(['nombre' => 'Medicina General', 'codigo' => 'general', 'prefijo_turno' => 'G']);
        $u = User::factory()->create(['cedula' => '0599999999']);
        $u->assignRole('medico');
        $medico = Medico::create(['user_id' => $u->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);

        $pac = Paciente::create([
            'nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino',
            'fecha_nacimiento' => now()->subYears(30), 'etnia' => 'mestizo', 'nacionalidad' => 'Ecuatoriana',
            'discapacidad' => 'ninguna', 'seguro' => 'iess_general', 'provincia' => 'Guayas',
        ]);

        ExpedienteConsulta::create([
            'paciente_id' => $pac->id, 'medico_id' => $medico->id, 'especialidad_id' => $esp->id,
            'tipo_consulta' => 'general', 'motivo_consulta' => 'x', 'fecha' => now()->subDay(),
            'codigo_cie10' => 'J00', 'diagnostico' => 'Resfriado común', 'tipo_atencion' => 'primera', 'tipo_diagnostico' => 'definitivo',
        ]);
    }

    public function test_el_servicio_arma_filas_con_las_columnas_de_los_bloques(): void
    {
        $filas = app(RdacaaService::class)->filas(now()->subDays(5)->startOfDay(), now()->endOfDay());

        $this->assertCount(1, $filas);
        $fila = $filas->first();
        $this->assertCount(count(RdacaaService::CABECERA), $fila, 'cada fila tiene tantas columnas como la cabecera');

        $texto = implode('|', $fila);
        $this->assertStringContainsString('0102030405', $texto);   // bloque C: HC
        $this->assertStringContainsString('Mestizo/a', $texto);    // étnia legible
        $this->assertStringContainsString('IESS seguro general', $texto); // seguro legible
        $this->assertStringContainsString('J00', $texto);          // bloque D: CIE-10
        $this->assertStringContainsString('Primera vez', $texto);  // tipo de atención
        $this->assertStringContainsString('Definitivo', $texto);   // condición del diagnóstico
    }

    public function test_exporta_rdacaa_y_queda_registrado(): void
    {
        $this->actingAs($this->admin);
        $r = Livewire::test(ReporteMorbilidad::class)
            ->set('desde', now()->subDays(10)->toDateString())
            ->call('exportarRdacaa');

        $r->assertFileDownloaded();
        $this->assertSame('exportar', RegistroAcceso::latest('id')->value('accion'));
        $this->assertSame('rdacaa', RegistroAcceso::latest('id')->value('recurso'));

        $csv = (string) base64_decode($r->effects['download']['content'] ?? '');
        $this->assertStringContainsString('Unicódigo', $csv);      // cabecera bloque A
        $this->assertStringContainsString('0102030405', $csv);
        $this->assertStringNotContainsString('Pérez', $csv);       // no expone el nombre
    }

    public function test_sin_permiso_no_entra_al_reporte(): void
    {
        $recep = User::factory()->create();
        $recep->assignRole('secretaria');

        $this->actingAs($recep)->get(route('reportes.morbilidad'))->assertForbidden();
    }
}
