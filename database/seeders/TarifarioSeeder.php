<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarifarioSeeder extends Seeder {
    public function run(): void {
        $genId = DB::table('especialidades')->where('codigo','general')->value('id');
        $ginId = DB::table('especialidades')->where('codigo','ginecologia')->value('id');

        DB::table('servicios_tarifario')->upsert([
            ['especialidad_id' => $genId, 'nombre' => 'Consulta general',         'precio' => 0.00, 'activo' => 1],
            ['especialidad_id' => $genId, 'nombre' => 'Control de seguimiento',   'precio' => 0.00, 'activo' => 1],
            ['especialidad_id' => $genId, 'nombre' => 'Procedimiento menor',      'precio' => 0.00, 'activo' => 1],
            ['especialidad_id' => $ginId, 'nombre' => 'Consulta ginecológica',    'precio' => 0.00, 'activo' => 1],
            ['especialidad_id' => $ginId, 'nombre' => 'Ecografía obstétrica',     'precio' => 20.00,'activo' => 1],
            ['especialidad_id' => $ginId, 'nombre' => 'Control prenatal',         'precio' => 0.00, 'activo' => 1],
            ['especialidad_id' => $ginId, 'nombre' => 'Planificación familiar',   'precio' => 0.00, 'activo' => 1],
            ['especialidad_id' => $ginId, 'nombre' => 'Cirugía / Parto',          'precio' => 0.00, 'activo' => 1],
        ], ['nombre', 'especialidad_id'], ['precio', 'activo']);
    }
}
