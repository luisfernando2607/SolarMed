<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del bloque C del RDACAA (Registro Diario Automatizado de Consultas y Atenciones
 * Ambulatorias del MSP) que faltaban en la ficha: autoidentificación étnica, nacionalidad,
 * discapacidad y tipo de seguro. Permiten emitir el reporte RDACAA al Ministerio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('etnia', 40)->nullable()->after('sexo');
            $table->string('nacionalidad', 60)->nullable()->after('etnia');
            $table->string('discapacidad', 40)->nullable()->after('nacionalidad');
            $table->string('seguro', 60)->nullable()->after('discapacidad');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['etnia', 'nacionalidad', 'discapacidad', 'seguro']);
        });
    }
};
