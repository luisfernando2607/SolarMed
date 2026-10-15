<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Cierre de caja. Una vez cerrado, sus cifras no cambian: solo un administrador puede marcarlo
 * como reabierto (con motivo); las facturas vuelven al período abierto y un nuevo cierre lo reemplaza.
 */
class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    /** Formas de pago de las facturas (enum de la tabla facturas) */
    public const FORMAS_PAGO = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta'];

    /** Billetes y monedas de dólar para el contador de efectivo: [clave => [etiqueta, valor]] (claves sin punto para Livewire) */
    public const DENOMINACIONES = [
        'b100' => ['$100', 100], 'b50' => ['$50', 50], 'b20' => ['$20', 20], 'b10' => ['$10', 10], 'b5' => ['$5', 5], 'b1' => ['$1 billete', 1],
        'm100' => ['$1 moneda', 1], 'm50' => ['50 ¢', 0.50], 'm25' => ['25 ¢', 0.25], 'm10' => ['10 ¢', 0.10], 'm5' => ['5 ¢', 0.05], 'm1' => ['1 ¢', 0.01],
    ];

    /** Campos que se pueden cambiar al reabrir; todo lo demás es inmutable */
    private const CAMPOS_REAPERTURA = ['estado', 'reabierto_por', 'reabierto_at', 'motivo_reapertura', 'updated_at'];

    protected $fillable = [
        'fecha', 'user_id', 'desde', 'hasta', 'fondo_inicial', 'cobrado', 'devoluciones', 'efectivo_esperado',
        'contado_efectivo', 'diferencia', 'denominaciones', 'detalle', 'facturas_count', 'anuladas_count', 'notas_credito_total',
        'observaciones', 'estado', 'reabierto_por', 'reabierto_at', 'motivo_reapertura',
    ];

    protected $casts = [
        'fecha' => 'date', 'desde' => 'datetime', 'hasta' => 'datetime', 'reabierto_at' => 'datetime',
        'cobrado' => 'array', 'devoluciones' => 'array', 'denominaciones' => 'array', 'detalle' => 'array',
        'fondo_inicial' => 'float', 'efectivo_esperado' => 'float', 'contado_efectivo' => 'float',
        'diferencia' => 'float', 'notas_credito_total' => 'float',
    ];

    protected static function booted(): void
    {
        static::updating(function (CierreCaja $c) {
            $cambios = array_diff(array_keys($c->getDirty()), self::CAMPOS_REAPERTURA);
            if ($cambios || $c->getOriginal('estado') !== 'cerrado') {
                throw new LogicException('Un cierre de caja no se puede modificar; solo reabrir.');
            }
        });
        static::deleting(fn () => throw new LogicException('Un cierre de caja no se puede eliminar.'));
    }

    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function reabiertoPor(): BelongsTo { return $this->belongsTo(User::class, 'reabierto_por'); }
    public function facturas(): HasMany      { return $this->hasMany(Factura::class, 'cierre_id'); }

    public static function valorDenominacion(string $clave): float
    {
        return (float) (self::DENOMINACIONES[$clave][1] ?? 0);
    }

    public function totalCobrado(): float
    {
        return round(array_sum($this->cobrado ?? []), 2);
    }
}
