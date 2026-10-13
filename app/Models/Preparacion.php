<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Constantes vitales y antropometría tomadas por enfermería antes de la consulta */
class Preparacion extends Model
{
    protected $table = 'preparaciones';

    protected $fillable = [
        'turno_id', 'paciente_id', 'user_id', 'fecha', 'presion_sistolica', 'presion_diastolica',
        'frecuencia_cardiaca', 'frecuencia_respiratoria', 'temperatura', 'saturacion', 'peso', 'talla',
        'perimetro_abdominal', 'glucosa_capilar', 'hemoglobina_capilar', 'observaciones',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'temperatura' => 'float', 'peso' => 'float', 'talla' => 'float',
        'perimetro_abdominal' => 'float', 'glucosa_capilar' => 'float', 'hemoglobina_capilar' => 'float',
    ];

    /**
     * Rangos para resaltar valores fuera de lo habitual en adultos. Solo es una ayuda visual para
     * que el médico lo vea primero; la interpretación clínica es del profesional.
     */
    public const RANGOS = [
        'presion_sistolica' => [90, 139],
        'presion_diastolica' => [60, 89],
        'frecuencia_cardiaca' => [50, 100],
        'frecuencia_respiratoria' => [12, 20],
        'temperatura' => [35.5, 37.9],
        'saturacion' => [92, 100],
    ];

    public function turno(): BelongsTo    { return $this->belongsTo(Turno::class); }
    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }

    public function imc(): ?float
    {
        return $this->peso && $this->talla ? round($this->peso / ($this->talla ** 2), 1) : null;
    }

    public function presion(): ?string
    {
        return $this->presion_sistolica && $this->presion_diastolica ? "{$this->presion_sistolica}/{$this->presion_diastolica}" : null;
    }

    /** Campos con valores fuera de rango, para resaltarlos */
    public function fueraDeRango(): array
    {
        $fuera = [];
        foreach (self::RANGOS as $campo => [$min, $max]) {
            $v = $this->{$campo};
            if ($v !== null && ($v < $min || $v > $max)) {
                $fuera[] = $campo;
            }
        }

        return $fuera;
    }
}
