<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('xml_firmado_path', 255)->nullable()->after('xml_enviado_path');
            $table->dateTime('fecha_autorizacion')->nullable()->after('numero_autorizacion');
            $table->text('mensajes_sri')->nullable()->after('estado_sri');
        });

        // Antes el XML firmado se guardaba en xml_autorizado_path
        DB::table('facturas')
            ->whereNotNull('xml_autorizado_path')
            ->where('estado_sri', '!=', 'autorizado')
            ->update([
                'xml_firmado_path' => DB::raw('xml_autorizado_path'),
                'xml_autorizado_path' => null,
            ]);
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn(['xml_firmado_path', 'fecha_autorizacion', 'mensajes_sri']);
        });
    }
};
