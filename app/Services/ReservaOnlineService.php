<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Agenda en línea: horarios libres según la configuración de la agenda y reserva de citas desde
 * la página pública. Nunca modifica los datos de un paciente ya registrado ni revela si existe.
 */
class ReservaOnlineService
{
    /** Citas futuras reservadas en línea que puede tener a la vez una misma cédula */
    public const MAX_CITAS_POR_CEDULA = 2;

    public function __construct(private AjustesService $ajustes) {}

    public function habilitada(): bool
    {
        return (bool) $this->ajustes->get('agenda.reserva_online');
    }

    public function duracion(): int
    {
        return max(5, (int) $this->ajustes->get('agenda.duracion_defecto'));
    }

    /** Primer momento reservable: ahora + anticipación mínima */
    public function desde(): Carbon
    {
        return now()->addHours(max(0, (int) $this->ajustes->get('agenda.reserva_anticipacion_horas')));
    }

    /** @return Collection<int, Medico> */
    public function medicos(int $especialidadId, ?int $medicoId = null): Collection
    {
        return Medico::where('activo', true)->where('especialidad_id', $especialidadId)
            ->when($medicoId, fn ($q) => $q->where('id', $medicoId))
            ->orderBy('apellidos')->get();
    }

    /** Días laborables dentro de la ventana de reserva (Y-m-d) */
    public function fechas(): array
    {
        $laborables = array_map('intval', (array) $this->ajustes->get('agenda.dias_laborables'));
        $dias = max(1, (int) $this->ajustes->get('agenda.reserva_dias'));
        $fechas = [];
        for ($i = 0; $i <= $dias; $i++) {
            $dia = today()->addDays($i);
            if (in_array($dia->dayOfWeek, $laborables, true)) {
                $fechas[] = $dia->toDateString();
            }
        }

        return $fechas;
    }

    /**
     * Horarios libres de un día: ['09:00' => [medico_id, …], …]. Con $medicoId solo ese médico;
     * sin él, cualquier médico activo de la especialidad.
     */
    public function horarios(string $fecha, int $especialidadId, ?int $medicoId = null): array
    {
        if (!in_array($fecha, $this->fechas(), true)) {
            return [];
        }

        $medicos = $this->medicos($especialidadId, $medicoId);
        if ($medicos->isEmpty()) {
            return [];
        }

        $duracion = $this->duracion();
        $dia = Carbon::parse($fecha);
        $inicio = $dia->copy()->setTimeFromTimeString($this->ajustes->get('agenda.hora_inicio'));
        $fin = $dia->copy()->setTimeFromTimeString($this->ajustes->get('agenda.hora_fin'));
        $desde = $this->desde();

        $ocupadas = Cita::whereIn('medico_id', $medicos->pluck('id'))
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->whereBetween('fecha_hora', [$inicio->copy()->subHours(4), $fin])
            ->get(['medico_id', 'fecha_hora', 'duracion_min'])
            ->groupBy('medico_id');

        $horarios = [];
        for ($t = $inicio->copy(); $t->copy()->addMinutes($duracion)->lte($fin); $t->addMinutes($duracion)) {
            if ($t->lt($desde)) {
                continue;
            }
            $libres = $medicos->filter(fn (Medico $m) => !$this->choca($ocupadas->get($m->id, collect()), $t, $duracion))->pluck('id')->all();
            if ($libres) {
                $horarios[$t->format('H:i')] = $libres;
            }
        }

        return $horarios;
    }

    private function choca(Collection $citas, Carbon $inicio, int $duracion): bool
    {
        $fin = $inicio->copy()->addMinutes($duracion);

        return $citas->contains(fn (Cita $c) => $c->fecha_hora->lt($fin) && $c->fecha_hora->copy()->addMinutes($c->duracion_min)->gt($inicio));
    }

