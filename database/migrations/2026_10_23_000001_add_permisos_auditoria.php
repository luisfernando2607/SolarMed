<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Área de auditoría (AM 5216-A Art. 10: acceso a la HC para auditoría de la calidad de la atención).
 * Se hace como migración y no en el seeder para no pisar los permisos editados desde Roles y permisos.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // En una base nueva (pruebas) los permisos clínicos aún no existen: los crea si faltan
        foreach (['auditoria.ver', 'auditoria.gestionar', 'pacientes.ver', 'expediente.ver', 'ecografias.ver'] as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'])->givePermissionTo(['auditoria.ver', 'auditoria.gestionar']);

        // Auditor: solo lectura de historias clínicas y del registro de auditoría
        Role::firstOrCreate(['name' => 'auditor', 'guard_name' => 'web'])->syncPermissions([
            'auditoria.ver', 'auditoria.gestionar', 'pacientes.ver', 'expediente.ver', 'ecografias.ver',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'auditor')->delete();
        Permission::whereIn('name', ['auditoria.ver', 'auditoria.gestionar'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
