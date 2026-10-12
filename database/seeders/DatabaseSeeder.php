<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        // Datos base: seguros de ejecutar en cualquier instalación (no borran nada)
        $this->call([
            RolesSeeder::class,
            EspecialidadesSeeder::class,
            ConfiguracionSeeder::class,
            TarifarioSeeder::class,
            Cie10Seeder::class,
            AdminSeeder::class,
        ]);

        // Usuarios y datos de demostración: solo en desarrollo local
        if (app()->environment('local')) {
            $this->call([
                UsuariosSeeder::class,
                DatosDemoSeeder::class,
            ]);
        }
    }
}
