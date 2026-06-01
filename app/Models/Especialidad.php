<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Especialidad extends Model {
    protected $table = 'especialidades';
    protected $fillable = ['nombre', 'codigo', 'color_agenda', 'activo'];
    protected $casts    = ['activo' => 'boolean'];

    public function medicos(): HasMany  { return $this->hasMany(Medico::class); }
    public function turnos(): HasMany   { return $this->hasMany(Turno::class); }
    public function citas(): HasMany    { return $this->hasMany(Cita::class); }
    public function servicios(): HasMany { return $this->hasMany(ServicioTarifario::class); }
}
