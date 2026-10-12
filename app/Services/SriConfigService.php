<?php
namespace App\Services;

use App\Models\Medico;
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

    /**
     * Datos del emisor de una factura. Cada médico factura con su propio RUC; si el médico aún no
     * tiene RUC configurado se usan los datos de la clínica (SRI > Configuración).
     *
     * @return array{ruc: ?string, razon_social: ?string, nombre_comercial: ?string, direccion: ?string,
     *               obligado_contabilidad: string, regimen_rimpe: ?string, contribuyente_especial: ?string,
     *               establecimiento: string, pto_emi: string, origen: string}
     */
    public function emisor(?Medico $medico): array
    {
        if ($medico?->ruc) {
            return [
                'ruc' => $medico->ruc,
                'razon_social' => $medico->razon_social ?: trim("{$medico->apellidos} {$medico->nombres}"),
                'nombre_comercial' => $medico->nombre_comercial,
                'direccion' => $medico->direccion_matriz ?: $this->get('sri_direccion'),
                'obligado_contabilidad' => $medico->obligado_contabilidad ?: 'NO',
                'regimen_rimpe' => $medico->regimen_rimpe,
                'contribuyente_especial' => null,
                'establecimiento' => $medico->sri_establecimiento ?: '001',
                'pto_emi' => $medico->sri_pto_emi ?: '001',
                'origen' => 'medico',
            ];
        }

        return [
            'ruc' => $this->get('sri_ruc'),
            'razon_social' => $this->get('sri_razon_social'),
            'nombre_comercial' => $this->get('sri_nombre_comercial'),
            'direccion' => $this->get('sri_direccion'),
            'obligado_contabilidad' => $this->get('sri_obligado_contabilidad', 'NO'),
            'regimen_rimpe' => null,
            'contribuyente_especial' => $this->get('sri_contribuyente_especial'),
            'establecimiento' => $this->get('sri_establecimiento', '001'),
            'pto_emi' => $this->get('sri_pto_emi', '001'),
            'origen' => 'clinica',
        ];
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
