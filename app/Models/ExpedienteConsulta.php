<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExpedienteConsulta extends Model {
    protected $table    = 'expediente_consultas';
    protected $fillable = [
        'paciente_id','medico_id','especialidad_id','tipo_consulta',
        'cita_id','turno_id','fecha',
        'motivo_consulta','anamnesis','examen_fisico',
        'diagnostico','codigo_cie10','tratamiento','indicaciones',
        'requiere_derivacion','derivacion_especialidad',
    ];
    protected $casts = [
        'fecha'               => 'datetime',
        'examen_fisico'       => 'array',
        'requiere_derivacion' => 'boolean',
    ];

    public function paciente(): BelongsTo    { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo      { return $this->belongsTo(Medico::class); }
    public function especialidad(): BelongsTo{ return $this->belongsTo(Especialidad::class); }
    public function controlPrenatal(): HasOne { return $this->hasOne(ControlPrenatal::class, 'consulta_id'); }
    public function ecografia(): HasOne      { return $this->hasOne(Ecografia::class, 'consulta_id'); }
}
