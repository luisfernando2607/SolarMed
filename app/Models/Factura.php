<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factura extends Model {
    protected $fillable = [
        'numero_factura', 'clave_acceso', 'numero_autorizacion', 'ambiente_sri',
        'paciente_id', 'medico_id', 'especialidad_id',
        'turno_id', 'cita_id', 'user_id', 'fecha',
        'subtotal', 'descuento', 'total', 'forma_pago',
        'referencia_pago', 'estado', 'estado_sri',
        'xml_enviado_path', 'xml_autorizado_path', 'observaciones',
    ];
    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function paciente(): BelongsTo     { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo       { return $this->belongsTo(Medico::class); }
    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class); }
    public function turno(): BelongsTo        { return $this->belongsTo(Turno::class); }
    public function user(): BelongsTo         { return $this->belongsTo(User::class); }
    public function items(): HasMany          { return $this->hasMany(FacturaItem::class); }
}
