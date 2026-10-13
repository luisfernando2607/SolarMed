<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Paciente extends Model {
    protected $fillable = [
        'nombres','apellidos','cedula','fecha_nacimiento','sexo',
        'telefono','telefono_secundario','direccion','ciudad','provincia','canton','parroquia','email','foto_path',
        'ocupacion','referido_por',
        'grupo_sanguineo','peso','altura','medicamentos','cirugias','alergias','antecedentes','enfermedades_familiares',
        'fum','gestas','partos','cesareas','abortos','metodo_anticonceptivo',
        'identidad_reservada',
    ];
    protected $casts = ['fecha_nacimiento' => 'date', 'fum' => 'date', 'identidad_reservada' => 'boolean'];

    /** Archivo pasivo: historias sin atención por más de 5 años (AM 5216-A Art. 17) */
    public const ANIOS_ARCHIVO_ACTIVO = 5;

    public function turnos(): HasMany    { return $this->hasMany(Turno::class); }
    public function citas(): HasMany     { return $this->hasMany(Cita::class); }
    public function consultas(): HasMany { return $this->hasMany(ExpedienteConsulta::class); }
    public function ecografias(): HasMany{ return $this->hasMany(Ecografia::class); }
    public function archivos(): HasMany  { return $this->hasMany(PacienteArchivo::class); }
    public function embarazos(): HasMany { return $this->hasMany(Embarazo::class)->latest('fum'); }

    public function getNombreCompletoAttribute(): string {
        return "{$this->nombres} {$this->apellidos}";
    }

    /**
     * Nombre para documentos y pantallas compartidas. Con identidad reservada (AM 5216-A Art. 13:
     * VIH, tuberculosis activa, cáncer, enfermedades raras o catastróficas) se usa la cédula.
     */
    public function getNombreVisibleAttribute(): string {
        return $this->identidad_reservada ? "C.I. {$this->cedula}" : $this->nombre_completo;
    }

    /** Cédula con solo los 4 últimos dígitos, para mensajes que salen de la clínica */
    public function getCedulaEnmascaradaAttribute(): string {
        $cedula = (string) $this->cedula;
        return str_repeat('*', max(0, strlen($cedula) - 4)) . substr($cedula, -4);
    }

    /** Fecha de la última consulta o ecografía; sin atenciones, la de apertura de la historia */
    public function ultimaAtencion(): ?Carbon {
        $fechas = array_filter([
            $this->consultas()->max('fecha'),
            $this->ecografias()->max('fecha'),
        ]);

        return $fechas ? Carbon::parse(max($fechas)) : $this->created_at;
    }

    public function archivoPasivo(?Carbon $ultimaAtencion = null): bool {
        $ultima = $ultimaAtencion ?? $this->ultimaAtencion();
        return $ultima !== null && $ultima->lt(now()->subYears(self::ANIOS_ARCHIVO_ACTIVO));
    }

    public function getEdadAttribute(): ?int {
        return $this->fecha_nacimiento?->age;
    }

    /** Semanas de gestación calculadas desde FUM */
    public function getSemanasGestacionAttribute(): ?int {
        return $this->fum ? (int) $this->fum->diffInWeeks(Carbon::today()) : null;
    }
}
