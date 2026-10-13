<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receta médica según la Resolución ACESS-2023-0030 (Art. 5): numeración secuencial, ciudad, vigencia,
 * diagnóstico CIE, alergias, signos de alarma, recomendaciones no farmacológicas y medicamentos por DCI.
 * Las recetas anteriores conservan su texto libre en `medicamentos` y se siguen imprimiendo igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recetas', function (Blueprint $table) {
            $table->unsignedInteger('numero')->nullable()->unique()->after('id');
            $table->string('ciudad', 80)->nullable()->after('fecha');
            $table->unsignedTinyInteger('vigencia_dias')->nullable()->after('ciudad');
            $table->text('diagnostico')->nullable()->after('vigencia_dias');
            $table->string('codigo_cie10', 10)->nullable()->after('diagnostico');
            $table->text('alergias')->nullable()->after('codigo_cie10');
            $table->text('signos_alarma')->nullable()->after('observaciones');
            $table->text('recomendaciones')->nullable()->after('signos_alarma');
        });

        Schema::create('receta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $table->unsignedTinyInteger('orden')->default(0);
            $table->string('dci', 200);
            $table->string('forma_farmaceutica', 80);
            $table->string('concentracion', 80);
            $table->string('via', 60);
            $table->unsignedInteger('cantidad');
            $table->string('dosis', 150);
            $table->string('frecuencia', 150);
            $table->string('duracion', 100);
            $table->string('indicacion', 500)->nullable();
            $table->boolean('antimicrobiano')->default(false);
            $table->timestamps();
            $table->index(['receta_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receta_items');
        Schema::table('recetas', function (Blueprint $table) {
            $table->dropUnique(['numero']);
            $table->dropColumn(['numero', 'ciudad', 'vigencia_dias', 'diagnostico', 'codigo_cie10', 'alergias', 'signos_alarma', 'recomendaciones']);
        });
    }
};
