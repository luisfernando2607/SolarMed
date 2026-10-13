<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Agenda en línea: de dónde vino la cita y el código que recibe el paciente al reservar */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->string('origen', 10)->default('interno')->after('estado'); // interno | web
            $table->string('codigo_reserva', 8)->nullable()->unique()->after('origen');
            $table->index(['origen', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropIndex(['origen', 'estado']);
            $table->dropUnique(['codigo_reserva']);
            $table->dropColumn(['origen', 'codigo_reserva']);
        });
    }
};
