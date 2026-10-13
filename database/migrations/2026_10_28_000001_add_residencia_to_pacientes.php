<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lugar de residencia actual (formulario 053, bloque A). La provincia ya existía. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('canton', 60)->nullable()->after('provincia');
            $table->string('parroquia', 60)->nullable()->after('canton');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', fn (Blueprint $table) => $table->dropColumn(['canton', 'parroquia']));
    }
};
