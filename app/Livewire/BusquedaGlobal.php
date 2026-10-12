<?php

namespace App\Livewire;

use App\Models\Factura;
use App\Models\Paciente;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Buscador de toda la app (Ctrl+K o "/"): pacientes, facturas y pantallas,
 * siempre limitado a lo que el usuario tiene permiso de ver.
 */
class BusquedaGlobal extends Component
{
    public string $q = '';

    /** [texto, ruta, permiso, palabras clave] */
    private const PANTALLAS = [
        ['Agenda', 'agenda', 'turnos.ver', 'citas calendario'],
        ['Registrar turno', 'turnos.crear', 'turnos.ver', 'nuevo turno'],
        ['Sala de espera', 'sala-espera', 'turnos.ver', 'turnos cola'],
        ['Pacientes', 'pacientes', 'pacientes.ver', 'lista'],
        ['Expedientes', 'expedientes', 'expediente.ver', 'consultas historia clinica'],
        ['Facturas', 'facturas', 'facturas.ver', 'facturacion sri'],
        ['Nueva factura', 'facturas.crear', 'facturas.crear', 'cobrar facturar'],
        ['Reporte de ventas', 'reportes.ventas', 'reportes.ver', 'reportes ingresos'],
        ['Usuarios', 'usuarios', 'usuarios.ver', 'personal cuentas'],
        ['Especialidades', 'especialidades', 'configuracion.editar', 'motivos'],
        ['Tarifario', 'tarifario', 'configuracion.editar', 'precios servicios'],
        ['SRI', 'sri.config', 'configuracion.editar', 'firma electronica ruc certificado'],
        ['Configuración', 'configuracion', 'configuracion.editar', 'ajustes marca logo clinica'],
        ['Auditoría', 'auditoria', 'configuracion.editar', 'registro accesos'],
        ['Mi perfil', 'profile', null, 'contraseña clave dos factores 2fa'],
    ];

    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }

    public function render()
    {
        $user = auth()->user();
        $q = trim($this->q);
        $pacientes = collect();
        $facturas = collect();
        $pantallas = [];

        if (mb_strlen($q) >= 2) {
            // Cada palabra debe aparecer en nombres, apellidos, cédula o teléfono ("ana per" encuentra "Ana Pérez")
            $palabras = array_slice(preg_split('/\s+/', $q), 0, 4);

            if ($user->can('pacientes.ver')) {
                $pacientes = Paciente::query()
                    ->where(function ($consulta) use ($palabras) {
                        foreach ($palabras as $palabra) {
                            $consulta->where(fn ($w) => $w->where('nombres', 'like', "%{$palabra}%")
                                ->orWhere('apellidos', 'like', "%{$palabra}%")
                                ->orWhere('cedula', 'like', "{$palabra}%")
                                ->orWhere('telefono', 'like', "%{$palabra}%"));
                        }
                    })
                    ->orderBy('apellidos')->orderBy('nombres')
                    ->take(6)->get();
            }

            if ($user->can('facturas.ver')) {
                $facturas = Factura::with('paciente')
                    ->where(fn ($w) => $w->where('numero_factura', 'like', "%{$q}%")
                        ->orWhereHas('paciente', fn ($p) => $p->where('cedula', 'like', "{$q}%")))
                    ->latest('fecha')->latest('id')
                    ->take(5)->get();
            }

            $buscado = $this->normalizar($q);
            foreach (self::PANTALLAS as [$texto, $ruta, $permiso, $claves]) {
                if ((!$permiso || $user->can($permiso)) && str_contains($this->normalizar("{$texto} {$claves}"), $buscado)) {
                    $pantallas[] = [$texto, route($ruta)];
                }
            }
        }

        return view('livewire.busqueda-global', [
            'pacientes' => $pacientes,
            'facturas' => $facturas,
            'pantallas' => array_slice($pantallas, 0, 5),
        ]);
    }
}
