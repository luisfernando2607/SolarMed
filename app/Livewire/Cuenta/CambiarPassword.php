<?php

namespace App\Livewire\Cuenta;

use App\Livewire\Actions\Logout;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/** Cambio de contraseña obligatorio cuando la cuenta tiene una clave asignada por otra persona */
class CambiarPassword extends Component
{
    public string $password_actual = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function guardar(): void
    {
        $user = auth()->user();

        $this->validate([
            'password_actual' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed', 'different:password_actual'],
        ], [
            'password_actual.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser distinta de la actual.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $user->forceFill([
            'password' => Hash::make($this->password),
            'debe_cambiar_password' => false,
            'password_cambiado_at' => now(),
        ])->save();

        app(AuditoriaService::class)->registrar('cambiar_password', 'cuenta', $user->id, null, 'Contraseña personal definida');
        $this->reset();

        $this->redirect(route('dashboard'), navigate: false);
    }

    public function salir(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: false);
    }

    public function render()
    {
        return view('livewire.cuenta.cambiar-password')->layout('layouts.guest');
    }
}
