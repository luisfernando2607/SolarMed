<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Turno extends Model {
    protected $fillable = [
        'numero_turno','prefijo','especialidad_id','medico_id','paciente_id',
        'nombre_temporal','cedula','telefono','motivo',
        'estado','fecha','hora_registro','hora_llamado','hora_fin',
    ];
    protected $casts = [
        'fecha'         => 'date',
        'hora_registro' => 'datetime',
        'hora_llamado'  => 'datetime',
        'hora_fin'      => 'datetime',
    ];

    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class); }
    public function medico(): BelongsTo       { return $this->belongsTo(Medico::class); }
    public function paciente(): BelongsTo     { return $this->belongsTo(Paciente::class); }

    /** Ej: G3, O12 */
    public function getCodigoAttribute(): string {
        return "{$this->prefijo}{$this->numero_turno}";
    }
}
