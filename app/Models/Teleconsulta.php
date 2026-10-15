<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Teleconsulta sincrónica (videoconsulta) según la Norma Técnica de Telesalud del MSP (2025).
 * La sala de video vive en una plataforma externa; aquí se guarda la agenda, la autorización y el
 * consentimiento del paciente (num. 7.2.2), la trazabilidad (7.1.1.1) y la efectividad de la conexión (7.3.4).
 */
class Teleconsulta extends Model
{
    public const ESTADOS = [
        'programada' => ['Programada', 'bg-blue-50 text-blue-700'],
        'en_curso' => ['En curso', 'bg-emerald-50 text-emerald-700'],
        'finalizada' => ['Finalizada', 'bg-gray-100 text-gray-600'],
        'cancelada' => ['Cancelada', 'bg-red-50 text-red-700'],
        'no_asistio' => ['No se conectó', 'bg-amber-50 text-amber-700'],
    ];

    /** Efectividad de la teleconsulta (indicador del num. 7.3.4) */
    public const RESULTADOS = [
        'efectiva' => 'Efectiva',
        'parcial' => 'Parcial (con problemas de conexión)',
        'fallida' => 'Fallida (no se pudo completar)',
    ];

    protected $fillable = [
        'paciente_id', 'medico_id', 'cita_id', 'consulta_id', 'fecha_hora', 'duracion_min', 'plataforma', 'sala_url', 'token',
        'estado', 'autorizacion_telematica_at', 'consentimiento_datos_at', 'consentimiento_medio', 'consentimiento_ip', 'consentimiento_representante',
        'consentimiento_registrado_por', 'inicio_at', 'fin_at', 'resultado_conexion', 'observaciones_tecnicas', 'created_by',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'autorizacion_telematica_at' => 'datetime',
        'consentimiento_datos_at' => 'datetime',
        'inicio_at' => 'datetime',
        'fin_at' => 'datetime',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function cita(): BelongsTo     { return $this->belongsTo(Cita::class); }
    public function consulta(): BelongsTo { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }

    /** Autorización de medios telemáticos y consentimiento de datos, ambos documentados (num. 7.2.2) */
    public function tieneConsentimiento(): bool
    {
        return $this->autorizacion_telematica_at !== null && $this->consentimiento_datos_at !== null;
    }

    public function abierta(): bool
    {
        return in_array($this->estado, ['programada', 'en_curso'], true);
    }

    public function enlacePaciente(): string
    {
        return route('teleconsulta.paciente', $this->token);
    }

    public function estadoTexto(): string
    {
        return self::ESTADOS[$this->estado][0] ?? $this->estado;
    }

    public function estadoTono(): string
    {
        return self::ESTADOS[$this->estado][1] ?? 'bg-gray-100 text-gray-600';
    }
}
