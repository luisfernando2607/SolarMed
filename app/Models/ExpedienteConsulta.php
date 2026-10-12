<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExpedienteConsulta extends Model {
    protected $table    = 'expediente_consultas';
    protected $fillable = [
        'paciente_id','medico_id','especialidad_id','tipo_consulta','tipo_atencion',
        'cita_id','turno_id','fecha',
        'motivo_consulta','anamnesis','revision_sistemas','examen_fisico',
        'diagnostico','codigo_cie10','tipo_diagnostico','tratamiento','indicaciones',
        'requiere_derivacion','derivacion_especialidad',
    ];
    protected $casts = [
        'fecha'               => 'datetime',
        'examen_fisico'       => 'array',
        'requiere_derivacion' => 'boolean',
    ];

    /** Formulario 002, bloque B */
    public const TIPOS_ATENCION = ['primera' => 'Primera vez', 'subsecuente' => 'Subsecuente'];
    /** Formulario 002, bloque I */
    public const TIPOS_DIAGNOSTICO = ['presuntivo' => 'Presuntivo', 'definitivo' => 'Definitivo'];

    public function paciente(): BelongsTo    { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo      { return $this->belongsTo(Medico::class); }
    public function especialidad(): BelongsTo{ return $this->belongsTo(Especialidad::class); }
    public function controlPrenatal(): HasOne { return $this->hasOne(ControlPrenatal::class, 'consulta_id'); }
    public function ecografia(): HasOne      { return $this->hasOne(Ecografia::class, 'consulta_id'); }
    public function versiones(): HasMany     { return $this->hasMany(ExpedienteConsultaVersion::class, 'consulta_id')->latest('created_at')->latest('id'); }
}
