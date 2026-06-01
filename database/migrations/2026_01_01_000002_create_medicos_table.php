<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->string('colegiatura', 30)->nullable();
            $table->string('telefono', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('firma_path', 255)->nullable();
            $table->json('horario')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('medicos'); }
};
