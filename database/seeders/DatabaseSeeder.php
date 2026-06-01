<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            RolesSeeder::class,
            EspecialidadesSeeder::class,
            ConfiguracionSeeder::class,
            TarifarioSeeder::class,
            AdminSeeder::class,
            UsuariosSeeder::class,
            Cie10Seeder::class,
            DatosDemoSeeder::class,
        ]);
    }
}
