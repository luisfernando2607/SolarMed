<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/** Kardex: cada movimiento se registra una sola vez y no se puede modificar ni borrar */
class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public const UPDATED_AT = null;

    public const TIPOS = ['entrada' => 'Entrada', 'salida' => 'Salida', 'ajuste' => 'Ajuste', 'baja' => 'Baja'];

    public const MOTIVOS = [
        'entrada' => ['compra' => 'Compra', 'donacion' => 'Donación', 'devolucion' => 'Devolución'],
        'salida' => ['uso_atencion' => 'Uso en atención', 'entrega_paciente' => 'Entrega a paciente', 'uso_interno' => 'Uso interno'],
        'baja' => ['vencido' => 'Vencido', 'danado' => 'Dañado', 'perdida' => 'Pérdida'],
        'ajuste' => ['conteo_mas' => 'Ajuste por conteo (sobrante)', 'conteo_menos' => 'Ajuste por conteo (faltante)'],
    ];

    protected $fillable = ['producto_id', 'lote_id', 'tipo', 'motivo', 'cantidad', 'saldo_lote_despues', 'user_id', 'paciente_id', 'consulta_id', 'observacion'];

    protected $casts = ['cantidad' => 'integer', 'saldo_lote_despues' => 'integer'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Un movimiento de inventario no se puede modificar.'));
        static::deleting(fn () => throw new LogicException('Un movimiento de inventario no se puede eliminar.'));
    }

    public function producto(): BelongsTo { return $this->belongsTo(Producto::class); }
    public function lote(): BelongsTo     { return $this->belongsTo(Lote::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }

    public function motivoTexto(): string
    {
        return self::MOTIVOS[$this->tipo][$this->motivo] ?? str_replace('_', ' ', $this->motivo);
    }

    /** Signo con el que el movimiento afecta al stock */
    public function signo(): int
    {
        return match ($this->tipo) {
            'entrada' => 1,
            'ajuste' => $this->motivo === 'conteo_mas' ? 1 : -1,
            default => -1,
        };
    }
}
