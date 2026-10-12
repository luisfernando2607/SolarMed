<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ArchivosPaciente;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PacienteArchivo;
use App\Models\RegistroAcceso;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ArchivosPacienteTest extends TestCase
{
    use RefreshDatabase;

    private User $medico;
    private Paciente $paciente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        Storage::fake('private');

        $esp = Especialidad::create(['nombre' => 'Ginecología', 'codigo' => 'ginecologia']);
        $this->medico = User::factory()->create();
        $this->medico->assignRole('medico');
        Medico::create(['user_id' => $this->medico->id, 'nombres' => 'Carlos', 'apellidos' => 'García', 'especialidad_id' => $esp->id]);
        $this->paciente = Paciente::create(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405', 'sexo' => 'femenino']);
    }

    public function test_sube_y_descarga_un_pdf_con_auditoria(): void
    {
        Livewire::actingAs($this->medico)->test(ArchivosPaciente::class, ['paciente' => $this->paciente])
            ->set('archivo', UploadedFile::fake()->create('biometria.pdf', 120, 'application/pdf'))
            ->set('categoria', 'laboratorio')
            ->set('descripcion', 'Biometría hemática')
            ->call('subir')
            ->assertHasNoErrors()
            ->assertSee('Biometría hemática');

        $archivo = PacienteArchivo::sole();
        $this->assertStringEndsWith('.pdf', $archivo->nombre_almacenado);
        $this->assertSame($this->medico->id, $archivo->user_id);
        $this->assertNotNull($archivo->medico_id);
        Storage::disk('private')->assertExists($archivo->ruta);

        $this->actingAs($this->medico)->get(route('pacientes.archivo', $archivo))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');

        $this->assertEqualsCanonicalizing(['ver', 'crear', 'descargar'], RegistroAcceso::pluck('accion')->all());
    }

    public function test_rechaza_formatos_no_permitidos_aunque_tengan_extension_pdf(): void
    {
        Livewire::actingAs($this->medico)->test(ArchivosPaciente::class, ['paciente' => $this->paciente])
            ->set('archivo', UploadedFile::fake()->create('resultado.pdf', 10, 'application/x-msdownload'))
            ->call('subir')
            ->assertHasErrors(['archivo' => 'mimetypes']);

        $this->assertSame(0, PacienteArchivo::count());
    }

    public function test_solo_quien_subio_o_admin_puede_eliminar(): void
    {
        $componente = Livewire::actingAs($this->medico)->test(ArchivosPaciente::class, ['paciente' => $this->paciente])
            ->set('archivo', UploadedFile::fake()->create('eco.pdf', 50, 'application/pdf'))
            ->call('subir');
        $archivo = PacienteArchivo::sole();

        $otro = User::factory()->create();
        $otro->assignRole('medico');
        Livewire::actingAs($otro)->test(ArchivosPaciente::class, ['paciente' => $this->paciente])
            ->call('eliminar', $archivo->id)
            ->assertForbidden();

        $this->actingAs($this->medico);
        $componente->call('eliminar', $archivo->id);
        $this->assertSame(0, PacienteArchivo::count());
        Storage::disk('private')->assertMissing($archivo->ruta);
    }

    public function test_secretaria_no_accede_a_los_archivos(): void
    {
        $secretaria = User::factory()->create();
        $secretaria->assignRole('secretaria');

        $this->actingAs($secretaria)->get(route('pacientes.archivos', $this->paciente))->assertForbidden();
    }
}
