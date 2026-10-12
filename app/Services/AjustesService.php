<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Ajustes de la clínica editables desde Configuración. Se guardan en la tabla `configuracion`
 * (prefijo "ajuste."); si un ajuste no se ha guardado, se usa su valor por defecto.
 */
class AjustesService
{
    public const DEFECTOS = [
        // Marca de la clínica (login, menú, formulario público, PDF y correos)
        'marca.nombre' => 'SolarMed',
        'marca.subtitulo' => 'Sistema médico',
        'marca.direccion' => '',
        'marca.telefono' => '044619253',
        'marca.email' => '',
        'marca.color' => '#0F6E56',
        'marca.logo' => null,      // ruta relativa a public/ (null = logo incluido)
        'marca.logo_bn' => null,   // versión en blanco y negro para tickets
        // Catálogo para el pedido de exámenes
        'examenes.laboratorio' => [
            'Biometría hemática', 'Glucosa en ayunas', 'Hemoglobina glicosilada (HbA1c)', 'Urea', 'Creatinina', 'Ácido úrico',
            'Perfil lipídico', 'TGO / TGP', 'TSH', 'T4 libre', 'Proteína C reactiva (PCR)', 'Grupo sanguíneo y factor Rh',
            'Prueba de embarazo (β-hCG)', 'VDRL', 'VIH', 'Hepatitis B (HBsAg)', 'EMO (examen de orina)', 'Urocultivo',
            'Coproparasitario',
        ],
        'examenes.imagen' => [
            'Ecografía obstétrica', 'Ecografía pélvica', 'Ecografía transvaginal', 'Ecografía abdominal', 'Ecografía mamaria',
            'Mamografía', 'Radiografía de tórax', 'Papanicolaou', 'Colposcopia',
        ],
        // Agenda
        'agenda.tipos' => ['Consulta general', 'Consulta ginecológica', 'Control prenatal', 'Ecografía', 'Control post-parto', 'Planificación familiar', 'Otro'],
        'agenda.duraciones' => [15, 20, 30, 40, 45, 60, 90],
        'agenda.duracion_defecto' => 20,
        'agenda.hora_inicio' => '07:00',
        'agenda.hora_fin' => '20:00',
        'agenda.dias_laborables' => [1, 2, 3, 4, 5, 6], // 0 = domingo … 6 = sábado
        // Alertas prenatales (apoyo a la decisión clínica)
        'prenatal.pa_sistolica' => 140,
        'prenatal.pa_diastolica' => 90,
        'prenatal.pa_severa_sistolica' => 160,
        'prenatal.pa_severa_diastolica' => 110,
        'prenatal.fcf_min' => 110,
        'prenatal.fcf_max' => 160,
        'prenatal.au_tolerancia_cm' => 3,
        'prenatal.movimientos_desde_semana' => 28,
        'prenatal.dias_sin_control' => 28,
        'prenatal.aviso_fpp_dias' => 14,
        // Tareas automáticas
        'tareas.hora_recordatorios' => '17:00',
        'tareas.hora_respaldo' => '02:30',
    ];

    private const CACHE = 'ajustes.clinica';

    public function get(string $clave): mixed
    {
        return array_key_exists($clave, $guardados = $this->guardados())
            ? $guardados[$clave]
            : (self::DEFECTOS[$clave] ?? null);
    }

    public function todos(): array
    {
        return array_merge(self::DEFECTOS, array_intersect_key($this->guardados(), self::DEFECTOS));
    }

    public function guardar(array $valores): void
    {
        foreach (array_intersect_key($valores, self::DEFECTOS) as $clave => $valor) {
            DB::table('configuracion')->updateOrInsert(
                ['clave' => "ajuste.{$clave}"],
                ['valor' => json_encode($valor, JSON_UNESCAPED_UNICODE), 'descripcion' => 'Ajuste de la clínica', 'updated_at' => now()]
            );
        }
        Cache::forget(self::CACHE);
    }

    /** Lee los ajustes guardados. Si la base aún no existe (instalación), devuelve vacío para usar los defectos. */
    private function guardados(): array
    {
        try {
            return Cache::rememberForever(self::CACHE, fn () => DB::table('configuracion')
                ->where('clave', 'like', 'ajuste.%')
                ->pluck('valor', 'clave')
                ->mapWithKeys(fn ($v, $k) => [substr($k, 7) => json_decode($v, true)])
                ->all());
        } catch (\Throwable) {
            return [];
        }
    }
}
