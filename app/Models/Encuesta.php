<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Encuesta extends Model
{
    /** Preguntas de 1 a 5 estrellas: [campo => texto] */
    public const PREGUNTAS = [
        'p_trato' => '¿Cómo fue el trato que recibió del personal?',
        'p_espera' => '¿Qué le pareció el tiempo de espera?',
        'p_explicacion' => '¿El médico le explicó con claridad su diagnóstico y tratamiento?',
        'p_instalaciones' => '¿Cómo calificaría la limpieza y comodidad de las instalaciones?',
    ];

    public const CORTAS = [
        'p_trato' => 'Trato', 'p_espera' => 'Tiempo de espera', 'p_explicacion' => 'Explicación del médico', 'p_instalaciones' => 'Instalaciones',
    ];

    protected $fillable = [
        'token', 'consulta_id', 'paciente_id', 'medico_id', 'especialidad_id', 'expira_at', 'enviada_at', 'canal_envio',
        'respondida_at', 'canal_respuesta', 'p_trato', 'p_espera', 'p_explicacion', 'p_instalaciones', 'recomendacion', 'comentario',
    ];

    protected $casts = ['expira_at' => 'datetime', 'enviada_at' => 'datetime', 'respondida_at' => 'datetime'];

    public function consulta(): BelongsTo     { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }
    public function paciente(): BelongsTo     { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo       { return $this->belongsTo(Medico::class); }
    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class); }

    public static function nuevoToken(): string
    {
        return Str::random(40);
    }

    public function scopeRespondidas(Builder $q): Builder
    {
        return $q->whereNotNull('respondida_at');
    }

    public function disponible(): bool
    {
        return !$this->respondida_at && $this->expira_at->isFuture();
    }

    public function url(): string
    {
        return route('encuesta.responder', $this->token);
    }

    /** Promedio de las preguntas de 1 a 5 de esta respuesta */
    public function promedio(): ?float
    {
        $v = array_filter(array_map(fn ($c) => $this->{$c}, array_keys(self::PREGUNTAS)), fn ($x) => $x !== null);

        return $v ? round(array_sum($v) / count($v), 1) : null;
    }
}
