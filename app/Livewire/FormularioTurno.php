<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Especialidad;
use App\Services\TurnoService;
use App\Exceptions\TurnoException;
use Illuminate\Support\Facades\RateLimiter;

class FormularioTurno extends Component
{
    public string $nombres = '';
    public string $apellidos = '';
    public string $cedula = '';
    public string $fecha_nacimiento = '';
    public string $sexo = '';
    public string $telefono = '';
    public string $direccion = '';
    public string $ciudad = '';
    public string $email = '';
    public string $ocupacion = '';
    public string $referido_por = '';

    public string $peso = '';
    public string $altura = '';
    public string $medicamentos = '';
    public string $cirugias = '';
    public string $alergias = '';
    public string $antecedentes = '';
    public string $enfermedades_familiares = '';

    public string $especialidad_id = '';
    public string $motivo = '';
    public ?array $motivosDisponibles = [];
    public ?array $especialidades = [];
    public ?string $turnoAsignado = null;
    public ?string $error = null;

    protected array $reglasPorPaso = [
        1 => [
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'cedula' => 'required|string|max:13',
            'fecha_nacimiento' => 'nullable|date',
            'sexo' => 'required|in:masculino,femenino,otro',
            'telefono' => 'required|string|max:15',
        ],
        2 => [
            'peso' => 'nullable|numeric|min:1|max:300',
            'altura' => 'nullable|numeric|min:0.3|max:2.5',
        ],
        3 => [
            'especialidad_id' => 'required|exists:especialidades,id',
            'motivo' => 'required|string|max:100',
        ],
    ];

    public function validarPaso(int $paso): bool
    {
        $reglas = $this->reglasPorPaso[$paso] ?? [];
        if (isset($reglas['motivo'])) {
            $reglas['motivo'] = ['required', 'string', 'max:100', \Illuminate\Validation\Rule::in(array_keys($this->motivosDisponibles ?? []))];
        }
        if ($reglas) {
            $this->validate($reglas);
        }
        return true;
    }

    public function mount(): void
    {
        $this->especialidades = Especialidad::where('activo', true)->get()->toArray();
    }

    public function updatedEspecialidadId($value): void
    {
        // Motivos configurados en la especialidad (Especialidades); la clave y el texto son el mismo
        $motivos = Especialidad::where('activo', true)->find((int) $value)?->motivos ?? [];
        $this->motivosDisponibles = array_combine($motivos, $motivos) ?: [];
        $this->motivo = '';
    }

    public function registrar(): void
    {
        $this->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'cedula' => 'required|string|max:13',
            'fecha_nacimiento' => 'nullable|date',
            'sexo' => 'required|in:masculino,femenino,otro',
            'telefono' => 'required|string|max:15',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:100',
            'ocupacion' => 'nullable|string|max:100',
            'referido_por' => 'nullable|string|max:100',
            'peso' => 'nullable|numeric|min:1|max:300',
            'altura' => 'nullable|numeric|min:0.3|max:2.5',
            'medicamentos' => 'nullable|string|max:1000',
            'cirugias' => 'nullable|string|max:1000',
            'alergias' => 'nullable|string|max:1000',
            'antecedentes' => 'nullable|string|max:2000',
            'enfermedades_familiares' => 'nullable|string|max:1000',
            'especialidad_id' => 'required|exists:especialidades,id',
            'motivo' => ['required', 'string', 'max:100', \Illuminate\Validation\Rule::in(array_keys($this->motivosDisponibles ?? []))],
        ]);

        // Límite por IP para evitar que el formulario público se use para llenar la base de datos
        $claveLimite = 'turno-publico:' . request()->ip();
        if (RateLimiter::tooManyAttempts($claveLimite, 10)) {
            $this->error = 'Demasiados registros desde este dispositivo. Intente más tarde o acérquese a recepción.';
            $this->turnoAsignado = null;
            return;
        }
        RateLimiter::hit($claveLimite, 3600);

        try {
            $turno = app(TurnoService::class)->registrarDesdeQR($this->toArray());

            $this->turnoAsignado = "{$turno->prefijo}{$turno->numero_turno}";
            $this->error = null;
            $this->reset(array_keys($this->except(['especialidades', 'turnoAsignado', 'error'])));
        } catch (TurnoException $e) {
            $this->error = $e->getMessage();
            $this->turnoAsignado = null;
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'No se pudo registrar el turno. Intente nuevamente o acérquese a recepción.';
            $this->turnoAsignado = null;
        }
    }

    private function toArray(): array
    {
        return [
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'cedula' => $this->cedula,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'sexo' => $this->sexo,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'email' => $this->email,
            'ocupacion' => $this->ocupacion,
            'referido_por' => $this->referido_por,
            'peso' => $this->peso,
            'altura' => $this->altura,
            'medicamentos' => $this->medicamentos,
            'cirugias' => $this->cirugias,
            'alergias' => $this->alergias,
            'antecedentes' => $this->antecedentes,
            'enfermedades_familiares' => $this->enfermedades_familiares,
            'especialidad_id' => $this->especialidad_id,
            'motivo' => $this->motivo,
        ];
    }

    public function render()
    {
        return view('livewire.formulario-turno');
    }
}
