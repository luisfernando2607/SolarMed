<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlPrenatal extends Model
{
    protected $table = 'controles_prenatales';

    protected $fillable = [
        'paciente_id', 'medico_id', 'consulta_id', 'fecha',
        'semanas_gestacion', 'fpp', 'peso_materno', 'presion_arterial',
        'altura_uterina', 'fcf', 'presentacion', 'movimientos_fetales',
        'edemas', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fpp' => 'date',
        'movimientos_fetales' => 'boolean',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo  { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
}
