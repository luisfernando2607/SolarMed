<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Paciente;
use App\Models\Especialidad;
use App\Services\TurnoService;
use Illuminate\Support\Facades\Auth;

class CrearTurnoRecepcion extends Component
{
    public string $busqueda = '';
    public ?Paciente $pacienteSeleccionado = null;

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

    public ?string $error = null;
    public ?string $turnoCreado = null;
    public bool $creando = false;

    public function mount(): void
    {
        $this->especialidades = Especialidad::where('activo', true)->get()->toArray();
    }

    public function seleccionarPaciente(int $id): void
    {
        $paciente = Paciente::find($id);
        if (!$paciente) return;

        $this->pacienteSeleccionado = $paciente;
        $this->busqueda = $paciente->cedula;
        $this->nombres = $paciente->nombres;
        $this->apellidos = $paciente->apellidos ?? '';
        $this->cedula = $paciente->cedula;
        $this->fecha_nacimiento = $paciente->fecha_nacimiento?->format('Y-m-d') ?? '';
        $this->sexo = $paciente->sexo ?? '';
        $this->telefono = $paciente->telefono ?? '';
        $this->direccion = $paciente->direccion ?? '';
        $this->ciudad = $paciente->ciudad ?? '';
        $this->email = $paciente->email ?? '';
        $this->ocupacion = $paciente->ocupacion ?? '';
        $this->referido_por = $paciente->referido_por ?? '';
        $this->peso = (string) ($paciente->peso ?? '');
        $this->altura = (string) ($paciente->altura ?? '');
        $this->medicamentos = $paciente->medicamentos ?? '';
        $this->cirugias = $paciente->cirugias ?? '';
        $this->alergias = $paciente->alergias ?? '';
        $this->antecedentes = $paciente->antecedentes ?? '';
        $this->enfermedades_familiares = $paciente->enfermedades_familiares ?? '';
    }

    public function buscarPaciente(): void
    {
        $this->error = null;
        $this->pacienteSeleccionado = null;

        if (!$this->busqueda) return;

        $paciente = Paciente::where('cedula', $this->busqueda)
            ->orWhere('nombres', 'like', "%{$this->busqueda}%")
            ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
            ->first();

        if ($paciente) {
            $this->pacienteSeleccionado = $paciente;
            $this->nombres = $paciente->nombres;
            $this->apellidos = $paciente->apellidos ?? '';
            $this->cedula = $paciente->cedula;
            $this->fecha_nacimiento = $paciente->fecha_nacimiento?->format('Y-m-d') ?? '';
            $this->sexo = $paciente->sexo ?? '';
            $this->telefono = $paciente->telefono ?? '';
            $this->direccion = $paciente->direccion ?? '';
            $this->ciudad = $paciente->ciudad ?? '';
            $this->email = $paciente->email ?? '';
            $this->ocupacion = $paciente->ocupacion ?? '';
            $this->referido_por = $paciente->referido_por ?? '';
            $this->peso = (string) ($paciente->peso ?? '');
            $this->altura = (string) ($paciente->altura ?? '');
            $this->medicamentos = $paciente->medicamentos ?? '';
            $this->cirugias = $paciente->cirugias ?? '';
            $this->alergias = $paciente->alergias ?? '';
            $this->antecedentes = $paciente->antecedentes ?? '';
            $this->enfermedades_familiares = $paciente->enfermedades_familiares ?? '';
        } else {
            $this->nuevoPaciente();
        }
    }

    public function nuevoPaciente(): void
    {
        $this->pacienteSeleccionado = null;
        $this->nombres = '';
        $this->apellidos = '';
        $this->cedula = $this->busqueda;
        $this->fecha_nacimiento = '';
        $this->sexo = '';
        $this->telefono = '';
        $this->direccion = '';
        $this->ciudad = '';
        $this->email = '';
        $this->ocupacion = '';
        $this->referido_por = '';
        $this->peso = '';
        $this->altura = '';
        $this->medicamentos = '';
        $this->cirugias = '';
        $this->alergias = '';
        $this->antecedentes = '';
        $this->enfermedades_familiares = '';
    }

    public function updatedEspecialidadId($value): void
    {
        $this->motivosDisponibles = match ((int) $value) {
            1 => [
                'consulta_general' => 'Consulta general',
                'control' => 'Control de seguimiento',
                'dolor_cabeza' => 'Dolor de cabeza / fiebre',
                'dolor_abdominal' => 'Dolor abdominal',
                'infeccion' => 'Infección respiratoria',
                'vacunacion' => 'Vacunación',
                'certificado' => 'Certificado médico',
                'otro' => 'Otro',
            ],
            2 => [
                'consulta_ginecologica' => 'Consulta ginecológica',
                'ecografia' => 'Ecografía obstétrica',
                'control_prenatal' => 'Control prenatal',
                'planificacion' => 'Planificación familiar',
                'infertilidad' => 'Estudio de infertilidad',
                'menopausia' => 'Control de menopausia',
                'infeccion_gineco' => 'Infección ginecológica',
                'papanicolaou' => 'Papanicolaou / tamizaje',
                'otro' => 'Otro',
            ],
            default => [],
        };
        $this->motivo = '';
    }

    public function registrar(): void
    {
        $this->creando = true;
        $this->error = null;
        $this->turnoCreado = null;

        $this->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'cedula' => 'required|string|max:13',
            'telefono' => 'required|string|max:15',
            'especialidad_id' => 'required|exists:especialidades,id',
            'motivo' => 'required|string|max:100',
        ]);

        try {
            $turno = app(TurnoService::class)->registrarDesdeQR(
                array_merge($this->toArray(), [
                    'medico_id' => null,
                ])
            );

            $this->turnoCreado = "{$turno->prefijo}{$turno->numero_turno}";
            $this->resetExcept(['especialidades', 'turnoCreado', 'error', 'creando', 'busqueda']);
            $this->pacienteSeleccionado = null;
            $this->motivosDisponibles = [];
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }

        $this->creando = false;
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
        $pacientes = collect();
        if (!$this->pacienteSeleccionado && strlen($this->busqueda) >= 2) {
            $pacientes = Paciente::where('cedula', 'like', "%{$this->busqueda}%")
                ->orWhere('nombres', 'like', "%{$this->busqueda}%")
                ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
        }

        return view('livewire.crear-turno-recepcion', [
            'pacientes' => $pacientes,
        ])->layout('layouts.app');
    }
}
