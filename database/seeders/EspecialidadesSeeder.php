<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspecialidadesSeeder extends Seeder {
    public function run(): void {
        DB::table('especialidades')->insertOrIgnore([
            ['nombre' => 'Medicina General',          'codigo' => 'general',    'color_agenda' => '#3B82F6', 'activo' => 1],
            ['nombre' => 'Ginecología y Obstetricia', 'codigo' => 'ginecologia','color_agenda' => '#EC4899', 'activo' => 1],
        ]);
    }
}
