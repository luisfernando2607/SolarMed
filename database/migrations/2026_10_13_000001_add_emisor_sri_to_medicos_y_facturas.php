<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cada médico factura con su propio RUC: datos tributarios por médico y numeración por RUC emisor.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('medicos', function (Blueprint $table) {
            $table->string('ruc', 13)->nullable()->after('apellidos');
            $table->string('razon_social', 300)->nullable()->after('ruc');
            $table->string('nombre_comercial', 300)->nullable()->after('razon_social');
            $table->string('direccion_matriz', 300)->nullable()->after('nombre_comercial');
            $table->enum('obligado_contabilidad', ['SI', 'NO'])->default('NO')->after('direccion_matriz');
            $table->string('regimen_rimpe', 60)->nullable()->after('obligado_contabilidad');
            $table->string('sri_establecimiento', 3)->default('001')->after('regimen_rimpe');
            $table->string('sri_pto_emi', 3)->default('001')->after('sri_establecimiento');
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->string('ruc_emisor', 13)->nullable()->after('numero_factura');
        });

        // Las facturas existentes se emitieron con el RUC configurado para la clínica
        $rucClinica = DB::table('configuracion')->where('clave', 'sri_ruc')->value('valor');
        if ($rucClinica) {
            DB::table('facturas')->whereNull('ruc_emisor')->update(['ruc_emisor' => $rucClinica]);
        }

        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique(['numero_factura']);
            $table->unique(['ruc_emisor', 'numero_factura']);
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropUnique(['ruc_emisor', 'numero_factura']);
            $table->unique('numero_factura');
            $table->dropColumn('ruc_emisor');
        });

        Schema::table('medicos', function (Blueprint $table) {
            $table->dropColumn([
                'ruc', 'razon_social', 'nombre_comercial', 'direccion_matriz',
                'obligado_contabilidad', 'regimen_rimpe', 'sri_establecimiento', 'sri_pto_emi',
            ]);
        });
    }
};
