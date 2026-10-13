<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    public const TIPOS = ['medicamento' => 'Medicamento', 'insumo' => 'Insumo', 'material' => 'Material'];

    /** Días antes del vencimiento en que un lote se marca "por vencer" */
    public const DIAS_POR_VENCER = 60;

    protected $fillable = ['nombre', 'tipo', 'dci', 'forma_farmaceutica', 'concentracion', 'presentacion', 'unidad', 'stock_minimo', 'activo', 'notas'];

    protected $casts = ['activo' => 'boolean', 'stock_minimo' => 'integer'];

    public function lotes(): HasMany       { return $this->hasMany(Lote::class); }
    public function movimientos(): HasMany { return $this->hasMany(MovimientoInventario::class); }

    /** Nombre con concentración y forma, como se ve en las listas */
    public function descripcion(): string
    {
        return trim(implode(' ', array_filter([$this->nombre, $this->concentracion, $this->forma_farmaceutica ? "({$this->forma_farmaceutica})" : null])));
    }
}
