<?php

namespace App\Livewire;

use App\Models\Cita;
use App\Models\Especialidad;
use App\Services\ReservaOnlineService;
use App\Support\CedulaEcuador;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Página pública para que el paciente reserve su cita eligiendo especialidad, médico, día y hora */
class ReservaCita extends Component
{
    public const RESERVAS_POR_HORA = 5;

    public int $paso = 1;

    public string $especialidad_id = '';
    public string $medico_id = '';   // '' = cualquier médico disponible
    public string $fecha = '';
    public string $hora = '';
    public string $motivo = '';

    public string $cedula = '';
    public string $nombres = '';
    public string $apellidos = '';
    public string $telefono = '';
    public string $email = '';
    public string $sexo = '';
    public string $fecha_nacimiento = '';

    /** Trampa para robots: un campo oculto que una persona nunca llena */
    public string $sitio_web = '';

    #[Locked]
    public ?int $citaId = null;

    private function servicio(): ReservaOnlineService
    {
        return app(ReservaOnlineService::class);
    }

    public function elegirEspecialidad(int $id): void
    {
        abort_unless(Especialidad::where('activo', true)->whereKey($id)->exists(), 404);
        $this->especialidad_id = (string) $id;
        $this->reset(['medico_id', 'fecha', 'hora', 'motivo']);
        $this->paso = 2;
    }

    public function elegirMedico(string $id): void
    {
        abort_unless($id === '' || $this->servicio()->medicos((int) $this->especialidad_id, (int) $id)->isNotEmpty(), 404);
        $this->medico_id = $id;
        $this->reset(['fecha', 'hora']);
        $this->fecha = collect($this->servicio()->fechas())->first(fn ($f) => $this->horariosDe($f)) ?? '';
        $this->paso = 3;
    }

    public function elegirFecha(string $fecha): void
    {
        $this->fecha = in_array($fecha, $this->servicio()->fechas(), true) ? $fecha : '';
        $this->hora = '';
    }

    public function elegirHora(string $hora): void
    {
        abort_unless(isset($this->horariosDe($this->fecha)[$hora]), 404);
        $this->hora = $hora;
        $this->paso = 4;
    }

    public function volver(int $paso): void
    {
        if ($paso >= 1 && $paso < $this->paso && !$this->citaId) {
            $this->paso = $paso;
        }
    }

    private function horariosDe(string $fecha): array
    {
        return $fecha && $this->especialidad_id
            ? $this->servicio()->horarios($fecha, (int) $this->especialidad_id, $this->medico_id !== '' ? (int) $this->medico_id : null)
            : [];
    }

    private function motivos(): array
    {
        $motivos = Especialidad::find((int) $this->especialidad_id)?->motivos ?: [];

        return $motivos ?: ['Consulta'];
    }

    public function reservar(): void
    {
        abort_unless($this->servicio()->habilitada(), 403);

        $this->cedula = preg_replace('/\D/', '', $this->cedula);
        $this->validate([
            'motivo' => ['required', Rule::in($this->motivos())],
            'cedula' => ['required', function ($attr, $valor, $fallo) {
                if (!CedulaEcuador::valida($valor)) {
                    $fallo('La cédula no es válida. Revise los 10 dígitos.');
                }
            }],
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'telefono' => ['required', 'regex:/^(\+?593|0)?9\d{8}$/'],
            'email' => 'nullable|email|max:120',
            'sexo' => 'required|in:femenino,masculino',
            'fecha_nacimiento' => 'nullable|date|before:today|after:1900-01-01',
        ], [
            'cedula.required' => 'Ingrese su número de cédula.',
            'nombres.required' => 'Ingrese sus nombres.',
            'apellidos.required' => 'Ingrese sus apellidos.',
            'telefono.required' => 'Ingrese su número de celular.',
            'telefono.regex' => 'Ingrese un celular ecuatoriano, por ejemplo 0991234567.',
            'motivo.required' => 'Elija el motivo de la cita.',
            'sexo.required' => 'Seleccione el sexo.',
        ], [
            'nombres' => 'los nombres', 'apellidos' => 'los apellidos', 'telefono' => 'el celular',
            'email' => 'el correo', 'fecha_nacimiento' => 'la fecha de nacimiento',
        ]);

        $clave = 'reserva-online:' . request()->ip();
        if ($this->sitio_web !== '' || RateLimiter::tooManyAttempts($clave, self::RESERVAS_POR_HORA)) {
            throw ValidationException::withMessages(['cedula' => 'No pudimos registrar la reserva. Intente más tarde o comuníquese con la clínica.']);
        }

        try {
            $cita = $this->servicio()->reservar([
                'especialidad_id' => (int) $this->especialidad_id,
                'medico_id' => $this->medico_id !== '' ? (int) $this->medico_id : null,
                'fecha' => $this->fecha,
                'hora' => $this->hora,
                'motivo' => $this->motivo,
                'cedula' => $this->cedula,
                'nombres' => trim($this->nombres),
                'apellidos' => trim($this->apellidos),
                'telefono' => trim($this->telefono),
                'email' => trim($this->email) ?: null,
                'sexo' => $this->sexo,
                'fecha_nacimiento' => $this->fecha_nacimiento ?: null,
            ]);
        } catch (ValidationException $e) {
            if (isset($e->errors()['hora'])) {
                // El horario se ocupó mientras llenaba los datos: vuelve a elegir hora
                $this->hora = '';
                $this->paso = 3;
            }
            throw $e;
        }

        RateLimiter::hit($clave, 3600);
        $this->citaId = $cita->id;
        $this->paso = 5;
    }

    public function render()
    {
        $servicio = $this->servicio();
        $especialidad = $this->especialidad_id ? Especialidad::find((int) $this->especialidad_id) : null;
        $cita = $this->citaId ? Cita::with('medico', 'especialidad')->find($this->citaId) : null;

        return view('livewire.reserva-cita', [
            'habilitada' => $servicio->habilitada(),
            'especialidades' => Especialidad::where('activo', true)->whereHas('medicos', fn ($q) => $q->where('activo', true))->orderBy('nombre')->get(),
            'especialidad' => $especialidad,
            'medicos' => $especialidad ? $servicio->medicos($especialidad->id) : collect(),
            'medico' => $this->medico_id !== '' ? $servicio->medicos((int) $this->especialidad_id, (int) $this->medico_id)->first() : null,
            'fechas' => $this->paso === 3 ? collect($servicio->fechas())->mapWithKeys(fn ($f) => [$f => count($this->horariosDe($f))])->all() : [],
            'horarios' => $this->paso === 3 ? $this->horariosDe($this->fecha) : [],
            'motivos' => $this->especialidad_id ? $this->motivos() : [],
            'cita' => $cita,
            'fechaTexto' => fn (string $f) => ucfirst(Carbon::parse($f)->locale('es')->translatedFormat('l j \d\e F')),
        ]);
    }
}
