<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('ocupacion', 100)->nullable()->after('email');
            $table->string('referido_por', 100)->nullable()->after('email');
            $table->decimal('peso', 5, 2)->nullable()->after('grupo_sanguineo');
            $table->decimal('altura', 3, 2)->nullable()->after('peso');
            $table->text('medicamentos')->nullable()->after('altura');
            $table->text('cirugias')->nullable()->after('medicamentos');
            $table->text('enfermedades_familiares')->nullable()->after('antecedentes');
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn(['ocupacion', 'referido_por', 'peso', 'altura', 'medicamentos', 'cirugias', 'enfermedades_familiares']);
        });
    }
};
