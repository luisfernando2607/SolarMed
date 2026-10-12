<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría de la calidad del llenado de la historia clínica y planes de acción (MSP):
 * la evaluación queda cerrada e inmutable; los planes de acción siguen su propio ciclo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias_hc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consulta_id')->constrained('expediente_consultas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('fecha');
            $table->unsignedTinyInteger('puntaje')->default(0);
            $table->unsignedTinyInteger('puntaje_automatico')->default(0);
            $table->json('resultado');
            $table->text('observacion')->nullable();
            $table->string('estado', 12)->default('borrador');
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();
            $table->index(['consulta_id', 'estado']);
            $table->index('fecha');
        });

        Schema::create('planes_accion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auditoria_hc_id')->nullable()->constrained('auditorias_hc')->restrictOnDelete();
            $table->foreignId('medico_id')->nullable()->constrained('medicos')->nullOnDelete();
            $table->text('hallazgo');
            $table->text('accion');
            $table->foreignId('responsable_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_limite');
            $table->string('estado', 12)->default('pendiente');
            $table->text('evidencia')->nullable();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['estado', 'fecha_limite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_accion');
        Schema::dropIfExists('auditorias_hc');
    }
};
