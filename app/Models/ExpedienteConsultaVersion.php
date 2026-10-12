<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Copia de una consulta clínica tal como estaba antes de cada corrección. Solo se agregan, nunca se editan. */
class ExpedienteConsultaVersion extends Model
{
    protected $table = 'expediente_consulta_versiones';

    public const UPDATED_AT = null;

    protected $fillable = ['consulta_id', 'user_id', 'datos_anteriores', 'motivo'];

    protected $casts = ['datos_anteriores' => 'array'];

    public function consulta(): BelongsTo { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
}
