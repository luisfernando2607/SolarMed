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
    <div class="mb-7 text-center">
        <span class="mx-auto mb-4 flex items-center justify-center w-12 h-12 rounded-xl bg-blue-50 text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <h1 class="text-2xl font-bold text-gray-900">Verificación en dos pasos</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $usarRecuperacion ? 'Ingresa uno de tus códigos de recuperación.' : 'Ingresa el código de 6 dígitos de tu app autenticadora.' }}
        </p>
    </div>

    <form wire:submit="verificar" class="space-y-5">
        <div>
            <label for="codigo" class="form-label">{{ $usarRecuperacion ? 'Código de recuperación' : 'Código' }}</label>
            <input wire:model="codigo" id="codigo" type="text" inputmode="{{ $usarRecuperacion ? 'text' : 'numeric' }}"
                autocomplete="one-time-code" required autofocus
                class="form-input text-center font-mono text-2xl tracking-[0.4em] {{ $errors->has('codigo') ? 'border-red-300' : '' }}">
            <x-input-error :messages="$errors->get('codigo')" class="form-error" />
        </div>

        <button type="submit"
            class="w-full inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 px-4 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2">
            Verificar
        </button>

        <div class="text-center">
            <button type="button" wire:click="$toggle('usarRecuperacion')" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                {{ $usarRecuperacion ? 'Usar código de la app' : 'Perdí mi teléfono' }}
            </button>
        </div>
    </form>
</div>
