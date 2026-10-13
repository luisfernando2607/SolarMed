<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de consentimientos para el tratamiento de datos personales dados en los formularios públicos
 * (LOPDP: los datos de salud son categoría especial y requieren consentimiento explícito y demostrable).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimientos_datos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->string('cedula', 13);
            $table->string('origen', 30);          // turno_publico | reserva_online
            $table->string('version_aviso', 20);
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['cedula', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimientos_datos');
    }
};
