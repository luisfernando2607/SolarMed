<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->string('tipo', 60);
            $table->dateTime('fecha_hora');
            $table->unsignedSmallInteger('duracion_min')->default(20);
            $table->enum('estado', ['pendiente','confirmada','completada','cancelada'])->default('pendiente');
            $table->string('color', 7)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->index(['medico_id', 'fecha_hora']);
        });
    }
    public function down(): void { Schema::dropIfExists('citas'); }
};
