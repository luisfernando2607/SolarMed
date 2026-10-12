<?php

namespace App\Services;

use App\Models\Turno;
use App\Models\Paciente;
use App\Models\Especialidad;
use App\Exceptions\TurnoException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TurnoService
{
    public function siguienteNumero(int $especialidadId, string $fecha): int
    {
        $ultimo = Turno::where('especialidad_id', $especialidadId)
            ->whereDate('fecha', $fecha)
            ->max('numero_turno');
        return ($ultimo ?? 0) + 1;
    }

    /**
     * @param bool $sobrescribirPaciente true cuando lo registra personal autenticado (recepción).
     *        Desde el formulario público solo se completan los campos vacíos de un paciente existente.
     */
    public function registrarDesdeQR(array $datos, bool $sobrescribirPaciente = false): Turno
    {
        $limite = (int) config('clinica.turno_rate_limit', 3);
        $hoy = Carbon::today()->toDateString();
        $cedula = $datos['cedula'];
        $espId = (int) $datos['especialidad_id'];

        $turnosHoy = Turno::where('cedula', $cedula)
            ->whereDate('fecha', $hoy)
            ->whereNotIn('estado', ['cancelado'])
            ->count();

        if ($turnosHoy >= $limite) {
            throw new TurnoException("Ha alcanzado el límite de $limite turnos por día.");
        }

        $datosPaciente = [
            'nombres' => $datos['nombres'],
            'apellidos' => $datos['apellidos'] ?? '',
            'cedula' => $cedula,
            'fecha_nacimiento' => !empty($datos['fecha_nacimiento']) ? $datos['fecha_nacimiento'] : null,
            'sexo' => $datos['sexo'] ?? 'otro',
            'telefono' => $datos['telefono'],
            'direccion' => $datos['direccion'] ?? null,
            'ciudad' => $datos['ciudad'] ?? null,
            'email' => $datos['email'] ?? null,
            'ocupacion' => $datos['ocupacion'] ?? null,
            'referido_por' => $datos['referido_por'] ?? null,
            'peso' => !empty($datos['peso']) ? (float) $datos['peso'] : null,
            'altura' => !empty($datos['altura']) ? (float) $datos['altura'] : null,
            'medicamentos' => $datos['medicamentos'] ?? null,
            'cirugias' => $datos['cirugias'] ?? null,
            'alergias' => $datos['alergias'] ?? null,
            'antecedentes' => $datos['antecedentes'] ?? null,
            'enfermedades_familiares' => $datos['enfermedades_familiares'] ?? null,
        ];

        return DB::transaction(function () use ($datos, $datosPaciente, $sobrescribirPaciente, $cedula, $espId, $hoy) {
            // Bloquea la especialidad para que dos registros simultáneos no tomen el mismo número
            $especialidad = Especialidad::lockForUpdate()->findOrFail($espId);
            $prefijo = mb_strtoupper($especialidad->prefijo_turno ?: mb_substr($especialidad->codigo, 0, 1));
            $numero = $this->siguienteNumero($espId, $hoy);

            $paciente = Paciente::where('cedula', $cedula)->first();

            if (!$paciente) {
                $paciente = Paciente::create($datosPaciente);
            } elseif ($sobrescribirPaciente) {
                $paciente->update($datosPaciente);
            } else {
                $vacios = array_filter(
                    $datosPaciente,
                    fn ($valor, $campo) => $valor !== null && $valor !== '' && blank($paciente->{$campo}),
                    ARRAY_FILTER_USE_BOTH
                );
                if ($vacios) {
                    $paciente->update($vacios);
                }
            }

            return Turno::create([
                'numero_turno' => $numero,
                'prefijo' => $prefijo,
                'especialidad_id' => $espId,
                'paciente_id' => $paciente->id,
                'nombre_temporal' => "{$datos['nombres']} {$datos['apellidos']}",
                'cedula' => $cedula,
                'telefono' => $datos['telefono'] ?? null,
                'motivo' => $datos['motivo'] ?? null,
                'estado' => 'esperando',
                'fecha' => $hoy,
                'hora_registro' => now(),
            ]);
        });
    }

    public function llamar(Turno $turno): void
    {
        $turno->update(['estado' => 'en_atencion', 'hora_llamado' => now()]);
    }

    public function completar(Turno $turno): void
    {
        $turno->update(['estado' => 'completado', 'hora_fin' => now()]);
    }

    public function cancelar(Turno $turno): void
    {
        $turno->update(['estado' => 'cancelado', 'hora_fin' => now()]);
    }
}
