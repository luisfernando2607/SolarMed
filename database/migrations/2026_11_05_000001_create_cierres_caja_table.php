<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cierre de caja diario: cuadra lo cobrado por forma de pago contra el efectivo contado.
 * Cada factura y nota de crédito pertenece a un solo cierre (cierre_id) para no contarla dos veces.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('desde');
            $table->dateTime('hasta');
            $table->decimal('fondo_inicial', 10, 2)->default(0);
            $table->json('cobrado');        // por forma de pago
            $table->json('devoluciones');   // notas de crédito de facturas de cierres anteriores, por forma de pago
            $table->decimal('efectivo_esperado', 10, 2);
            $table->decimal('contado_efectivo', 10, 2);
            $table->decimal('diferencia', 10, 2);  // contado − esperado (positivo = sobrante)
            $table->json('denominaciones')->nullable();
            $table->json('detalle');        // copia de las facturas y notas de crédito incluidas (para el acta)
            $table->unsignedInteger('facturas_count')->default(0);
            $table->unsignedInteger('anuladas_count')->default(0);
            $table->decimal('notas_credito_total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('cerrado'); // cerrado | reabierto
            $table->foreignId('reabierto_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reabierto_at')->nullable();
            $table->string('motivo_reapertura', 500)->nullable();
            $table->timestamps();
            $table->index(['fecha', 'estado']);
        });

        Schema::table('facturas', function (Blueprint $table) {
            $table->foreignId('cierre_id')->nullable()->after('estado')->constrained('cierres_caja')->nullOnDelete();
        });
        Schema::table('notas_credito', function (Blueprint $table) {
            $table->foreignId('cierre_id')->nullable()->constrained('cierres_caja')->nullOnDelete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'caja.cerrar', 'guard_name' => 'web']);
        foreach (['admin', 'secretaria'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web'])->givePermissionTo('caja.cerrar');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('notas_credito', fn (Blueprint $t) => $t->dropConstrainedForeignId('cierre_id'));
        Schema::table('facturas', fn (Blueprint $t) => $t->dropConstrainedForeignId('cierre_id'));
        Schema::dropIfExists('cierres_caja');
        Permission::where('name', 'caja.cerrar')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
