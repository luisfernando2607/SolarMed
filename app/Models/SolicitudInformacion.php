<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudInformacion extends Model
{
    protected $table = 'solicitudes_informacion';

    /** [etiqueta, plazo en horas, base normativa] */
    public const TIPOS = [
        'copia_hc' => ['Copia certificada de historia clínica', 48, 'AM 5216-A, Arts. 28-30'],
        'acceso' => ['Derecho de acceso', 15 * 24, 'LOPDP'],
        'rectificacion' => ['Rectificación o actualización', 15 * 24, 'LOPDP'],
        'eliminacion' => ['Eliminación', 15 * 24, 'LOPDP'],
        'oposicion' => ['Oposición', 15 * 24, 'LOPDP'],
        'portabilidad' => ['Portabilidad', 15 * 24, 'LOPDP'],
        'uso_investigacion' => ['Entrega para investigación o docencia', 15 * 24, 'AM 5216-A, Art. 12'],
    ];

    public const RELACIONES = [
        'titular' => 'El propio paciente',
        'representante' => 'Representante legal / apoderado',
        'familiar' => 'Cónyuge o familiar (paciente fallecido)',
        'profesional' => 'Profesional de la salud tratante',
        'autoridad' => 'Autoridad judicial o sanitaria',
        'investigador' => 'Investigador / docente',
    ];

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'entregada' => 'Entregada',
        'rechazada' => 'Rechazada',
    ];

    /** Margen para avisar que una solicitud está por vencer */
    public const HORAS_AVISO = 12;

    protected $fillable = [
        'paciente_id', 'tipo', 'solicitante_nombres', 'solicitante_cedula', 'relacion', 'entidad', 'motivo',
        'documento_ruta', 'documento_nombre', 'documento_mime', 'fecha_solicitud', 'fecha_limite',
        'estado', 'fecha_respuesta', 'respuesta', 'registrado_por', 'atendido_por',
    ];

    protected $casts = [
        'fecha_solicitud' => 'datetime',
        'fecha_limite' => 'datetime',
        'fecha_respuesta' => 'datetime',
    ];

    public function paciente(): BelongsTo     { return $this->belongsTo(Paciente::class); }
    public function registrador(): BelongsTo  { return $this->belongsTo(User::class, 'registrado_por'); }
    public function atendedor(): BelongsTo    { return $this->belongsTo(User::class, 'atendido_por'); }

    /** Plazo legal: 48 horas para copias de HC; 15 días calendario para el resto */
    public static function calcularFechaLimite(string $tipo, Carbon $fechaSolicitud): Carbon
    {
        return $fechaSolicitud->copy()->addHours(self::TIPOS[$tipo][1] ?? 15 * 24);
    }

    public function scopePendientes(Builder $q): Builder
    {
        return $q->where('estado', 'pendiente');
    }

    public function vencida(): bool
    {
        return $this->estado === 'pendiente' && $this->fecha_limite->isPast();
    }

    public function porVencer(): bool
    {
        return $this->estado === 'pendiente' && !$this->vencida()
            && $this->fecha_limite->lte(now()->addHours(self::HORAS_AVISO));
    }

    public function tipoTexto(): string
    {
        return self::TIPOS[$this->tipo][0] ?? $this->tipo;
    }
}
