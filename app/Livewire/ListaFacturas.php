<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Factura;
use Carbon\Carbon;

class ListaFacturas extends Component
{
    use WithPagination;

    public string $busqueda = '';
    public string $filtroEstado = '';
    public string $sortBy = 'created_at';
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
        $query = Factura::with('paciente', 'user', 'items')
            ->orderBy($this->sortBy, $this->sortDirection);

        // Secretaria solo ve facturas del día actual
        if (auth()->user()->hasRole('secretaria')) {
            $query->whereDate('fecha', Carbon::today());
        }

        if ($this->busqueda) {
            $query->where(function ($q) {
                $q->where('numero_factura', 'like', "%{$this->busqueda}%")
                  ->orWhereHas('paciente', function ($pq) {
                      $pq->where('nombres', 'like', "%{$this->busqueda}%")
                         ->orWhere('apellidos', 'like', "%{$this->busqueda}%")
                         ->orWhere('cedula', 'like', "%{$this->busqueda}%");
                  });
            });
        }

        if ($this->filtroEstado) {
            $query->where('estado', $this->filtroEstado);
        }

        return view('livewire.lista-facturas', [
            'facturas' => $query->paginate(15),
        ])->layout('layouts.app');
    }
}
