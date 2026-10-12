<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medico extends Model {
    protected $fillable = ['user_id','nombres','apellidos','especialidad_id','colegiatura',
                           'telefono','email','firma_path','horario','activo',
                           'p12_path', 'p12_password_encrypted',
                           'ruc', 'razon_social', 'nombre_comercial', 'direccion_matriz',
                           'obligado_contabilidad', 'regimen_rimpe', 'sri_establecimiento', 'sri_pto_emi'];
    protected $casts    = ['horario' => 'array', 'activo' => 'boolean'];

    public function user(): BelongsTo        { return $this->belongsTo(User::class); }
    public function especialidad(): BelongsTo { return $this->belongsTo(Especialidad::class); }
    public function consultas(): HasMany      { return $this->hasMany(ExpedienteConsulta::class); }
    public function ecografias(): HasMany     { return $this->hasMany(Ecografia::class); }
    public function facturas(): HasMany       { return $this->hasMany(Factura::class); }

    /** Regímenes RIMPE válidos para el tag contribuyenteRimpe del SRI */
    public const REGIMENES_RIMPE = [
        'CONTRIBUYENTE RÉGIMEN RIMPE',
        'CONTRIBUYENTE NEGOCIO POPULAR - RÉGIMEN RIMPE',
    ];

    public function getNombreCompletoAttribute(): string {
        return "Dr. {$this->nombres} {$this->apellidos}";
    }
}
