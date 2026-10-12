<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['facturas', 'notas_credito'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dateTime('email_enviado_at')->nullable()->after('mensajes_sri');
                $table->string('email_enviado_a', 150)->nullable()->after('email_enviado_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['facturas', 'notas_credito'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn(['email_enviado_at', 'email_enviado_a']);
            });
        }
    }
};
