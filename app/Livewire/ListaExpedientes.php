<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ExpedienteConsulta;
use App\Models\Paciente;

class ListaExpedientes extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $sortBy = 'fecha';
    public string $sortDirection = 'desc';

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

    public function render()
    {
        $query = ExpedienteConsulta::with('paciente', 'medico');

        if ($this->busqueda) {
            $query->whereHas('paciente', function ($q) {
                $q->where('nombres', 'like', "%{$this->busqueda}%")
                  ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
                  ->orWhere('cedula', 'like', "%{$this->busqueda}%");
            });
        }

        $consultas = $query->orderBy($this->sortBy, $this->sortDirection)->paginate(15);

        return view('livewire.lista-expedientes', [
            'consultas' => $consultas,
        ])->layout('layouts.app');
    }
}
