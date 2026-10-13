<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Preparación (preconsulta) de enfermería: constantes vitales y antropometría antes de la consulta
 * (AM 00115-2021, definición de "Preparación"; formulario 002 bloque F).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('preparaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->nullable()->unique()->constrained('turnos')->nullOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('fecha');
            $table->unsignedSmallInteger('presion_sistolica')->nullable();
            $table->unsignedSmallInteger('presion_diastolica')->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->unsignedTinyInteger('saturacion')->nullable();
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('talla', 4, 2)->nullable();   // metros, como en la consulta
            $table->decimal('perimetro_abdominal', 5, 1)->nullable();
            $table->decimal('glucosa_capilar', 5, 1)->nullable();
            $table->decimal('hemoglobina_capilar', 4, 1)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->index(['paciente_id', 'fecha']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::firstOrCreate(['name' => 'preparacion.registrar', 'guard_name' => 'web']);
        foreach (['admin', 'medico', 'enfermeria'] as $rol) {
            Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web'])->givePermissionTo('preparacion.registrar');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('preparaciones');
        Permission::where('name', 'preparacion.registrar')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
