<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PacienteArchivo extends Model
{
    protected $table = 'paciente_archivos';

    protected $fillable = [
        'paciente_id', 'medico_id', 'user_id', 'consulta_id', 'ecografia_id', 'categoria',
        'nombre_original', 'nombre_almacenado', 'ruta', 'mime_type', 'tamanio_kb', 'descripcion',
    ];

    public function paciente(): BelongsTo  { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo    { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo  { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function ecografia(): BelongsTo { return $this->belongsTo(Ecografia::class); }
    public function user(): BelongsTo      { return $this->belongsTo(User::class); }

    public function esImagen(): bool { return str_starts_with((string) $this->mime_type, 'image/'); }
}
