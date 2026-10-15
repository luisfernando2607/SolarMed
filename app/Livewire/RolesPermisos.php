<?php

namespace App\Livewire;

use App\Services\AuditoriaService;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Matriz de permisos por rol. El rol admin conserva siempre todos los permisos y no se puede editar,
 * así nunca queda nadie sin acceso a la administración.
 */
class RolesPermisos extends Component
{
    const ROL_PROTEGIDO = 'admin';

    const GRUPOS = [
        'turnos' => 'Turnos y agenda',
        'pacientes' => 'Pacientes',
        'expediente' => 'Historia clínica',
        'ecografias' => 'Ecografías',
        'facturas' => 'Facturación',
        'reportes' => 'Reportes',
        'configuracion' => 'Configuración',
        'usuarios' => 'Usuarios',
        'auditoria' => 'Auditoría',
        'inventario' => 'Inventario',
        'caja' => 'Caja',
        'preparacion' => 'Preparación de enfermería',
    ];

    const DESCRIPCIONES = [
        'turnos.ver' => 'Ver agenda, turnos y sala de espera',
        'turnos.gestionar' => 'Registrar, mover y cancelar turnos',
        'pacientes.ver' => 'Ver la lista y ficha de pacientes',
        'pacientes.crear' => 'Registrar pacientes',
        'pacientes.editar' => 'Editar datos de pacientes',
        'pacientes.eliminar' => 'Eliminar pacientes',
        'expediente.ver' => 'Ver consultas, recetas y archivos clínicos',
        'expediente.crear' => 'Registrar consultas y documentos médicos',
        'ecografias.ver' => 'Ver ecografías',
        'ecografias.crear' => 'Registrar ecografías',
        'facturas.ver' => 'Ver facturas',
        'facturas.crear' => 'Emitir facturas',
        'facturas.anular' => 'Anular facturas y emitir notas de crédito',
        'caja.cerrar' => 'Cerrar la caja diaria (cuadre del efectivo)',
        'reportes.ver' => 'Ver reportes de ventas',
        'configuracion.ver' => 'Ver la configuración',
        'configuracion.editar' => 'Administrar la clínica (configuración, SRI, tarifario, roles)',
        'usuarios.ver' => 'Ver usuarios',
        'usuarios.gestionar' => 'Crear y editar usuarios',
        'auditoria.ver' => 'Ver el registro de accesos y los indicadores de auditoría',
        'inventario.ver' => 'Ver existencias, lotes y kardex',
        'inventario.gestionar' => 'Registrar entradas, salidas, ajustes y bajas',
        'preparacion.registrar' => 'Tomar signos vitales antes de la consulta',
        'auditoria.gestionar' => 'Evaluar historias clínicas y registrar planes de acción',
    ];

    /** Permisos que convierten a un rol en administrador de hecho: se avisa al marcarlos */
    const SENSIBLES = ['configuracion.editar', 'usuarios.gestionar'];

    public function alternar(int $rolId, string $permiso): void
    {
        $this->authorize('configuracion.editar');

        $rol = Role::findOrFail($rolId);
        if ($rol->name === self::ROL_PROTEGIDO) {
            $this->dispatch('notify', message: 'El rol administrador siempre tiene todos los permisos.', type: 'error');
            return;
        }
        $perm = Permission::where('name', $permiso)->firstOrFail();

        $quitar = $rol->hasPermissionTo($perm);
        $quitar ? $rol->revokePermissionTo($perm) : $rol->givePermissionTo($perm);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(AuditoriaService::class)->registrar(
            $quitar ? 'quitar_permiso' : 'dar_permiso', 'rol', $rol->id, null, "{$rol->name}: {$perm->name}"
        );
        $this->dispatch('notify', message: ($quitar ? 'Permiso quitado a ' : 'Permiso dado a ') . $rol->name . '.', type: 'success');
    }

    public function render()
    {
        $roles = Role::with('permissions:id,name')->withCount('users')->orderByRaw('name = ? desc', [self::ROL_PROTEGIDO])->orderBy('name')->get();
        $grupos = Permission::orderBy('name')->pluck('name')
            ->groupBy(fn ($p) => str($p)->before('.')->toString())
            ->sortBy(fn ($v, $g) => array_search($g, array_keys(self::GRUPOS)) === false ? 99 : array_search($g, array_keys(self::GRUPOS)));

        return view('livewire.roles-permisos', [
            'roles' => $roles,
            'grupos' => $grupos,
            'asignados' => $roles->mapWithKeys(fn ($r) => [$r->id => $r->permissions->pluck('name')->flip()]),
        ])->layout('layouts.app');
    }
}
