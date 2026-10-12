<?php

namespace Tests\Feature\Clinica;

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class RespaldoTest extends TestCase
{
    private string $destino;

    protected function setUp(): void
    {
        parent::setUp();
        $this->destino = sys_get_temp_dir() . '/solarmed-respaldo-test-' . uniqid();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.password' => 'secreto-db',
            'respaldo.ruta' => $this->destino,
            'respaldo.password' => 'clave-respaldo',
            'respaldo.dias_retencion' => 30,
        ]);
        // Ninguna prueba de respaldos debe ejecutar procesos reales (mysqldump, tar, openssl)
        Process::preventStrayProcesses();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->destino);
        parent::tearDown();
    }

    public function test_vuelca_empaqueta_y_cifra_sin_exponer_contrasenas(): void
    {
        Process::fake();

        $this->artisan('respaldo:crear')->assertSuccessful();

        Process::assertRan(fn (PendingProcess $p) => $p->command[0] === 'mysqldump'
            && in_array('--single-transaction', $p->command, true)
            && $p->environment['MYSQL_PWD'] === 'secreto-db'
            && !collect($p->command)->contains(fn ($arg) => str_contains($arg, 'secreto-db')));
        Process::assertRan(fn (PendingProcess $p) => $p->command[0] === 'tar' && in_array('--exclude=livewire-tmp', $p->command, true));
        Process::assertRan(fn (PendingProcess $p) => $p->command[0] === 'openssl'
            && $p->environment['BACKUP_PASS'] === 'clave-respaldo'
            && in_array('env:BACKUP_PASS', $p->command, true));
    }

    public function test_elimina_respaldos_mas_antiguos_que_la_retencion(): void
    {
        Process::fake();
        File::ensureDirectoryExists($this->destino);
        File::put("{$this->destino}/solarmed_viejo.tar.gz.enc", 'x');
        touch("{$this->destino}/solarmed_viejo.tar.gz.enc", now()->subDays(40)->getTimestamp());
        File::put("{$this->destino}/solarmed_reciente.tar.gz.enc", 'x');
        File::put("{$this->destino}/otro_archivo.txt", 'x');

        $this->artisan('respaldo:crear')->assertSuccessful();

        $this->assertFileDoesNotExist("{$this->destino}/solarmed_viejo.tar.gz.enc");
        $this->assertFileExists("{$this->destino}/solarmed_reciente.tar.gz.enc");
        $this->assertFileExists("{$this->destino}/otro_archivo.txt");
    }

    public function test_si_falla_el_volcado_reporta_error(): void
    {
        Process::fake(["'mysqldump'*" => Process::result(errorOutput: 'Access denied', exitCode: 2)]);

        $this->artisan('respaldo:crear')->assertFailed();
        Process::assertDidntRun(fn (PendingProcess $p) => $p->command[0] === 'tar');
    }
}
