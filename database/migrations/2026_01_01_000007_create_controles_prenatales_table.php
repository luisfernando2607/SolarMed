<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('controles_prenatales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->date('fecha');
            $table->unsignedTinyInteger('semanas_gestacion')->nullable();
            $table->date('fpp')->nullable();
            $table->decimal('peso_materno', 5, 2)->nullable();
            $table->string('presion_arterial', 10)->nullable();
            $table->decimal('altura_uterina', 4, 1)->nullable();
            $table->unsignedSmallInteger('fcf')->nullable();
            $table->string('presentacion', 50)->nullable();
            $table->boolean('movimientos_fetales')->nullable();
            $table->string('edemas', 60)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('controles_prenatales'); }
};
