<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes sobre la historia clínica: copias certificadas (AM 5216-A Arts. 28-30, 48 h),
 * entregas para investigación o docencia (Art. 12) y derechos del titular (LOPDP, 15 días).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_informacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->string('tipo', 30);
            $table->string('solicitante_nombres', 150);
            $table->string('solicitante_cedula', 20);
            $table->string('relacion', 30);
            $table->string('entidad', 150)->nullable();
            $table->text('motivo')->nullable();
            $table->string('documento_ruta')->nullable();
            $table->string('documento_nombre')->nullable();
            $table->string('documento_mime', 50)->nullable();
            $table->dateTime('fecha_solicitud');
            $table->dateTime('fecha_limite');
            $table->string('estado', 20)->default('pendiente');
            $table->dateTime('fecha_respuesta')->nullable();
            $table->text('respuesta')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atendido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'fecha_limite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_informacion');
    }
};
