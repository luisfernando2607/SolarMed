<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero_turno');
            $table->string('prefijo', 5);
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->nullOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->string('nombre_temporal', 100)->nullable();
            $table->string('cedula', 13);
            $table->string('telefono', 15)->nullable();
            $table->string('motivo', 100)->nullable();
            $table->enum('estado', ['esperando','en_atencion','completado','cancelado'])->default('esperando');
            $table->date('fecha');
            $table->timestamp('hora_registro')->useCurrent();
            $table->timestamp('hora_llamado')->nullable();
            $table->timestamp('hora_fin')->nullable();
            $table->timestamps();
            $table->unique(['especialidad_id', 'fecha', 'numero_turno']);
            $table->index(['fecha', 'estado']);
        });
    }
    public function down(): void { Schema::dropIfExists('turnos'); }
};
