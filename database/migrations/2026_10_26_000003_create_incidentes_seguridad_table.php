<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Vulneraciones de seguridad de datos personales (LOPDP: notificar a la autoridad en máximo 5 días) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentes_seguridad', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_deteccion');
            $table->text('descripcion');
            $table->text('datos_afectados');
            $table->unsignedInteger('pacientes_afectados')->default(0);
            $table->string('severidad', 10);
            $table->boolean('requiere_notificacion')->default(true);
            $table->text('medidas')->nullable();
            $table->dateTime('notificado_autoridad_at')->nullable();
            $table->string('estado', 15)->default('abierto');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'fecha_deteccion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentes_seguridad');
    }
};
