<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    protected $fillable = [
        'paciente_id', 'medico_id', 'especialidad_id', 'tipo', 'fecha_hora',
        'duracion_min', 'estado', 'color', 'notas', 'recordatorio_enviado_at',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'recordatorio_enviado_at' => 'datetime',
    ];

    public function paciente(): BelongsTo     { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo       { return $this->belongsTo(Medico::class); }
    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class); }
}
