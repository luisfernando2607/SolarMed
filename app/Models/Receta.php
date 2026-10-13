<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receta extends Model
{
    /** Vigencia máxima en consulta externa, también para antimicrobianos (ACESS-2023-0030 Arts. 18 b y 19) */
    public const VIGENCIA_CONSULTA_EXTERNA = 3;

    protected $fillable = [
        'numero', 'consulta_id', 'paciente_id', 'medico_id', 'fecha', 'ciudad', 'vigencia_dias',
        'diagnostico', 'codigo_cie10', 'alergias',
        'medicamentos', 'observaciones', 'signos_alarma', 'recomendaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'medicamentos' => 'array',
        'numero' => 'integer',
        'vigencia_dias' => 'integer',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo  { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function items(): HasMany      { return $this->hasMany(RecetaItem::class)->orderBy('orden'); }

    /** Receta con medicamentos por DCI (las anteriores a la norma solo tienen texto libre) */
    public function estructurada(): bool
    {
        return $this->numero !== null;
    }

    /** N.º secuencial; las recetas antiguas muestran su id */
    public function numeroFormateado(): string
    {
        return str_pad((string) ($this->numero ?? $this->id), 6, '0', STR_PAD_LEFT);
    }

    public function validaHasta(): ?Carbon
    {
        return $this->vigencia_dias ? $this->fecha->copy()->addDays($this->vigencia_dias) : null;
    }

    /** Lista corta de medicamentos para la ficha y la atención */
    public function resumen(): string
    {
        if ($this->estructurada()) {
            return $this->items->map(fn ($i) => trim("{$i->dci} {$i->concentracion}"))->implode(', ');
        }

        return collect($this->medicamentos)->pluck('medicamento')->filter()->implode(', ');
    }

    /**
     * Edad para la receta: en años, y en años y meses para menores de cinco años (Art. 5 b.ii).
     */
    public static function edadTexto(?Carbon $nacimiento, ?Carbon $al = null): ?string
    {
        if (!$nacimiento) {
            return null;
        }
        $al ??= today();
        $anios = (int) $nacimiento->diffInYears($al);
        if ($anios >= 5) {
            return "{$anios} años";
        }
        $meses = (int) $nacimiento->copy()->addYears($anios)->diffInMonths($al);
        $partes = [];
        if ($anios > 0) {
            $partes[] = $anios === 1 ? '1 año' : "{$anios} años";
        }
        $partes[] = $meses === 1 ? '1 mes' : "{$meses} meses";

        return implode(' y ', $partes);
    }
}
