<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Encuesta de satisfacción del usuario externo: una por consulta, respondida con un enlace de un solo uso */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuestas', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();
            $table->foreignId('consulta_id')->unique()->constrained('expediente_consultas')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->nullable()->constrained('especialidades')->nullOnDelete();
            $table->dateTime('expira_at');
            $table->dateTime('enviada_at')->nullable();
            $table->string('canal_envio', 20)->nullable();     // whatsapp | correo
            $table->dateTime('respondida_at')->nullable();
            $table->string('canal_respuesta', 20)->nullable(); // enlace | dispositivo
            $table->unsignedTinyInteger('p_trato')->nullable();
            $table->unsignedTinyInteger('p_espera')->nullable();
            $table->unsignedTinyInteger('p_explicacion')->nullable();
            $table->unsignedTinyInteger('p_instalaciones')->nullable();
            $table->unsignedTinyInteger('recomendacion')->nullable(); // 0 a 10
            $table->text('comentario')->nullable();
            $table->timestamps();
            $table->index(['medico_id', 'respondida_at']);
            $table->index('respondida_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuestas');
    }
};
