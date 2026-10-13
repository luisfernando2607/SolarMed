<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Referencia, derivación, contrarreferencia y referencia inversa (SNS-MSP/HCU-form.053/2021) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // saliente: esta clínica refiere al paciente; entrante: el paciente llega referido y la clínica contrarrefiere
            $table->enum('direccion', ['saliente', 'entrante'])->default('saliente');
            $table->string('establecimiento_origen', 160)->nullable();
            $table->string('institucion_origen', 60)->nullable();
            $table->string('profesional_origen', 160)->nullable();

            // Sección referencia (bloques A–G)
            $table->enum('tipo', ['referencia', 'derivacion'])->default('referencia');
            $table->unsignedTinyInteger('motivo')->nullable();
            $table->string('institucion_destino', 60)->nullable();
            $table->string('establecimiento_destino', 160)->nullable();
            $table->string('servicio', 120)->nullable();
            $table->string('especialidad', 120)->nullable();
            $table->text('resumen_cuadro_clinico')->nullable();
            $table->text('hallazgos')->nullable();
            $table->json('diagnosticos')->nullable();
            $table->dateTime('fecha');
            $table->boolean('referencia_justificada')->nullable();
            $table->boolean('derivacion_justificada')->nullable();

            // Sección contrarreferencia / referencia inversa (bloques A–H)
            $table->enum('contra_tipo', ['contrarreferencia', 'referencia_inversa'])->nullable();
            $table->string('contra_establecimiento', 160)->nullable();
            $table->string('contra_institucion', 60)->nullable();
            $table->string('contra_distrito', 30)->nullable();
            $table->dateTime('contra_fecha')->nullable();
            $table->text('contra_resumen')->nullable();
            $table->text('contra_hallazgos')->nullable();
            $table->text('contra_tratamientos_realizados')->nullable();
            $table->json('contra_diagnosticos')->nullable();
            $table->text('contra_tratamiento_recomendado')->nullable();
            $table->string('contra_profesional', 160)->nullable();
            $table->string('contra_profesional_cedula', 13)->nullable();
            $table->foreignId('contra_medico_id')->nullable()->constrained('medicos')->nullOnDelete();
            $table->string('documento_respuesta_ruta')->nullable();
            $table->string('documento_respuesta_nombre')->nullable();
            $table->string('documento_respuesta_mime', 60)->nullable();

            $table->enum('estado', ['enviada', 'contrarreferida', 'cerrada'])->default('enviada');
            $table->timestamps();
            $table->index(['paciente_id', 'fecha']);
            $table->index(['estado', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referencias');
    }
};
