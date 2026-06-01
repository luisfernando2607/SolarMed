<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class SriConfigService {

    public function get(string $clave, mixed $default = null): mixed
    {
        $row = DB::table('configuracion')->where('clave', $clave)->first();
        return $row ? $row->valor : $default;
    }

    public function set(string $clave, mixed $valor): void
    {
        DB::table('configuracion')->updateOrInsert(
            ['clave' => $clave],
            ['valor' => $valor, 'descripcion' => $this->descripciones[$clave] ?? '']
        );
    }

    public function getAll(): array
    {
        $rows = DB::table('configuracion')
            ->whereIn('clave', array_keys($this->descripciones))
            ->get();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->clave] = $row->valor;
        }
        return $result;
    }

    private array $descripciones = [
        'sri_ruc' => 'RUC de la clínica',
        'sri_razon_social' => 'Razón social',
        'sri_nombre_comercial' => 'Nombre comercial',
        'sri_direccion' => 'Dirección matriz',
        'sri_telefono' => 'Teléfono',
        'sri_email' => 'Correo electrónico',
        'sri_contribuyente_especial' => 'Resolución de contribuyente especial',
        'sri_obligado_contabilidad' => 'Obligado a llevar contabilidad (SI/NO)',
        'sri_establecimiento' => 'Código de establecimiento (001)',
        'sri_pto_emi' => 'Código punto de emisión (001)',
        'sri_ambiente' => '1=Pruebas, 2=Producción',
        'factura_secuencial' => 'Último secuencial de factura',
    ];
}
