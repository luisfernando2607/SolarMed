<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder {
    public function run(): void {
        $config = [
            ['clave' => 'clinica_nombre',     'valor' => 'Clínica Santa Martha',   'descripcion' => 'Nombre de la clínica'],
            ['clave' => 'clinica_telefono',   'valor' => '044619253',              'descripcion' => 'Teléfono principal'],
            ['clave' => 'clinica_ciudad',     'valor' => 'Ecuador',                'descripcion' => 'Ciudad/país'],
            ['clave' => 'clinica_logo',       'valor' => null,                     'descripcion' => 'Ruta al logo'],
            ['clave' => 'sri_ruc',            'valor' => null,                     'descripcion' => 'RUC para facturación'],
            ['clave' => 'sri_razon_social',   'valor' => null,                     'descripcion' => 'Razón social'],
            ['clave' => 'sri_direccion',      'valor' => null,                     'descripcion' => 'Dirección fiscal'],
            ['clave' => 'factura_secuencial', 'valor' => '1',                      'descripcion' => 'Último número de factura'],
            ['clave' => 'turno_rate_limit',   'valor' => '3',                      'descripcion' => 'Máx turnos por cédula/día'],
        ];
        foreach ($config as $item) {
            DB::table('configuracion')->insertOrIgnore($item);
        }
    }
}
