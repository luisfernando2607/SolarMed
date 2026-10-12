<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoExamen extends Model
{
    protected $table = 'pedidos_examenes';

    protected $fillable = [
        'paciente_id', 'medico_id', 'consulta_id', 'user_id', 'fecha', 'laboratorio', 'imagen', 'otros',
        'diagnostico', 'codigo_cie10', 'indicaciones',
    ];

    protected $casts = [
        'fecha' => 'date',
        'laboratorio' => 'array',
        'imagen' => 'array',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }

    public function numero(): string
    {
        return str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function totalExamenes(): int
    {
        return count($this->laboratorio ?? []) + count($this->imagen ?? []) + ($this->otros ? 1 : 0);
    }
}
