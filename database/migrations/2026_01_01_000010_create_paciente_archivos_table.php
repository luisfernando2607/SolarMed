<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('paciente_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->foreignId('ecografia_id')->nullable()->constrained('ecografias')->nullOnDelete();
            $table->enum('categoria', ['laboratorio','referencia','ecografia','foto','otro']);
            $table->string('nombre_original', 255);
            $table->string('nombre_almacenado', 255);
            $table->string('ruta', 500);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('tamanio_kb')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
            $table->index(['paciente_id', 'categoria']);
        });
    }
    public function down(): void { Schema::dropIfExists('paciente_archivos'); }
};
