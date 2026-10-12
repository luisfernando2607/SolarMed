<?php

use App\Services\DosFactoresService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component
{
    public ?string $qr = null;
    #[Locked]
    public ?string $secretoPendiente = null;
    public string $codigo = '';
    public string $password = '';
    public array $codigosRecuperacion = [];

    /** Paso 1: genera el secreto (queda en sesión hasta confirmarlo) y muestra el QR */
    public function activar(): void
    {
        $servicio = app(DosFactoresService::class);
        $secreto = $servicio->generarSecreto();
        $this->secretoPendiente = $secreto;
        $this->qr = $servicio->qrSvg(Auth::user(), $secreto);
        $this->codigo = '';
    }

    /** Paso 2: confirma con un código de la app y guarda */
    public function confirmar(): void
    {
        $this->resetErrorBag();
        $secreto = $this->secretoPendiente;
        $servicio = app(DosFactoresService::class);

        if (!$secreto || !$servicio->verificarCodigo($secreto, $this->codigo)) {
            $this->addError('codigo', 'El código no es válido. Revise que la hora de su teléfono sea correcta.');
            return;
        }

        $this->codigosRecuperacion = $servicio->generarCodigosRecuperacion();
        Auth::user()->forceFill([
            'two_factor_secret' => $secreto,
            'two_factor_recovery_codes' => $this->codigosRecuperacion,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->reset('qr', 'codigo', 'secretoPendiente');
        app(\App\Services\AuditoriaService::class)->registrar('editar', 'usuario', Auth::id(), null, 'Activó verificación en dos pasos');
    }

    public function cancelar(): void
    {
        $this->reset('qr', 'codigo', 'secretoPendiente');
    }

    public function regenerarCodigos(): void
    {
        $this->resetErrorBag();
        $this->validate(['password' => 'required|current_password'], ['password.current_password' => 'Contraseña incorrecta.']);
        $this->codigosRecuperacion = app(DosFactoresService::class)->generarCodigosRecuperacion();
        Auth::user()->forceFill(['two_factor_recovery_codes' => $this->codigosRecuperacion])->save();
        $this->reset('password');
    }

    public function desactivar(): void
    {
        $this->resetErrorBag();
        if (Auth::user()->requiereDosFactores()) {
            $this->addError('password', 'Su rol exige verificación en dos pasos; no se puede desactivar.');
            return;
        }
        $this->validate(['password' => 'required|current_password'], ['password.current_password' => 'Contraseña incorrecta.']);

        Auth::user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->reset('password', 'codigosRecuperacion');
        app(\App\Services\AuditoriaService::class)->registrar('editar', 'usuario', Auth::id(), null, 'Desactivó verificación en dos pasos');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Verificación en dos pasos</h2>
        <p class="mt-1 text-sm text-gray-600">
            Además de la contraseña, al iniciar sesión se pedirá un código de 6 dígitos de una app autenticadora
            (Google Authenticator, Microsoft Authenticator o Authy) instalada en su teléfono.
        </p>
    </header>

    @if (auth()->user()->requiereDosFactores() && !auth()->user()->tieneDosFactores())
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800">
            Su rol requiere verificación en dos pasos. Actívela para continuar usando el sistema.
        </div>
    @endif

    @if ($codigosRecuperacion)
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800">Guarde estos códigos de recuperación en un lugar seguro.</p>
            <p class="text-xs text-green-700 mb-3">Cada uno sirve una sola vez si pierde el teléfono. No se volverán a mostrar.</p>
            <div class="grid grid-cols-2 gap-1 font-mono text-sm">
                @foreach ($codigosRecuperacion as $c)
                    <span>{{ $c }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if (auth()->user()->tieneDosFactores())
        <p class="mt-4 text-sm text-green-700 font-medium">✓ Activa desde el {{ auth()->user()->two_factor_confirmed_at->format('d/m/Y') }}.
            Quedan {{ count(auth()->user()->two_factor_recovery_codes ?? []) }} códigos de recuperación.</p>
        <div class="mt-4 flex flex-col sm:flex-row gap-3 sm:items-start">
            <div>
                <x-text-input wire:model="password" type="password" placeholder="Contraseña actual" class="block w-full sm:w-64" autocomplete="current-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <x-secondary-button wire:click="regenerarCodigos">Nuevos códigos de recuperación</x-secondary-button>
            @unless (auth()->user()->requiereDosFactores())
                <x-danger-button wire:click="desactivar">Desactivar</x-danger-button>
            @endunless
        </div>
    @elseif ($qr)
        <div class="mt-4">
            <p class="text-sm text-gray-700 mb-2">1. Escanee este código con la app autenticadora:</p>
            <div class="inline-block border rounded-lg p-2 bg-white">{!! $qr !!}</div>
            <p class="text-xs text-gray-500 mt-1">¿No puede escanear? Ingrese manualmente la clave: <span class="font-mono">{{ chunk_split($secretoPendiente ?? '', 4, ' ') }}</span></p>
            <p class="text-sm text-gray-700 mt-4 mb-2">2. Escriba el código de 6 dígitos que muestra la app:</p>
            <div class="flex gap-3 items-start">
                <div>
                    <x-text-input wire:model="codigo" inputmode="numeric" autocomplete="one-time-code" maxlength="6" class="w-32 font-mono text-center tracking-widest" />
                    <x-input-error :messages="$errors->get('codigo')" class="mt-1" />
                </div>
                <x-primary-button wire:click="confirmar">Confirmar</x-primary-button>
                <x-secondary-button wire:click="cancelar">Cancelar</x-secondary-button>
            </div>
        </div>
    @else
        <div class="mt-4">
            <x-primary-button wire:click="activar">Activar verificación en dos pasos</x-primary-button>
        </div>
    @endif
</section>
