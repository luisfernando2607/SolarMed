<div>
    @php $activa = auth()->user()->tieneDosFactores(); @endphp

    <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Último paso · protege tu cuenta</p>
    <h1 class="mt-1 text-lg font-semibold text-gray-900">Verificación en dos pasos</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $marca->nombre() }} · {{ auth()->user()->name }}</p>

    @unless ($activa)
        <div class="mt-4 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
            <svg class="w-5 h-5 flex-none mt-0.5 text-blue-600" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            <p class="text-sm text-blue-900">Tu rol maneja información clínica, por eso el sistema exige una segunda clave temporal al ingresar. Toma menos de un minuto configurarla y solo se hace una vez.</p>
        </div>

        <ol class="mt-5 space-y-2 text-sm text-gray-600">
            <li class="flex items-center gap-2"><span class="flex-none w-5 h-5 rounded-full bg-gray-100 text-gray-500 text-[11px] font-semibold flex items-center justify-center">1</span> Instala una app autenticadora (Google Authenticator, Microsoft Authenticator o Authy).</li>
            <li class="flex items-center gap-2"><span class="flex-none w-5 h-5 rounded-full bg-gray-100 text-gray-500 text-[11px] font-semibold flex items-center justify-center">2</span> Escanea el código y confirma con los 6 dígitos que aparecen.</li>
        </ol>
    @endunless

    {{-- Formulario de activación (reutilizado del perfil) --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50/60 p-5">
        <livewire:profile.dos-factores-form :compacto="true" />
    </div>

    <div class="mt-6 flex items-center justify-between">
        <button type="button" wire:click="salir" class="text-sm text-gray-500 hover:text-gray-800">Cerrar sesión</button>
        @if ($activa)
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold py-2.5 px-5 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:ring-offset-2">
                Continuar al panel
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            </a>
        @else
            <span class="text-xs text-gray-400">Debes activarla para continuar</span>
        @endif
    </div>
</div>
