<?php

namespace Tests\Feature\Clinica;

use App\Jobs\LatidoCola;
use App\Livewire\EstadoSistema;
use App\Models\User;
use App\Services\EstadoSistemaService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class EstadoSistemaTest extends TestCase
{
    use RefreshDatabase;

    private string $carpeta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->carpeta = storage_path('framework/testing/respaldos-' . uniqid());
        File::ensureDirectoryExists($this->carpeta);
        config(['respaldo.ruta' => $this->carpeta, 'respaldo.password' => 'x']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->carpeta);
        parent::tearDown();
    }

    public function test_detecta_respaldo_viejo_y_servicios_detenidos(): void
    {
        $s = app(EstadoSistemaService::class);
        $this->assertSame('error', $s->chequeos()['respaldo']['estado']);

        File::put("{$this->carpeta}/solarmed_20260101_020000.tar.gz.enc", 'x');
        touch("{$this->carpeta}/solarmed_20260101_020000.tar.gz.enc", now()->subHours(60)->getTimestamp());
        $this->assertSame('error', $s->chequeos()['respaldo']['estado']);
        touch("{$this->carpeta}/solarmed_20260101_020000.tar.gz.enc", now()->subHours(2)->getTimestamp());
        $this->assertSame('ok', $s->chequeos()['respaldo']['estado']);

        Cache::forever(EstadoSistemaService::LATIDO_TAREAS, now()->subMinutes(20)->toIso8601String());
        $this->assertSame('error', $s->chequeos()['tareas']['estado']);
        Cache::forever(EstadoSistemaService::LATIDO_TAREAS, now()->toIso8601String());
        $this->assertSame('ok', $s->chequeos()['tareas']['estado']);

        (new LatidoCola)->handle();
        $this->assertSame('ok', $s->chequeos()['cola']['estado']);
    }

    public function test_configuracion_pendiente_y_acceso(): void
    {
        config(['app.debug' => true, 'mail.default' => 'log']);
        $c = app(EstadoSistemaService::class)->chequeos()['configuracion'];
        $this->assertSame('aviso', $c['estado']);
        $this->assertStringContainsString('APP_DEBUG', $c['detalle']);
        $this->assertStringContainsString('correo', $c['detalle']);

        $admin = User::factory()->create(); $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('sistema'))->assertOk()->assertSee('Estado del sistema');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Estado del sistema:');

        $medico = User::factory()->create(); $medico->assignRole('medico');
        $this->actingAs($medico)->get(route('sistema'))->assertForbidden();
        Livewire::actingAs($medico)->test(EstadoSistema::class)->call('respaldarAhora')->assertForbidden();
    }
}
