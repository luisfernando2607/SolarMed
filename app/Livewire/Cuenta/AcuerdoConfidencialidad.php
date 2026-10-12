<?php

namespace App\Livewire\Cuenta;

use App\Livewire\Actions\Logout;
use App\Services\AuditoriaService;
use Livewire\Component;

/** Acuerdo de confidencialidad del personal (AM 5216-A, Disposición General Segunda) */
class AcuerdoConfidencialidad extends Component
{
    public bool $acepto = false;

    public function aceptar(): void
    {
        $this->validate(['acepto' => 'accepted'], ['acepto.accepted' => 'Debe marcar la casilla para continuar.']);

        $user = auth()->user();
        $user->forceFill(['confidencialidad_aceptada_at' => now(), 'confidencialidad_ip' => request()->ip()])->save();
        app(AuditoriaService::class)->registrar('aceptar_confidencialidad', 'cuenta', $user->id, null, 'Acuerdo de confidencialidad AM 5216-A');

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function salir(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: false);
    }

    public function render()
    {
        return view('livewire.cuenta.acuerdo-confidencialidad')->layout('layouts.guest');
    }
}
