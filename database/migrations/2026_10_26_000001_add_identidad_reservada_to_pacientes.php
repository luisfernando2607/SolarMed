<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            // AM 5216-A Art. 13: en documentos y pantallas compartidas el nombre se reemplaza por la cédula
            $table->boolean('identidad_reservada')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('pacientes', fn (Blueprint $table) => $table->dropColumn('identidad_reservada'));
    }
};
