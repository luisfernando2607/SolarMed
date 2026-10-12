<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quien sube un archivo puede no ser médico (p. ej. resultados que trae el paciente):
 * se registra el usuario y el médico pasa a ser opcional.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('paciente_archivos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('medico_id')->constrained()->nullOnDelete();
            $table->foreignId('medico_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('paciente_archivos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
