<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Embarazo extends Model
{
    public const DESENLACES = [
        'parto' => 'Parto vaginal',
        'cesarea' => 'Cesárea',
        'aborto' => 'Aborto',
        'ectopico' => 'Embarazo ectópico',
        'traslado' => 'Continúa control en otra institución',
    ];

    protected $fillable = ['paciente_id', 'medico_id', 'fum', 'fpp', 'estado', 'fecha_fin', 'desenlace', 'checklist', 'observaciones'];

    protected $casts = [
        'fum' => 'date',
        'fpp' => 'date',
        'fecha_fin' => 'date',
        'checklist' => 'array',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function controles(): HasMany  { return $this->hasMany(ControlPrenatal::class)->orderBy('fecha')->orderBy('id'); }

    /** Edad gestacional por FUM a una fecha: ['semanas' => int, 'dias' => int] */
    public function edadGestacional(?Carbon $fecha = null): array
    {
        $dias = max(0, (int) $this->fum->diffInDays($fecha ?? Carbon::today()));
        return ['semanas' => intdiv($dias, 7), 'dias' => $dias % 7];
    }

    public function trimestre(?Carbon $fecha = null): int
    {
        $semanas = $this->edadGestacional($fecha)['semanas'];
        return $semanas < 14 ? 1 : ($semanas < 28 ? 2 : 3);
    }
}
