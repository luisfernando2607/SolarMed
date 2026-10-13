<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Consentimiento informado (SNS-MSP/HCU-form.024). Mientras espera la firma se puede corregir;
 * una vez aceptado, negado o revocado el contenido clínico queda fijo y solo avanza su estado.
 */
class Consentimiento extends Model
{
    public const ESTADOS = [
        'pendiente_firma' => ['Pendiente de firma', 'bg-amber-50 text-amber-700'],
        'aceptado' => ['Aceptado', 'bg-emerald-50 text-emerald-700'],
        'negado' => ['Negado', 'bg-red-50 text-red-700'],
        'revocado' => ['Revocado', 'bg-gray-100 text-gray-600'],
    ];

    /** Textos legales de los bloques C, D y E, transcritos del formulario SNS-MSP/HCU-form.024 */
    public const TEXTO_DECLARACION = 'He facilitado la información completa que conozco, y me ha sido solicitada, sobre los antecedentes personales, familiares y de mi estado de salud. Soy consciente que de omitir estos datos puede afectarse los resultados del tratamiento. Estoy de acuerdo con el procedimiento que se me ha propuesto; he sido informado de las ventajas e inconvenientes del mismo; se me ha explicado de forma clara en qué consiste, los beneficios y posibles riesgos del procedimiento. He escuchado, leído y comprendido la información recibida y se me ha dado la oportunidad de preguntar sobre el procedimiento. He tomado consciente y libremente de decisión de autorizar el procedimiento adicional, si es considerado necesario según el juicio del profesional de la salud, para mi beneficio. También conozco que puedo retirar mi consentimiento cuando lo estime oportuno.';

    public const TEXTO_NEGATIVA = 'Una vez que he entendido claramente el procedimiento propuesto, así como las consecuencias posibles si no se realiza la intervención, no autorizo y me niego a que se me realice el procedimiento propuesto y desvinculo de responsabilidades futuras de cualquier índole al establecimiento de salud y al profesional sanitario que me atiende, por no realizar la intervención sugerida.';

    public const TEXTO_REVOCATORIA = 'De forma libre y voluntaria, revoco el consentimiento realizado en fecha y manifiesto expresamente mi deseo de no continuar con el procedimiento médico que doy por finalizado en esta fecha. Libero de responsabilidades futuras de cualquier índole al establecimiento de salud y al profesional sanitario que me atienda.';

    /** Contenido del bloque B: no cambia después de la firma */
    public const CAMPOS_CLINICOS = [
        'paciente_id', 'medico_id', 'consulta_id', 'servicio', 'tipo_atencion', 'diagnostico', 'codigo_cie10',
        'procedimiento', 'en_que_consiste', 'como_se_realiza', 'grafico_ruta', 'duracion_estimada', 'beneficios',
        'riesgos_frecuentes', 'riesgos_graves', 'riesgos_especificos', 'alternativas', 'manejo_posterior',
        'consecuencias_no_realizar', 'fecha_informacion', 'hora_informacion',
    ];

    /** Transiciones permitidas del estado */
    private const TRANSICIONES = [
        'pendiente_firma' => ['aceptado', 'negado'],
        'aceptado' => ['revocado'],
        'negado' => [],
        'revocado' => [],
    ];

    protected $fillable = [
        ...self::CAMPOS_CLINICOS, 'user_id', 'estado',
        'representante_nombre', 'representante_cedula', 'representante_parentesco',
        'negativa_sin_firma', 'testigo_nombre', 'testigo_cedula',
        'fecha_aceptacion', 'fecha_negativa', 'fecha_revocatoria', 'motivo_revocatoria',
        'documento_firmado_ruta', 'documento_firmado_mime', 'documento_firmado_at',
    ];

    protected $casts = [
        'fecha_informacion' => 'date',
        'fecha_aceptacion' => 'datetime',
        'fecha_negativa' => 'datetime',
        'fecha_revocatoria' => 'datetime',
        'documento_firmado_at' => 'datetime',
        'negativa_sin_firma' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (Consentimiento $c) {
            $estadoAnterior = $c->getOriginal('estado');

            if ($estadoAnterior !== 'pendiente_firma' && $c->isDirty(self::CAMPOS_CLINICOS)) {
                throw new LogicException('El consentimiento ya fue firmado: su contenido no se puede modificar.');
            }
            if ($c->isDirty('estado') && !in_array($c->estado, self::TRANSICIONES[$estadoAnterior] ?? [], true)) {
                throw new LogicException("No se puede pasar de {$estadoAnterior} a {$c->estado}.");
            }
        });

        static::deleting(function (Consentimiento $c) {
            if ($c->estado !== 'pendiente_firma') {
                throw new LogicException('Un consentimiento firmado forma parte de la historia clínica y no se puede eliminar.');
            }
        });
    }

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function autor(): BelongsTo    { return $this->belongsTo(User::class, 'user_id'); }

    public function numero(): string
    {
        return 'CI-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function editable(): bool
    {
        return $this->estado === 'pendiente_firma';
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    public function estadoTono(): string
    {
        return self::ESTADOS[$this->estado][1] ?? 'bg-gray-100 text-gray-600';
    }

    /** Menores de edad: firma el padre, la madre o el representante legal (instructivo del form. 024) */
    public function requiereRepresentante(): bool
    {
        $edad = $this->paciente?->edad;
        return $edad !== null && $edad < 18;
    }
}
