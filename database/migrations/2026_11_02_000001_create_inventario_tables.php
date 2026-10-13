<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Inventario de insumos y medicamentos de la clínica: productos, lotes con vencimiento
 * y kardex de movimientos (solo inserciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->enum('tipo', ['medicamento', 'insumo', 'material'])->default('insumo');
            $table->string('dci', 150)->nullable();
            $table->string('forma_farmaceutica', 80)->nullable();
            $table->string('concentracion', 80)->nullable();
            $table->string('presentacion', 120)->nullable();
            $table->string('unidad', 40)->default('unidad');
            $table->unsignedInteger('stock_minimo')->default(0);
            $table->boolean('activo')->default(true);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->index(['activo', 'tipo']);
        });

        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->string('codigo_lote', 60);
            $table->date('fecha_vencimiento')->nullable();
            $table->unsignedInteger('cantidad')->default(0);
            $table->string('proveedor', 150)->nullable();
            $table->decimal('costo_unitario', 10, 4)->nullable();
            $table->string('registro_sanitario', 60)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['producto_id', 'fecha_vencimiento']);
        });

        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('lote_id')->nullable()->constrained('lotes')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'salida', 'ajuste', 'baja']);
            $table->string('motivo', 60);
            $table->unsignedInteger('cantidad');
            $table->integer('saldo_lote_despues');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('paciente_id')->nullable()->constrained('pacientes')->nullOnDelete();
            $table->foreignId('consulta_id')->nullable()->constrained('expediente_consultas')->nullOnDelete();
            $table->string('observacion', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['producto_id', 'created_at']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['inventario.ver', 'inventario.gestionar'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])->givePermissionTo(['inventario.ver', 'inventario.gestionar']);
        Role::firstOrCreate(['name' => 'enfermeria', 'guard_name' => 'web'])->givePermissionTo(['inventario.ver', 'inventario.gestionar']);
        Role::firstOrCreate(['name' => 'medico', 'guard_name' => 'web'])->givePermissionTo('inventario.ver');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('lotes');
        Schema::dropIfExists('productos');
        Permission::whereIn('name', ['inventario.ver', 'inventario.gestionar'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
