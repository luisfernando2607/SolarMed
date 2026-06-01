<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Paciente extends Model {
    protected $fillable = [
        'nombres','apellidos','cedula','fecha_nacimiento','sexo',
        'telefono','telefono_secundario','direccion','ciudad','provincia','email','foto_path',
        'ocupacion','referido_por',
        'grupo_sanguineo','peso','altura','medicamentos','cirugias','alergias','antecedentes','enfermedades_familiares',
        'fum','gestas','partos','cesareas','abortos','metodo_anticonceptivo',
    ];
    protected $casts = ['fecha_nacimiento' => 'date', 'fum' => 'date'];

    public function turnos(): HasMany    { return $this->hasMany(Turno::class); }
    public function citas(): HasMany     { return $this->hasMany(Cita::class); }
    public function consultas(): HasMany { return $this->hasMany(ExpedienteConsulta::class); }
    public function ecografias(): HasMany{ return $this->hasMany(Ecografia::class); }
    public function archivos(): HasMany  { return $this->hasMany(PacienteArchivo::class); }

    public function getNombreCompletoAttribute(): string {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function getEdadAttribute(): ?int {
        return $this->fecha_nacimiento?->age;
    }

    /** Semanas de gestación calculadas desde FUM */
    public function getSemanasGestacionAttribute(): ?int {
        return $this->fum ? (int) $this->fum->diffInWeeks(Carbon::today()) : null;
    }
}
