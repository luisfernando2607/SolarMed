<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    protected $fillable = ['producto_id', 'codigo_lote', 'fecha_vencimiento', 'cantidad', 'proveedor', 'costo_unitario', 'registro_sanitario', 'created_by'];

    protected $casts = ['fecha_vencimiento' => 'date', 'cantidad' => 'integer', 'costo_unitario' => 'float'];

    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }

    public function vencido(): bool
    {
        return $this->fecha_vencimiento !== null && $this->fecha_vencimiento->lt(today());
    }

    public function porVencer(): bool
    {
        return !$this->vencido() && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->lte(today()->addDays(Producto::DIAS_POR_VENCER));
    }

    /** Lotes con existencias que se pueden entregar (no vencidos) */
    public function scopeDisponibles(Builder $q): Builder
    {
        return $q->where('cantidad', '>', 0)
            ->where(fn ($w) => $w->whereNull('fecha_vencimiento')->orWhereDate('fecha_vencimiento', '>=', today()));
    }
}
