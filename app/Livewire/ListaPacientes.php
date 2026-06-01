<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Paciente;
use App\Models\ExpedienteConsulta;
use Illuminate\Validation\Rule;

class ListaPacientes extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $sortBy = 'created_at';
    public string $sortDirection = 'desc';
    public ?Paciente $selected = null;
    public $turnoActivo = null;
    public $consultas = [];

    public bool $editMode = false;
    public $editId = null;

    public $nombres;
    public $apellidos;
    public $cedula;
    public $fecha_nacimiento;
    public $sexo;
    public $telefono;
    public $telefono_secundario;
    public $direccion;
    public $ciudad;
    public $email;
    public $ocupacion;
    public $referido_por;
    public $grupo_sanguineo;
    public $peso;
    public $altura;
    public $medicamentos;
    public $cirugias;
    public $alergias;
    public $antecedentes;
    public $enfermedades_familiares;
    public $fum;
    public $gestas;
    public $partos;
    public $cesareas;
    public $abortos;
    public $metodo_anticonceptivo;

    protected function rules(): array
    {
        return [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'cedula' => ['required', 'string', 'max:13', Rule::unique('pacientes', 'cedula')->ignore($this->editId)],
            'fecha_nacimiento' => 'nullable|date',
            'sexo' => 'required|in:masculino,femenino',
            'telefono' => 'nullable|string|max:20',
            'telefono_secundario' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:500',
            'ciudad' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'ocupacion' => 'nullable|string|max:255',
            'referido_por' => 'nullable|string|max:255',
            'grupo_sanguineo' => 'nullable|string|max:5',
            'peso' => 'nullable|numeric|min:0|max:500',
            'altura' => 'nullable|numeric|min:0|max:3',
            'medicamentos' => 'nullable|string|max:2000',
            'cirugias' => 'nullable|string|max:2000',
            'alergias' => 'nullable|string|max:2000',
            'antecedentes' => 'nullable|string|max:2000',
            'enfermedades_familiares' => 'nullable|string|max:2000',
            'fum' => 'nullable|date',
            'gestas' => 'nullable|integer|min:0|max:50',
            'partos' => 'nullable|integer|min:0|max:50',
            'cesareas' => 'nullable|integer|min:0|max:50',
            'abortos' => 'nullable|integer|min:0|max:50',
            'metodo_anticonceptivo' => 'nullable|string|max:255',
        ];
    }

    protected $messages = [
        'nombres.required' => 'El nombre es obligatorio.',
        'apellidos.required' => 'Los apellidos son obligatorios.',
        'cedula.required' => 'La cédula es obligatoria.',
        'cedula.unique' => 'Esta cédula ya está registrada.',
        'sexo.required' => 'Seleccione el sexo del paciente.',
    ];

    public function ordenarPor($field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function seleccionar(Paciente $paciente): void
    {
        $this->editMode = false;
        $this->selected = $paciente;
        $this->turnoActivo = $paciente->turnos()
            ->whereIn('estado', ['esperando', 'atencion'])
            ->whereDate('fecha', today())
            ->latest()
            ->first();

        $this->consultas = ExpedienteConsulta::where('paciente_id', $paciente->id)
            ->with('medico')
            ->orderBy('fecha', 'desc')
            ->take(10)
            ->get();
    }

    public function cerrarDetalle(): void
    {
        $this->selected = null;
        $this->editMode = false;
        $this->turnoActivo = null;
        $this->consultas = [];
    }

    public function editar(Paciente $paciente): void
    {
        $this->authorize('pacientes.editar');
        $this->editMode = true;
        $this->editId = $paciente->id;
        $this->selected = $paciente;

        $this->nombres = $paciente->nombres;
        $this->apellidos = $paciente->apellidos;
        $this->cedula = $paciente->cedula;
        $this->fecha_nacimiento = $paciente->fecha_nacimiento?->format('Y-m-d');
        $this->sexo = $paciente->sexo;
        $this->telefono = $paciente->telefono;
        $this->telefono_secundario = $paciente->telefono_secundario;
        $this->direccion = $paciente->direccion;
        $this->ciudad = $paciente->ciudad;
        $this->email = $paciente->email;
        $this->ocupacion = $paciente->ocupacion;
        $this->referido_por = $paciente->referido_por;
        $this->grupo_sanguineo = $paciente->grupo_sanguineo;
        $this->peso = $paciente->peso;
        $this->altura = $paciente->altura;
        $this->medicamentos = $paciente->medicamentos;
        $this->cirugias = $paciente->cirugias;
        $this->alergias = $paciente->alergias;
        $this->antecedentes = $paciente->antecedentes;
        $this->enfermedades_familiares = $paciente->enfermedades_familiares;
        $this->fum = $paciente->fum?->format('Y-m-d');
        $this->gestas = $paciente->gestas;
        $this->partos = $paciente->partos;
        $this->cesareas = $paciente->cesareas;
        $this->abortos = $paciente->abortos;
        $this->metodo_anticonceptivo = $paciente->metodo_anticonceptivo;
    }

    public function cancelarEdicion(): void
    {
        $this->editMode = false;
        $this->editId = null;
        $this->reset(array_keys($this->rules()));
    }

    public function guardarPaciente(): void
    {
        $this->authorize('pacientes.editar');
        $this->validate();

        $paciente = Paciente::findOrFail($this->editId);
        $paciente->update([
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'cedula' => $this->cedula,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'sexo' => $this->sexo,
            'telefono' => $this->telefono,
            'telefono_secundario' => $this->telefono_secundario,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'email' => $this->email,
            'ocupacion' => $this->ocupacion,
            'referido_por' => $this->referido_por,
            'grupo_sanguineo' => $this->grupo_sanguineo,
            'peso' => $this->peso,
            'altura' => $this->altura,
            'medicamentos' => $this->medicamentos,
            'cirugias' => $this->cirugias,
            'alergias' => $this->alergias,
            'antecedentes' => $this->antecedentes,
            'enfermedades_familiares' => $this->enfermedades_familiares,
            'fum' => $this->fum,
            'gestas' => $this->gestas !== null ? (int) $this->gestas : null,
            'partos' => $this->partos !== null ? (int) $this->partos : null,
            'cesareas' => $this->cesareas !== null ? (int) $this->cesareas : null,
            'abortos' => $this->abortos !== null ? (int) $this->abortos : null,
            'metodo_anticonceptivo' => $this->metodo_anticonceptivo,
        ]);

        $this->cerrarDetalle();

        $this->dispatch('notify', message: 'Paciente actualizado exitosamente.', type: 'success');
    }

    public function eliminarPaciente(Paciente $paciente): void
    {
        $this->authorize('pacientes.eliminar');

        $paciente->delete();

        if ($this->selected && $this->selected->id === $paciente->id) {
            $this->cerrarDetalle();
        }

        $this->dispatch('notify', message: 'Paciente eliminado exitosamente.', type: 'success');
    }

    public function render()
    {
        $query = Paciente::query();

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('nombres', 'like', "%{$this->busqueda}%")
                  ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
                  ->orWhere('cedula', 'like', "%{$this->busqueda}%")
                  ->orWhere('telefono', 'like', "%{$this->busqueda}%");
            });
        }

        $pacientes = $query->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);

        return view('livewire.lista-pacientes', [
            'pacientes' => $pacientes,
        ])->layout('layouts.app');
    }
}
