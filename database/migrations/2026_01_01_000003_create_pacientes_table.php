<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('cedula', 13)->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->enum('sexo', ['masculino', 'femenino', 'otro']);
            $table->string('telefono', 15)->nullable();
            $table->string('telefono_secundario', 15)->nullable();
            $table->text('direccion')->nullable();
            $table->string('ciudad', 60)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('foto_path', 255)->nullable();
            // Clínicos base
            $table->enum('grupo_sanguineo', ['A+','A-','B+','B-','AB+','AB-','O+','O-'])->nullable();
            $table->text('alergias')->nullable();
            $table->text('antecedentes')->nullable();
            // Gineco-obstétricos
            $table->date('fum')->nullable();
            $table->unsignedTinyInteger('gestas')->nullable();
            $table->unsignedTinyInteger('partos')->nullable();
            $table->unsignedTinyInteger('cesareas')->nullable();
            $table->unsignedTinyInteger('abortos')->nullable();
            $table->string('metodo_anticonceptivo', 100)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('pacientes'); }
};
