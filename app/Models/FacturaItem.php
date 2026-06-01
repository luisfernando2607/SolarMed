<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaItem extends Model {
    protected $table = 'factura_items';
    protected $fillable = ['factura_id', 'servicio_id', 'descripcion', 'cantidad', 'precio_unitario', 'subtotal'];
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function factura(): BelongsTo  { return $this->belongsTo(Factura::class); }
    public function servicio(): BelongsTo { return $this->belongsTo(ServicioTarifario::class, 'servicio_id'); }
}
