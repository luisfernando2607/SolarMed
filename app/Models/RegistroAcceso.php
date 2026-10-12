<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroAcceso extends Model
{
    protected $table = 'registro_accesos';

    public const UPDATED_AT = null;

    public const ACCIONES = [
        'ver' => 'Vio',
        'descargar' => 'Descargó',
        'crear' => 'Creó',
        'editar' => 'Editó',
        'eliminar' => 'Eliminó',
    ];

    protected $fillable = ['user_id', 'accion', 'recurso', 'recurso_id', 'paciente_id', 'detalle', 'ip', 'user_agent'];

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
}
