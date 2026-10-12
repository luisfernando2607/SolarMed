<?php

use App\Models\User;
use App\Services\DosFactoresService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $codigo = '';
    public bool $usarRecuperacion = false;

    public function mount(): void
    {
        if (!$this->pendiente()) {
            $this->redirectRoute('login', navigate: true);
        }
    }

    private function pendiente(): ?array
    {
        $p = Session::get('dos_factores.login');
        return ($p && $p['expira'] >= now()->getTimestamp()) ? $p : null;
    }

    public function verificar(): void
    {
        $this->resetErrorBag();
        $pendiente = $this->pendiente();
        if (!$pendiente) {
            Session::forget('dos_factores.login');
            $this->redirectRoute('login', navigate: true);
            return;
        }

        $clave = 'dos-factores:' . $pendiente['id'] . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $this->addError('codigo', 'Demasiados intentos. Espere ' . RateLimiter::availableIn($clave) . ' segundos.');
            return;
        }

        $user = User::find($pendiente['id']);
        $servicio = app(DosFactoresService::class);
        $codigo = trim($this->codigo);

        $valido = $user && ($this->usarRecuperacion
            ? $servicio->usarCodigoRecuperacion($user, $codigo)
            // Un mismo código TOTP no se acepta dos veces (evita su reutilización si fue interceptado)
            : ($servicio->verificarCodigo($user->two_factor_secret, $codigo)
                && Cache::add('dos-factores-usado:' . $user->id . ':' . preg_replace('/\D/', '', $codigo), true, 120)));

        if (!$valido) {
            RateLimiter::hit($clave, 300);
            app(\App\Services\AuditoriaService::class)->registrar('login_fallido', 'sesion', null, null,
                $this->usarRecuperacion ? 'Código de recuperación incorrecto' : 'Código de verificación incorrecto', $user?->id);
            $this->addError('codigo', 'Código incorrecto.');
            return;
        }

        RateLimiter::clear($clave);
        Session::forget('dos_factores.login');
        Auth::login($user, $pendiente['remember']);
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Verificación en dos pasos</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $usarRecuperacion ? 'Ingrese uno de sus códigos de recuperación.' : 'Ingrese el código de 6 dígitos de su app autenticadora.' }}
        </p>
    </div>

    <form wire:submit="verificar">
        <div>
            <x-input-label for="codigo" :value="$usarRecuperacion ? 'Código de recuperación' : 'Código'" />
            <x-text-input wire:model="codigo" id="codigo" class="block mt-1 w-full font-mono text-center tracking-widest" type="text"
                :inputmode="$usarRecuperacion ? 'text' : 'numeric'" autocomplete="one-time-code" required autofocus />
            <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4">
            <button type="button" wire:click="$toggle('usarRecuperacion')" class="text-sm text-gray-600 underline hover:text-gray-900">
                {{ $usarRecuperacion ? 'Usar código de la app' : 'Perdí mi teléfono' }}
            </button>
            <x-primary-button>Verificar</x-primary-button>
        </div>
    </form>
</div>
