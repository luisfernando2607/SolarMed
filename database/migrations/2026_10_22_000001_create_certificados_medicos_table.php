<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('certificados_medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('tipo', ['atencion', 'reposo']);
            $table->date('fecha_emision');
            $table->date('fecha_atencion');
            $table->string('hora_atencion', 5)->nullable();
            $table->unsignedSmallInteger('dias_reposo')->nullable();
            $table->date('reposo_desde')->nullable();
            $table->date('reposo_hasta')->nullable();
            $table->boolean('mostrar_diagnostico')->default(true);
            $table->string('diagnostico', 500)->nullable();
            $table->string('codigo_cie10', 10)->nullable();
            $table->text('observaciones')->nullable();
            $table->string('codigo_verificacion', 16)->unique();
            $table->timestamps();
            $table->index(['paciente_id', 'fecha_emision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados_medicos');
    }
};
