<?php

namespace Tests\Feature\Clinica;

use App\Livewire\ConfiguracionClinica;
use App\Models\User;
use App\Services\MarcaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class MarcaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private array $logosPrevios;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->logosPrevios = File::glob(public_path('marca/*'));
    }

    protected function tearDown(): void
    {
        foreach (array_diff(File::glob(public_path('marca/*')), $this->logosPrevios) as $f) {
            File::delete($f);
        }
        parent::tearDown();
    }

    public function test_valores_por_defecto(): void
    {
        $marca = app(MarcaService::class);
        $this->assertSame('SolarMed', $marca->nombre());
        $this->assertStringEndsWith('images/SolarMed.jpg', $marca->logoUrl());
        $this->assertFileExists($marca->logoRuta());
        $this->assertFileExists($marca->logoBnRuta());

        $this->get('/login')->assertOk()->assertSee('SolarMed');
    }

    public function test_cambiar_nombre_contacto_color_y_logo(): void
    {
        Livewire::actingAs($this->admin)->test(ConfiguracionClinica::class)
            ->set('marca_nombre', 'Clínica Santa Ana')
            ->set('marca_subtitulo', 'Pediatría y Medicina Familiar')
            ->set('marca_direccion', 'Av. Siempre Viva 123, Cuenca')
            ->set('marca_telefono', '07-2834567')
            ->set('marca_email', 'info@santaana.ec')
            ->set('marca_color', '#7C3AED')
            ->set('logo', UploadedFile::fake()->image('logo.png', 300, 120))
            ->call('guardar')
            ->assertHasNoErrors();

        $marca = app(MarcaService::class);
        $this->assertSame('Clínica Santa Ana', $marca->nombre());
        $this->assertSame('#7C3AED', $marca->color());
        $this->assertSame('Av. Siempre Viva 123, Cuenca · Tel: 07-2834567 · info@santaana.ec', $marca->contacto());
        $this->assertStringStartsWith(public_path('marca/logo-'), $marca->logoRuta());
        $this->assertFileExists($marca->logoRuta());
        $this->assertFileExists($marca->logoBnRuta());
        $this->assertStringEndsWith('.png', $marca->logoBnRuta());

        // Login, menú y formulario público usan la nueva marca
        auth()->logout();
        $this->get('/login')->assertSee('Clínica Santa Ana')->assertSee('#7C3AED', false)->assertDontSee('SolarMed Software');
        $this->get('/')->assertSee('Clínica Santa Ana')->assertSee('Pediatría y Medicina Familiar')->assertSee('07-2834567');
        $this->actingAs($this->admin)->get('/configuracion')->assertSee('Clínica Santa Ana')->assertSee($marca->logoUrl(), false);

        // PDF con la marca nueva
        $html = view('pdf.historial-paciente', ['paciente' => new \App\Models\Paciente(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => '0102030405'])])->render();
        $this->assertStringContainsString('Clínica Santa Ana', $html);
        $this->assertStringContainsString($marca->logoRuta(), $html);
    }

    public function test_restaurar_logo_original_borra_los_archivos(): void
    {
        $c = Livewire::actingAs($this->admin)->test(ConfiguracionClinica::class)
            ->set('logo', UploadedFile::fake()->image('logo.jpg', 200, 200))
            ->call('guardar')
            ->assertHasNoErrors();
        $propio = app(MarcaService::class)->logoRuta();
        $this->assertFileExists($propio);

        $c->call('quitarLogo');
        $this->assertFileDoesNotExist($propio);
        $this->assertStringEndsWith('images/SolarMed.jpg', app(MarcaService::class)->logoUrl());
    }

    public function test_rechaza_archivos_que_no_son_imagen(): void
    {
        Livewire::actingAs($this->admin)->test(ConfiguracionClinica::class)
            ->set('logo', UploadedFile::fake()->create('logo.pdf', 50, 'application/pdf'))
            ->call('guardar')
            ->assertHasErrors('logo')
            ->set('logo', UploadedFile::fake()->image('enorme.png', 4000, 4000))
            ->call('guardar')
            ->assertHasErrors(['logo' => 'dimensions']);

        $this->assertSame('SolarMed', app(MarcaService::class)->nombre());
    }
}
