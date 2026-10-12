<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Plan de acción de mejora derivado de una auditoría de historia clínica (MSP) */
class PlanAccion extends Model
{
    protected $table = 'planes_accion';

    public const ESTADOS = ['pendiente' => 'Pendiente', 'en_proceso' => 'En proceso', 'cumplido' => 'Cumplido'];

    protected $fillable = ['auditoria_hc_id', 'medico_id', 'hallazgo', 'accion', 'responsable_user_id', 'fecha_limite', 'estado', 'evidencia', 'cerrado_at', 'creado_por'];

    protected $casts = ['fecha_limite' => 'date', 'cerrado_at' => 'datetime'];

    public function auditoria(): BelongsTo   { return $this->belongsTo(AuditoriaHc::class, 'auditoria_hc_id'); }
    public function medico(): BelongsTo      { return $this->belongsTo(Medico::class); }
    public function responsable(): BelongsTo { return $this->belongsTo(User::class, 'responsable_user_id'); }

    public function vencido(): bool
    {
        return $this->estado !== 'cumplido' && $this->fecha_limite->lt(today());
    }
}
