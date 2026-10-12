<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidenteSeguridad extends Model
{
    protected $table = 'incidentes_seguridad';

    /** LOPDP: notificar a la autoridad y al CSIRT en máximo 5 días desde que se conoce la vulneración */
    public const DIAS_NOTIFICACION = 5;

    public const SEVERIDADES = [
        'baja' => ['Baja', 'bg-gray-100 text-gray-700'],
        'media' => ['Media', 'bg-amber-50 text-amber-700'],
        'alta' => ['Alta', 'bg-orange-50 text-orange-700'],
        'critica' => ['Crítica', 'bg-red-50 text-red-700'],
    ];

    public const ESTADOS = [
        'abierto' => 'Abierto',
        'notificado' => 'Notificado',
        'cerrado' => 'Cerrado',
    ];

    protected $fillable = [
        'fecha_deteccion', 'descripcion', 'datos_afectados', 'pacientes_afectados', 'severidad',
        'requiere_notificacion', 'medidas', 'notificado_autoridad_at', 'estado', 'registrado_por',
    ];

    protected $casts = [
        'fecha_deteccion' => 'datetime',
        'notificado_autoridad_at' => 'datetime',
        'requiere_notificacion' => 'boolean',
    ];

    public function registrador(): BelongsTo { return $this->belongsTo(User::class, 'registrado_por'); }

    public function fechaLimiteNotificacion(): Carbon
    {
        return $this->fecha_deteccion->copy()->addDays(self::DIAS_NOTIFICACION);
    }

    /** Requiere notificación, aún no se notificó y ya pasó el plazo */
    public function notificacionVencida(): bool
    {
        return $this->requiere_notificacion && !$this->notificado_autoridad_at && $this->fechaLimiteNotificacion()->isPast();
    }

    public function notificacionPendiente(): bool
    {
        return $this->requiere_notificacion && !$this->notificado_autoridad_at;
    }
}
