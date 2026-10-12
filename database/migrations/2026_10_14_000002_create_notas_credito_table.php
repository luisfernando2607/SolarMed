<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nota de crédito electrónica (codDoc 04): anula una factura ya autorizada por el SRI.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('notas_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->unique()->constrained('facturas')->restrictOnDelete();
            $table->foreignId('medico_id')->constrained('medicos')->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('numero', 20);
            $table->string('ruc_emisor', 13)->nullable();
            $table->date('fecha');
            $table->string('motivo', 300);
            $table->decimal('subtotal', 8, 2);
            $table->decimal('valor_modificacion', 8, 2);
            $table->enum('ambiente_sri', ['1', '2'])->default('1');
            $table->string('clave_acceso', 50)->nullable();
            $table->string('numero_autorizacion', 50)->nullable();
            $table->dateTime('fecha_autorizacion')->nullable();
            $table->string('xml_enviado_path', 255)->nullable();
            $table->string('xml_firmado_path', 255)->nullable();
            $table->string('xml_autorizado_path', 255)->nullable();
            $table->string('estado_sri', 20)->default('pendiente');
            $table->text('mensajes_sri')->nullable();
            $table->timestamps();
            $table->unique(['ruc_emisor', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_credito');
    }
};
