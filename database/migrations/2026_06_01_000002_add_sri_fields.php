<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('cedula', 13)->nullable()->after('email');
        });

        Schema::table('medicos', function (Blueprint $table) {
            $table->string('p12_path', 255)->nullable()->after('activo');
            $table->text('p12_password_encrypted')->nullable()->after('p12_path');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->string('clave_acceso', 50)->nullable()->after('numero_factura');
            $table->string('numero_autorizacion', 50)->nullable()->after('clave_acceso');
            $table->enum('ambiente_sri', ['1', '2'])->default('1')->after('numero_autorizacion');
            $table->string('xml_enviado_path', 255)->nullable()->after('ambiente_sri');
            $table->string('xml_autorizado_path', 255)->nullable()->after('xml_enviado_path');
            $table->string('estado_sri', 20)->default('pendiente')->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cedula');
        });
        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn(['p12_path', 'p12_password_encrypted']);
        });
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'clave_acceso', 'numero_autorizacion', 'ambiente_sri',
                'xml_enviado_path', 'xml_autorizado_path', 'estado_sri',
            ]);
        });
    }
};
