<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RegistroAcceso;
use App\Models\User;

class Auditoria extends Component
{
    use WithPagination;

    public string $paciente = '';
    public string $usuario = '';
    public string $accion = '';
    public string $desde = '';
    public string $hasta = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset(['paciente', 'usuario', 'accion', 'desde', 'hasta']);
        $this->resetPage();
    }

    public function render()
    {
        $query = RegistroAcceso::with('user', 'paciente')->latest('created_at')->latest('id');

        if ($this->paciente !== '') {
            $query->whereHas('paciente', fn ($q) => $q
                ->where('cedula', 'like', "%{$this->paciente}%")
                ->orWhere('nombres', 'like', "%{$this->paciente}%")
                ->orWhere('apellidos', 'like', "%{$this->paciente}%"));
        }
        if ($this->usuario !== '') {
            $query->where('user_id', (int) $this->usuario);
        }
        if (array_key_exists($this->accion, RegistroAcceso::ACCIONES)) {
            $query->where('accion', $this->accion);
        }
        if ($this->desde !== '' && strtotime($this->desde)) {
            $query->whereDate('created_at', '>=', $this->desde);
        }
        if ($this->hasta !== '' && strtotime($this->hasta)) {
            $query->whereDate('created_at', '<=', $this->hasta);
        }

        return view('livewire.auditoria', [
            'registros' => $query->paginate(25),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
        ])->layout('layouts.app');
    }
}
