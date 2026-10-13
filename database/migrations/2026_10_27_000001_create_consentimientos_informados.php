<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consentimiento informado, formulario SNS-MSP/HCU-form.024 (AM 00115-2021).
 * Las plantillas guardan los textos que redacta cada profesional para reutilizarlos;
 * no se precargan contenidos médicos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_consentimiento', function (Blueprint $table) {
            $table->id();
            $table->string('procedimiento', 200);
            $table->text('en_que_consiste')->nullable();
            $table->text('como_se_realiza')->nullable();
            $table->string('duracion_estimada', 100)->nullable();
            $table->text('beneficios')->nullable();
            $table->text('riesgos_frecuentes')->nullable();
            $table->text('riesgos_graves')->nullable();
            $table->text('alternativas')->nullable();
            $table->text('manejo_posterior')->nullable();
            $table->text('consecuencias_no_realizar')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consentimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Bloque B
            $table->string('servicio', 120)->nullable();
            $table->enum('tipo_atencion', ['ambulatorio', 'hospitalizacion'])->default('ambulatorio');
            $table->string('diagnostico', 500)->nullable();
            $table->string('codigo_cie10', 10)->nullable();
            $table->string('procedimiento', 200);
            $table->text('en_que_consiste');
            $table->text('como_se_realiza')->nullable();
            $table->string('grafico_ruta')->nullable();
            $table->string('duracion_estimada', 100)->nullable();
            $table->text('beneficios')->nullable();
            $table->text('riesgos_frecuentes')->nullable();
            $table->text('riesgos_graves')->nullable();
            $table->text('riesgos_especificos')->nullable();
            $table->text('alternativas')->nullable();
            $table->text('manejo_posterior')->nullable();
            $table->text('consecuencias_no_realizar')->nullable();
            $table->date('fecha_informacion');
            $table->string('hora_informacion', 5)->nullable();

            // Bloques C, D y E
            $table->enum('estado', ['pendiente_firma', 'aceptado', 'negado', 'revocado'])->default('pendiente_firma');
            $table->string('representante_nombre', 150)->nullable();
            $table->string('representante_cedula', 20)->nullable();
            $table->string('representante_parentesco', 60)->nullable();
            $table->boolean('negativa_sin_firma')->default(false);
            $table->string('testigo_nombre', 150)->nullable();
            $table->string('testigo_cedula', 20)->nullable();
            $table->dateTime('fecha_aceptacion')->nullable();
            $table->dateTime('fecha_negativa')->nullable();
            $table->dateTime('fecha_revocatoria')->nullable();
            $table->string('motivo_revocatoria', 500)->nullable();

            // Copia escaneada del formulario firmado en papel
            $table->string('documento_firmado_ruta')->nullable();
            $table->string('documento_firmado_mime', 60)->nullable();
            $table->dateTime('documento_firmado_at')->nullable();

            $table->timestamps();
            $table->index(['paciente_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos');
        Schema::dropIfExists('plantillas_consentimiento');
    }
};
