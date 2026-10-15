<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cita extends Model
{
    protected $fillable = [
        'paciente_id', 'medico_id', 'especialidad_id', 'tipo', 'fecha_hora',
        'duracion_min', 'estado', 'origen', 'codigo_reserva', 'color', 'notas', 'recordatorio_enviado_at', 'whatsapp_recordatorio_at',
    ];

    /** Citas reservadas por el paciente en la página pública que el personal aún no confirma */
    public function scopeEnLineaPorConfirmar($q)
    {
        return $q->where('origen', 'web')->where('estado', 'pendiente')->where('fecha_hora', '>=', now()->startOfDay());
    }

    protected $casts = [
        'fecha_hora' => 'datetime',
        'recordatorio_enviado_at' => 'datetime',
        'whatsapp_recordatorio_at' => 'datetime',
    ];

    public function paciente(): BelongsTo     { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo       { return $this->belongsTo(Medico::class); }
    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class); }
}
