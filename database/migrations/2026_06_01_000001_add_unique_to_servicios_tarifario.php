<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('servicios_tarifario', function (Blueprint $table) {
            $table->unique(['nombre', 'especialidad_id'], 'servicios_tarifario_nombre_especialidad_unique');
        });
    }

    public function down(): void
    {
        Schema::table('servicios_tarifario', function (Blueprint $table) {
            $table->dropUnique('servicios_tarifario_nombre_especialidad_unique');
        });
    }
};
