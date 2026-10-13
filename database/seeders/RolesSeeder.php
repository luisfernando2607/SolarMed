<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder {
    public function run(): void {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            // Turnos
            'turnos.ver', 'turnos.gestionar',
            // Pacientes
            'pacientes.ver', 'pacientes.crear', 'pacientes.editar', 'pacientes.eliminar',
            // Expediente
            'expediente.ver', 'expediente.crear',
            // Ecografías
            'ecografias.ver', 'ecografias.crear',
            // Facturación
            'facturas.ver', 'facturas.crear', 'facturas.anular',
            // Reportes
            'reportes.ver',
            // Configuración
            'configuracion.ver', 'configuracion.editar',
            // Usuarios
            'usuarios.ver', 'usuarios.gestionar',
            // Auditoría (también se crean en la migración add_permisos_auditoria)
            'auditoria.ver', 'auditoria.gestionar',
            // Preparación de enfermería (también en la migración create_preparaciones)
            'preparacion.registrar',
            // Inventario (también en la migración create_inventario_tables)
            'inventario.ver', 'inventario.gestionar',
        ];

        foreach ($permisos as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions(Permission::all());

        $medico = Role::firstOrCreate(['name' => 'medico']);
        $medico->syncPermissions([
            'turnos.ver', 'pacientes.ver', 'pacientes.crear', 'pacientes.editar', 'pacientes.eliminar',
            'expediente.ver', 'expediente.crear', 'ecografias.ver', 'ecografias.crear',
            'facturas.ver', 'facturas.crear', 'preparacion.registrar', 'inventario.ver',
        ]);

        $secretaria = Role::firstOrCreate(['name' => 'secretaria']);
        $secretaria->syncPermissions([
            'turnos.ver', 'turnos.gestionar',
            'facturas.ver', 'facturas.crear',
        ]);

        $enfermeria = Role::firstOrCreate(['name' => 'enfermeria']);
        $enfermeria->syncPermissions(['turnos.ver', 'pacientes.ver', 'preparacion.registrar', 'inventario.ver', 'inventario.gestionar']);

        $auditor = Role::firstOrCreate(['name' => 'auditor']);
        $auditor->syncPermissions(['auditoria.ver', 'auditoria.gestionar', 'pacientes.ver', 'expediente.ver', 'ecografias.ver']);
    }
}
