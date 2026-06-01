<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ServicioTarifario;
use App\Models\Especialidad;

class GestionTarifario extends Component
{
    use WithPagination;

    public string $sortBy = 'especialidad_id';
    public string $sortDirection = 'asc';
    public bool $editMode = false;
    public ?int $editId = null;

    public $especialidad_id;
    public $nombre;
    public $descripcion;
    public $precio;
    public $activo = true;

    protected function rules(): array
    {
        return [
            'especialidad_id' => 'required|exists:especialidades,id',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:500',
            'precio' => 'required|numeric|min:0',
            'activo' => 'boolean',
        ];
    }

    protected $messages = [
        'especialidad_id.required' => 'Seleccione una especialidad.',
        'nombre.required' => 'El nombre del servicio es obligatorio.',
        'precio.required' => 'El precio es obligatorio.',
        'precio.numeric' => 'El precio debe ser un valor numérico.',
    ];

    public function resetForm(): void
    {
        $this->reset(['especialidad_id', 'nombre', 'descripcion', 'precio', 'activo', 'editMode', 'editId']);
        $this->activo = true;
    }

    public function crear(): void
    {
        $this->authorize('configuracion.editar');
        $this->resetForm();
        $this->editId = 0;
    }

    public function editar(ServicioTarifario $servicio): void
    {
        $this->authorize('configuracion.editar');
        $this->editMode = true;
        $this->editId = $servicio->id;
        $this->especialidad_id = $servicio->especialidad_id;
        $this->nombre = $servicio->nombre;
        $this->descripcion = $servicio->descripcion;
        $this->precio = (float) $servicio->precio;
        $this->activo = $servicio->activo;
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');
        $this->validate();

        $data = [
            'especialidad_id' => $this->especialidad_id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio,
            'activo' => $this->activo,
        ];

        if ($this->editMode) {
            ServicioTarifario::findOrFail($this->editId)->update($data);
            $this->dispatch('notify', message: 'Servicio actualizado.', type: 'success');
        } else {
            ServicioTarifario::create($data);
            $this->dispatch('notify', message: 'Servicio creado.', type: 'success');
        }

        $this->resetForm();
    }

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

    public function eliminar(ServicioTarifario $servicio): void
    {
        $this->authorize('configuracion.editar');
        $servicio->delete();
        $this->dispatch('notify', message: 'Servicio eliminado.', type: 'success');
    }

    public function render()
    {
        return view('livewire.gestion-tarifario', [
            'servicios' => ServicioTarifario::with('especialidad')
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(20),
            'especialidades' => Especialidad::where('activo', true)->get(),
        ])->layout('layouts.app');
    }
}
