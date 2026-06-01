<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receta extends Model
{
    protected $fillable = [
        'consulta_id', 'paciente_id', 'medico_id', 'fecha',
        'medicamentos', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'medicamentos' => 'array',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo  { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
}
