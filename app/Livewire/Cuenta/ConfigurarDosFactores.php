<?php

namespace App\Livewire\Cuenta;

use App\Livewire\Actions\Logout;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Compuerta de activación de la verificación en dos pasos para roles que la exigen (AM 5216-A).
 * Reutiliza el formulario del perfil y, al activarla, deja continuar al panel.
 */
class ConfigurarDosFactores extends Component
{
    /** El formulario hijo avisa cuando cambia el estado para refrescar el botón de continuar */
    #[On('dos-factores-cambiado')]
    public function refrescar(): void
    {
    }

    public function salir(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: false);
    }

    public function render()
    {
        return view('livewire.cuenta.configurar-dos-factores')->layout('layouts.guest');
    }
}
