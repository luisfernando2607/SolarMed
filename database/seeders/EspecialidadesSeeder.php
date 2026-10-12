<?php
namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadesSeeder extends Seeder {
    public function run(): void {
        $base = [
            'general' => [
                'nombre' => 'Medicina General', 'prefijo_turno' => 'G', 'color_agenda' => '#3B82F6',
                'motivos' => ['Consulta general', 'Control de seguimiento', 'Dolor de cabeza / fiebre', 'Dolor abdominal',
                    'Infección respiratoria', 'Vacunación', 'Certificado médico', 'Otro'],
            ],
            'ginecologia' => [
                'nombre' => 'Ginecología y Obstetricia', 'prefijo_turno' => 'O', 'color_agenda' => '#EC4899',
                'motivos' => ['Consulta ginecológica', 'Ecografía obstétrica', 'Control prenatal', 'Planificación familiar',
                    'Estudio de infertilidad', 'Control de menopausia', 'Infección ginecológica', 'Papanicolaou / tamizaje', 'Otro'],
            ],
        ];

        // Solo crea las que faltan: no pisa cambios hechos desde la pantalla de Especialidades
        foreach ($base as $codigo => $datos) {
            Especialidad::firstOrCreate(['codigo' => $codigo], $datos + ['activo' => true]);
        }
    }
}
