<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Evaluación manual de la calidad del llenado de una consulta (formulario MSP 002) */
class AuditoriaHc extends Model
{
    protected $table = 'auditorias_hc';

    protected $fillable = ['consulta_id', 'user_id', 'fecha', 'puntaje', 'puntaje_automatico', 'resultado', 'observacion', 'estado', 'cerrada_at'];

    protected $casts = ['fecha' => 'date', 'resultado' => 'array', 'cerrada_at' => 'datetime'];

    protected static function booted(): void
    {
        // Una evaluación cerrada es un registro de auditoría: no se modifica ni se elimina
        static::updating(fn (self $a) => $a->getOriginal('estado') !== 'cerrada');
        static::deleting(fn (self $a) => $a->estado !== 'cerrada');
    }

    public function consulta(): BelongsTo { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function auditor(): BelongsTo  { return $this->belongsTo(User::class, 'user_id'); }
    public function planes(): HasMany     { return $this->hasMany(PlanAccion::class, 'auditoria_hc_id'); }

    public function cerrada(): bool
    {
        return $this->estado === 'cerrada';
    }

    /** Número correlativo del informe, p. ej. AHC-000012 */
    public function numero(): string
    {
        return 'AHC-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
