<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();
        $pendiente = $this->form->authenticate();

        if ($pendiente) {
            // Contraseña correcta, falta el código de verificación en dos pasos
            Session::put('dos_factores.login', [
                'id' => $pendiente->id,
                'remember' => $this->form->remember,
                'expira' => now()->addMinutes(10)->getTimestamp(),
            ]);
            $this->redirectRoute('dos-factores.desafio', navigate: true);
            return;
        }

        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div x-data="{ verClave: false }">
    <div class="mb-7">
        <h1 class="text-2xl font-bold text-gray-900">Bienvenido</h1>
        <p class="text-sm text-gray-500 mt-1">Inicia sesión para continuar.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                </span>
                <input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username"
                    placeholder="correo@ejemplo.com" class="form-input pl-11 {{ $errors->has('form.email') ? 'border-red-300' : '' }}">
            </div>
            <x-input-error :messages="$errors->get('form.email')" class="form-error" />
        </div>

        <div>
            <label for="password" class="form-label">Contraseña</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                </span>
                <input wire:model="form.password" id="password" name="password" required autocomplete="current-password"
                    placeholder="••••••••" x-bind:type="verClave ? 'text' : 'password'"
                    class="form-input pl-11 pr-11 {{ $errors->has('form.password') ? 'border-red-300' : '' }}">
                <button type="button" @click="verClave = !verClave" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 hover:text-gray-600"
                    :aria-label="verClave ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                    <svg x-show="!verClave" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <svg x-show="verClave" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="form-error" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center gap-2 cursor-pointer select-none">
                <input wire:model="form.remember" id="remember" type="checkbox" name="remember"
                    class="rounded border-gray-300 text-blue-600 shadow-xs focus:ring-blue-500">
                <span class="text-sm text-gray-600">Recordarme</span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-700">¿Olvidaste tu contraseña?</a>
            @endif
        </div>

        <button type="submit"
            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-3 px-4 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2">
            <span wire:loading.remove wire:target="login">Ingresar</span>
            <span wire:loading wire:target="login">Ingresando…</span>
            <svg wire:loading.remove wire:target="login" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        </button>
    </form>
</div>
