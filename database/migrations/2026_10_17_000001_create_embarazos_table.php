<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada embarazo agrupa sus controles prenatales: una paciente puede tener varios embarazos en el tiempo.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('embarazos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->date('fum');
            $table->date('fpp');
            $table->enum('estado', ['activo', 'finalizado'])->default('activo');
            $table->date('fecha_fin')->nullable();
            $table->string('desenlace', 30)->nullable();
            $table->json('checklist')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['paciente_id', 'estado']);
        });

        Schema::table('controles_prenatales', function (Blueprint $table) {
            $table->foreignId('embarazo_id')->nullable()->after('id')->constrained('embarazos')->restrictOnDelete();
            $table->unsignedTinyInteger('dias_gestacion')->nullable()->after('semanas_gestacion');
        });
    }

    public function down(): void
    {
        Schema::table('controles_prenatales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('embarazo_id');
            $table->dropColumn('dias_gestacion');
        });
        Schema::dropIfExists('embarazos');
    }
};
