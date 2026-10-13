<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Textos reutilizables de un procedimiento para el consentimiento informado (los redacta el profesional) */
class PlantillaConsentimiento extends Model
{
    protected $table = 'plantillas_consentimiento';

    /** Campos del bloque B que se copian de la plantilla al consentimiento */
    public const CAMPOS = [
        'procedimiento', 'en_que_consiste', 'como_se_realiza', 'duracion_estimada', 'beneficios',
        'riesgos_frecuentes', 'riesgos_graves', 'alternativas', 'manejo_posterior', 'consecuencias_no_realizar',
    ];

    protected $fillable = [...self::CAMPOS, 'activo', 'created_by'];

    protected $casts = ['activo' => 'boolean'];

    public function autor(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
