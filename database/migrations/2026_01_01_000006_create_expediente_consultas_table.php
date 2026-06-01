<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('expediente_consultas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->enum('tipo_consulta', ['general','ginecologica','control_prenatal']);
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('turno_id')->nullable()->constrained('turnos')->nullOnDelete();
            $table->dateTime('fecha');
            $table->text('motivo_consulta')->nullable();
            $table->text('anamnesis')->nullable();
            $table->json('examen_fisico')->nullable();
            $table->text('diagnostico')->nullable();
            $table->string('codigo_cie10', 10)->nullable();
            $table->text('tratamiento')->nullable();
            $table->text('indicaciones')->nullable();
            $table->boolean('requiere_derivacion')->default(false);
            $table->string('derivacion_especialidad', 60)->nullable();
            $table->timestamps();
            $table->index(['paciente_id', 'fecha']);
        });
    }
    public function down(): void { Schema::dropIfExists('expediente_consultas'); }
};
