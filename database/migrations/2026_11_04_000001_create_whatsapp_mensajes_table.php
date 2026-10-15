<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Envío de WhatsApp por la API oficial de Meta (WhatsApp Business Cloud API): historial de mensajes,
 * baja voluntaria del paciente y marca del recordatorio enviado por este canal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->foreignId('cita_id')->nullable()->constrained('citas')->nullOnDelete();
            $table->foreignId('encuesta_id')->nullable()->constrained('encuestas')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo', 30);               // recordatorio_cita | confirmacion_reserva | encuesta | prueba
            $table->string('telefono', 15);
            $table->string('plantilla', 100);
            $table->json('parametros')->nullable();
            $table->string('estado', 15)->default('pendiente'); // pendiente | enviado | entregado | leido | fallido
            $table->string('wamid', 100)->nullable()->unique();
            $table->string('error_codigo', 20)->nullable();
            $table->string('error_mensaje', 255)->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('entregado_at')->nullable();
            $table->timestamp('leido_at')->nullable();
            $table->text('respuesta_texto')->nullable();
            $table->string('respuesta_payload', 100)->nullable();
            $table->timestamp('respondido_at')->nullable();
            $table->timestamp('respuesta_revisada_at')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
            $table->index(['telefono', 'created_at']);
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->boolean('whatsapp_baja')->default(false);
            $table->timestamp('whatsapp_baja_at')->nullable();
        });

        Schema::table('citas', function (Blueprint $table) {
            $table->timestamp('whatsapp_recordatorio_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_mensajes');
        Schema::table('pacientes', fn (Blueprint $t) => $t->dropColumn(['whatsapp_baja', 'whatsapp_baja_at']));
        Schema::table('citas', fn (Blueprint $t) => $t->dropColumn('whatsapp_recordatorio_at'));
    }
};