    /**
     * Reserva la cita. Vuelve a comprobar el horario dentro de un bloqueo para que dos personas no tomen el mismo.
     *
     * @param array{especialidad_id:int, medico_id:?int, fecha:string, hora:string, motivo:string, cedula:string,
     *              nombres:string, apellidos:string, telefono:string, email:?string, sexo:string, fecha_nacimiento:?string} $d
     * @throws ValidationException
     */
    public function reservar(array $d): Cita
    {
        return Cache::lock('reserva-online', 10)->block(5, fn () => DB::transaction(function () use ($d) {
            $libres = $this->horarios($d['fecha'], (int) $d['especialidad_id'], $d['medico_id'] ? (int) $d['medico_id'] : null)[$d['hora']] ?? [];
            if (!$libres) {
                throw ValidationException::withMessages(['hora' => 'Ese horario acaba de ser reservado por otra persona. Elija otro, por favor.']);
            }

            // Solo cuenta reservas hechas en línea: así el límite no revela si la cédula ya es paciente de la clínica
            $previas = Cita::where('origen', 'web')->whereIn('estado', ['pendiente', 'confirmada'])->where('fecha_hora', '>=', now())
                ->whereHas('paciente', fn ($q) => $q->where('cedula', $d['cedula']))->count();
            if ($previas >= self::MAX_CITAS_POR_CEDULA) {
                throw ValidationException::withMessages(['cedula' => 'Ya tiene ' . self::MAX_CITAS_POR_CEDULA . ' citas reservadas en línea. Para agendar otra, comuníquese con la clínica.']);
            }

            $notas = ['Reservada en línea.'];
            $paciente = Paciente::where('cedula', $d['cedula'])->first();
            if ($paciente) {
                // Nunca se cambian los datos de un paciente registrado desde la página pública:
                // si lo indicado difiere, queda en la nota para que el personal lo revise
                $distintos = array_filter([
                    'Nombre' => Str::lower(trim("{$d['nombres']} {$d['apellidos']}")) !== Str::lower(trim($paciente->nombre_completo)) ? "{$d['nombres']} {$d['apellidos']}" : null,
                    'Teléfono' => preg_replace('/\D/', '', $d['telefono']) !== preg_replace('/\D/', '', (string) $paciente->telefono) ? $d['telefono'] : null,
                    'Correo' => $d['email'] && Str::lower($d['email']) !== Str::lower((string) $paciente->email) ? $d['email'] : null,
                ]);
                if ($distintos) {
                    $notas[] = 'Datos indicados en la reserva (revisar): ' . collect($distintos)->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ') . '.';
                }
            } else {
                $paciente = Paciente::create([
                    'cedula' => $d['cedula'],
                    'nombres' => $d['nombres'],
                    'apellidos' => $d['apellidos'],
                    'telefono' => $d['telefono'],
                    'email' => $d['email'] ?: null,
                    'sexo' => $d['sexo'],
                    'fecha_nacimiento' => $d['fecha_nacimiento'] ?: null,
                ]);
            }

            $medico = Medico::findOrFail($libres[0]);
            do {
                $codigo = Str::upper(Str::random(6));
                $codigo = strtr($codigo, ['0' => '8', 'O' => 'P', '1' => '7', 'I' => 'K', 'L' => 'M']);
            } while (Cita::where('codigo_reserva', $codigo)->exists());

            $cita = Cita::create([
                'paciente_id' => $paciente->id,
                'medico_id' => $medico->id,
                'especialidad_id' => $medico->especialidad_id,
                'tipo' => mb_substr($d['motivo'], 0, 100),
                'fecha_hora' => Carbon::parse("{$d['fecha']} {$d['hora']}"),
                'duracion_min' => $this->duracion(),
                'estado' => 'pendiente',
                'origen' => 'web',
                'codigo_reserva' => $codigo,
                'notas' => mb_substr(implode(' ', $notas), 0, 500),
            ]);

            app(AuditoriaService::class)->registrar('crear', 'cita', $cita->id, $paciente->id, "Reserva en línea {$codigo}");

            return $cita;
        }));
    }
}
