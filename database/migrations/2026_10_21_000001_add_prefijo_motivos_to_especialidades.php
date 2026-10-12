<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prefijo del número de turno y motivos de consulta por especialidad (antes fijos en el código por ID).
 */
return new class extends Migration {
    private const MOTIVOS = [
        'general' => [
            'Consulta general', 'Control de seguimiento', 'Dolor de cabeza / fiebre', 'Dolor abdominal',
            'Infección respiratoria', 'Vacunación', 'Certificado médico', 'Otro',
        ],
        'ginecologia' => [
            'Consulta ginecológica', 'Ecografía obstétrica', 'Control prenatal', 'Planificación familiar',
            'Estudio de infertilidad', 'Control de menopausia', 'Infección ginecológica', 'Papanicolaou / tamizaje', 'Otro',
        ],
    ];

    private const PREFIJOS = ['general' => 'G', 'ginecologia' => 'O'];

    public function up(): void
    {
        Schema::table('especialidades', function (Blueprint $table) {
            $table->string('prefijo_turno', 3)->nullable()->after('codigo');
            $table->json('motivos')->nullable()->after('color_agenda');
        });

        foreach (DB::table('especialidades')->get() as $esp) {
            DB::table('especialidades')->where('id', $esp->id)->update([
                'prefijo_turno' => self::PREFIJOS[$esp->codigo] ?? mb_strtoupper(mb_substr($esp->codigo, 0, 1)),
                'motivos' => json_encode(self::MOTIVOS[$esp->codigo] ?? ['Consulta', 'Control', 'Otro']),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('especialidades', function (Blueprint $table) {
            $table->dropColumn(['prefijo_turno', 'motivos']);
        });
    }
};
