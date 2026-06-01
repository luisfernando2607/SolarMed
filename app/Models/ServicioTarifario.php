<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicioTarifario extends Model {
    protected $table = 'servicios_tarifario';
    protected $fillable = ['especialidad_id', 'nombre', 'descripcion', 'precio', 'activo'];
    protected $casts = ['precio' => 'decimal:2', 'activo' => 'boolean'];

    public function especialidad(): BelongsTo {
        return $this->belongsTo(Especialidad::class);
    }
}
