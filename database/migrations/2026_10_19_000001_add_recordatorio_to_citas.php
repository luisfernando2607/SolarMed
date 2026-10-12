<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dateTime('recordatorio_enviado_at')->nullable()->after('notas');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('recordatorio_enviado_at');
        });
    }
};
