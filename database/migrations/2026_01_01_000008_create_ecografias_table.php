<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('ecografias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->date('fecha');
            $table->string('indicacion', 255)->nullable();
            $table->string('semanas_gestacion', 20)->nullable();
            $table->date('fum')->nullable();
            $table->date('fpp')->nullable();
            $table->string('presentacion', 50)->nullable();
            $table->unsignedSmallInteger('lcf')->nullable();
            $table->string('placenta', 100)->nullable();
            $table->string('liquido_amniotico', 50)->nullable();
            $table->decimal('dbp', 4, 1)->nullable();
            $table->decimal('cc', 5, 1)->nullable();
            $table->decimal('ca', 5, 1)->nullable();
            $table->decimal('lf', 4, 1)->nullable();
            $table->unsignedSmallInteger('peso_fetal_estimado')->nullable();
            $table->text('conclusion')->nullable();
            $table->string('imagen_path', 255)->nullable();
            $table->string('pdf_path', 255)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('ecografias'); }
};
