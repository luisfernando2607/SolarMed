<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de accesos a datos de salud (LOPDP): quién vio, descargó o modificó qué y cuándo.
 * Solo se insertan filas; nunca se actualizan ni se borran desde la aplicación.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('registro_accesos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('accion', 30);
            $table->string('recurso', 30);
            $table->unsignedBigInteger('recurso_id')->nullable();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->string('detalle', 255)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['paciente_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_accesos');
    }
};
