<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Referencia, derivación, contrarreferencia y referencia inversa (SNS-MSP/HCU-form.053/2021).
 * La parte clínica de la referencia (bloques A–F) queda fija cuando llega o se emite la contrarreferencia.
 */
class Referencia extends Model
{
    protected $table = 'referencias';

    /** Motivo de la referencia o derivación, numerado como en el formulario */
    public const MOTIVOS = [
        1 => 'Accesibilidad geográfica',
        2 => 'Falta de espacio físico',
        3 => 'Falta de equipamiento',
        4 => 'Equipos en mal estado',
        5 => 'Problemas de infraestructura',
        6 => 'Problemas de abastecimiento',
        7 => 'Insuficiencia de profesionales',
        8 => 'Inadecuada capacidad resolutiva',
        9 => 'Ausencia de la prestación en la cartera de servicios',
    ];

    public const INSTITUCIONES = ['Ministerio de Salud Pública (MSP)', 'IESS', 'ISSFA', 'ISSPOL', 'Red Privada Complementaria'];

    public const TIPOS = ['referencia' => 'Referencia', 'derivacion' => 'Derivación'];

    public const TIPOS_CONTRA = ['contrarreferencia' => 'Contrarreferencia', 'referencia_inversa' => 'Referencia inversa'];

    public const ESTADOS = ['enviada' => 'Enviada', 'contrarreferida' => 'Contrarreferida', 'cerrada' => 'Cerrada'];

    /** Bloque E admite hasta 4 diagnósticos; la contrarreferencia (bloque F) hasta 2 */
    public const MAX_DIAGNOSTICOS = 4;
    public const MAX_DIAGNOSTICOS_CONTRA = 2;

    /** Días sin respuesta tras los que una referencia saliente se resalta */
    public const DIAS_SIN_RESPUESTA = 30;

    /** Campos de la sección referencia que no cambian una vez contrarreferida */
    private const CAMPOS_CLINICOS = [
        'paciente_id', 'consulta_id', 'medico_id', 'direccion', 'establecimiento_origen', 'institucion_origen', 'profesional_origen',
        'tipo', 'motivo', 'institucion_destino', 'establecimiento_destino', 'servicio', 'especialidad',
        'resumen_cuadro_clinico', 'hallazgos', 'diagnosticos', 'fecha',
    ];

    protected $fillable = [
        'paciente_id', 'consulta_id', 'medico_id', 'user_id', 'direccion', 'establecimiento_origen', 'institucion_origen', 'profesional_origen',
        'tipo', 'motivo', 'institucion_destino', 'establecimiento_destino', 'servicio', 'especialidad',
        'resumen_cuadro_clinico', 'hallazgos', 'diagnosticos', 'fecha', 'referencia_justificada', 'derivacion_justificada',
        'contra_tipo', 'contra_establecimiento', 'contra_institucion', 'contra_distrito', 'contra_fecha', 'contra_resumen',
        'contra_hallazgos', 'contra_tratamientos_realizados', 'contra_diagnosticos', 'contra_tratamiento_recomendado',
        'contra_profesional', 'contra_profesional_cedula', 'contra_medico_id',
        'documento_respuesta_ruta', 'documento_respuesta_nombre', 'documento_respuesta_mime', 'estado',
    ];

    protected $attributes = ['direccion' => 'saliente', 'tipo' => 'referencia', 'estado' => 'enviada'];

    protected $casts = [
        'fecha' => 'datetime',
        'contra_fecha' => 'datetime',
        'diagnosticos' => 'array',
        'contra_diagnosticos' => 'array',
        'referencia_justificada' => 'boolean',
        'derivacion_justificada' => 'boolean',
        'motivo' => 'integer',
    ];

    protected static function booted(): void
    {
        static::updating(function (Referencia $r) {
            if ($r->getOriginal('estado') !== 'enviada' && $r->isDirty(self::CAMPOS_CLINICOS)) {
                throw new LogicException('La referencia ya fue contrarreferida: su contenido clínico no se puede modificar.');
            }
        });
        static::deleting(fn () => throw new LogicException('Las referencias forman parte de la historia clínica y no se eliminan.'));
    }

    public function paciente(): BelongsTo      { return $this->belongsTo(Paciente::class); }
    public function consulta(): BelongsTo      { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function medico(): BelongsTo        { return $this->belongsTo(Medico::class); }
    public function contraMedico(): BelongsTo  { return $this->belongsTo(Medico::class, 'contra_medico_id'); }
    public function user(): BelongsTo          { return $this->belongsTo(User::class); }

    public function editable(): bool
    {
        return $this->estado === 'enviada';
    }

    public function tieneContrarreferencia(): bool
    {
        return $this->contra_tipo !== null;
    }

    /** Referencia saliente sin respuesta por más de DIAS_SIN_RESPUESTA días */
    public function sinRespuesta(): bool
    {
        return $this->direccion === 'saliente' && $this->estado === 'enviada'
            && $this->fecha->lt(now()->subDays(self::DIAS_SIN_RESPUESTA));
    }

    public function numero(): string
    {
        return 'REF-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function motivoTexto(): ?string
    {
        return self::MOTIVOS[$this->motivo] ?? null;
    }
}
