<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * Respaldo completo: base de datos MySQL + storage/app/private (certificados, XML del SRI, archivos de pacientes).
 */
class CrearRespaldo extends Command
{
    protected $signature = 'respaldo:crear';

    protected $description = 'Crea un respaldo (cifrado si BACKUP_PASSWORD está definido) de la base de datos y los archivos privados';

    public function handle(): int
    {
        $conexion = config('database.connections.' . config('database.default'));
        if (($conexion['driver'] ?? null) !== 'mysql') {
            $this->error('El respaldo automático solo está implementado para MySQL.');
            return self::FAILURE;
        }

        $destino = config('respaldo.ruta');
        $marca = now()->format('Ymd_His');
        $trabajo = storage_path("app/respaldo-tmp-{$marca}");
        File::ensureDirectoryExists($destino, 0700);
        File::ensureDirectoryExists($trabajo, 0700);

        try {
            $this->line('Volcando la base de datos...');
            Process::env(['MYSQL_PWD' => (string) $conexion['password']])
                ->timeout(1800)
                ->run([
                    'mysqldump',
                    '--host=' . $conexion['host'],
                    '--port=' . $conexion['port'],
                    '--user=' . $conexion['username'],
                    '--single-transaction', '--routines', '--triggers', '--no-tablespaces',
                    '--result-file=' . "{$trabajo}/base.sql",
                    $conexion['database'],
                ])->throw();

            $this->line('Empaquetando base de datos y archivos privados...');
            $archivo = "{$destino}/solarmed_{$marca}.tar.gz";
            Process::timeout(1800)->run([
                'tar', '-czf', $archivo,
                '--exclude=livewire-tmp',
                '-C', $trabajo, 'base.sql',
                '-C', storage_path('app'), 'private',
            ])->throw();

            if ($password = config('respaldo.password')) {
                $this->line('Cifrando...');
                Process::env(['BACKUP_PASS' => $password])->timeout(1800)->run([
                    'openssl', 'enc', '-aes-256-cbc', '-pbkdf2', '-salt',
                    '-in', $archivo, '-out', "{$archivo}.enc", '-pass', 'env:BACKUP_PASS',
                ])->throw();
                File::delete($archivo);
                $archivo .= '.enc';
            }

            @chmod($archivo, 0600);
            $eliminados = $this->aplicarRetencion($destino);

            $tamano = File::exists($archivo) ? round(File::size($archivo) / 1048576, 2) . ' MB' : '?';
            $this->info("Respaldo creado: {$archivo} ({$tamano})" . ($eliminados ? ". Respaldos antiguos eliminados: {$eliminados}" : ''));
            Log::info('Respaldo creado', ['archivo' => $archivo, 'tamano' => $tamano, 'cifrado' => (bool) config('respaldo.password')]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Falló el respaldo: ' . $e->getMessage());
            $this->error('Falló el respaldo: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            File::deleteDirectory($trabajo);
        }
    }

    private function aplicarRetencion(string $destino): int
    {
        $limite = now()->subDays(config('respaldo.dias_retencion'))->getTimestamp();
        $eliminados = 0;

        foreach (File::glob("{$destino}/solarmed_*.tar.gz*") as $viejo) {
            if (File::lastModified($viejo) < $limite) {
                File::delete($viejo);
                $eliminados++;
            }
        }

        return $eliminados;
    }
}
