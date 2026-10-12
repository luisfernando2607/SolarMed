<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CertificadoMedico extends Model
{
    protected $table = 'certificados_medicos';

    protected $fillable = [
        'paciente_id', 'medico_id', 'consulta_id', 'user_id', 'tipo', 'fecha_emision', 'fecha_atencion', 'hora_atencion',
        'dias_reposo', 'reposo_desde', 'reposo_hasta', 'mostrar_diagnostico', 'diagnostico', 'codigo_cie10', 'observaciones',
        'codigo_verificacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_atencion' => 'date',
        'reposo_desde' => 'date',
        'reposo_hasta' => 'date',
        'mostrar_diagnostico' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CertificadoMedico $c) {
            // Código corto sin caracteres ambiguos (0/O, 1/I) para verificar el certificado
            do {
                $codigo = Str::upper(collect(str_split('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'))->random(10)->implode(''));
            } while (static::where('codigo_verificacion', $codigo)->exists());
            $c->codigo_verificacion ??= $codigo;
        });
    }

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
    public function consulta(): BelongsTo { return $this->belongsTo(ExpedienteConsulta::class, 'consulta_id'); }

    public function numero(): string
    {
        return str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function urlVerificacion(): string
    {
        return route('certificados.verificar', $this->codigo_verificacion);
    }
}
