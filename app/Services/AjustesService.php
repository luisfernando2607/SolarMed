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
        // Establecimiento de salud: bloque A de los formularios de la Historia Clínica Única (AM 00115-2021)
        'establecimiento.institucion' => 'Red Privada Complementaria',
        'establecimiento.unicodigo' => '',
        'establecimiento.tipologia' => '',
        'establecimiento.provincia' => '',
        'establecimiento.canton' => '',
        'establecimiento.parroquia' => '',
        'establecimiento.distrito' => '',
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
        // Agenda en línea (página pública /agendar)
        'agenda.reserva_online' => true,
        'agenda.reserva_dias' => 30,
        'agenda.reserva_anticipacion_horas' => 2,
        // Recordatorio por WhatsApp: {paciente} {fecha} {hora} {medico} {clinica}
        'recordatorios.whatsapp_plantilla' => 'Hola {paciente}, le recordamos su cita en {clinica} el {fecha} a las {hora} con {medico}. Si no puede asistir, por favor avísenos respondiendo este mensaje.',
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
        // WhatsApp por la API oficial de Meta (apagado hasta configurarlo). El token y el app secret se guardan cifrados.
        'whatsapp.activo' => false,
        'whatsapp.api_version' => 'v23.0',
        'whatsapp.phone_number_id' => '',
        'whatsapp.waba_id' => '',
        'whatsapp.token' => '',
        'whatsapp.app_secret' => '',
        'whatsapp.verify_token' => '',
        'whatsapp.idioma' => 'es',
        'whatsapp.usar_recordatorio' => true,
        'whatsapp.plantilla_recordatorio' => 'recordatorio_cita',
        'whatsapp.usar_confirmacion' => true,
        'whatsapp.plantilla_confirmacion' => 'confirmacion_reserva',
        'whatsapp.usar_encuesta' => true,
        'whatsapp.plantilla_encuesta' => 'encuesta_satisfaccion',
        // Telemedicina (Norma Técnica de Telesalud, MSP 2025). Apagada hasta que la clínica cuente con el permiso de ACESS (num. 7.1.6)
        'telemedicina.activa' => false,
        'telemedicina.plataforma' => 'jitsi',
        'telemedicina.jitsi_url' => 'https://meet.jit.si',
        'telemedicina.minutos_antes' => 15,
        'telemedicina.minutos_despues' => 60,
        // Tareas automáticas
        // Encuesta de satisfacción del usuario externo
        'encuestas.activas' => true,
        'encuestas.dias_validez' => 7,
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
