<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('medicamentos')->comment('JSON: [{medicamento, presentacion, dosis, frecuencia, duracion, indicaciones}]');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};
