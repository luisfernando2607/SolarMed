<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos del formulario SNS-MSP/HCU-form.002/2021 que faltaban (AM 00115-2021):
 * B. primera/subsecuente, G. revisión actual de órganos y sistemas, I. diagnóstico presuntivo/definitivo.
 * Nulos para no invalidar las consultas ya registradas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expediente_consultas', function (Blueprint $table) {
            $table->string('tipo_atencion', 12)->nullable()->after('tipo_consulta');
            $table->text('revision_sistemas')->nullable()->after('anamnesis');
            $table->string('tipo_diagnostico', 12)->nullable()->after('codigo_cie10');
        });
    }

    public function down(): void
    {
        Schema::table('expediente_consultas', function (Blueprint $table) {
            $table->dropColumn(['tipo_atencion', 'revision_sistemas', 'tipo_diagnostico']);
        });
    }
};
