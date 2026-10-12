<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotaCredito extends Model
{
    protected $table = 'notas_credito';

    protected $fillable = [
        'factura_id', 'medico_id', 'user_id', 'numero', 'ruc_emisor', 'fecha', 'motivo',
        'subtotal', 'valor_modificacion', 'ambiente_sri', 'clave_acceso', 'numero_autorizacion',
        'fecha_autorizacion', 'xml_enviado_path', 'xml_firmado_path', 'xml_autorizado_path',
        'estado_sri', 'mensajes_sri', 'email_enviado_at', 'email_enviado_a',
    ];

    protected $casts = [
        'fecha' => 'date',
        'subtotal' => 'decimal:2',
        'valor_modificacion' => 'decimal:2',
        'fecha_autorizacion' => 'datetime',
        'mensajes_sri' => 'array',
        'email_enviado_at' => 'datetime',
    ];

    public function factura(): BelongsTo { return $this->belongsTo(Factura::class); }
    public function medico(): BelongsTo  { return $this->belongsTo(Medico::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
}
