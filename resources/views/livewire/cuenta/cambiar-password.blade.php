<div>
    <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Paso 1 de 2 · se hace una sola vez</p>
    <h1 class="mt-1 text-lg font-semibold text-gray-900">Defina su contraseña personal</h1>
    <p class="mt-1 text-sm text-gray-500">Su cuenta tiene una contraseña asignada por otra persona. Por seguridad de los datos de los pacientes, cada usuario debe usar una clave propia que nadie más conozca. Después de guardarla no se le volverá a pedir.</p>

    @if ($errors->any())
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">No se guardó: revise los campos marcados en rojo.</div>
    @endif

    <form wire:submit="guardar" class="mt-5 space-y-4">
        <div>
            <x-input-label for="password_actual" value="Contraseña actual" />
            <x-text-input wire:model="password_actual" id="password_actual" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
            <x-input-error :messages="$errors->get('password_actual')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Repita la nueva contraseña" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
        </div>
        {{-- Requisitos que se marcan mientras escribe --}}
        <ul x-data class="space-y-1 text-xs">
            @foreach ([
                ['$wire.password.length >= 10', 'Al menos 10 caracteres'],
                ['/[a-zA-ZáéíóúñÁÉÍÓÚÑ]/.test($wire.password) && /[0-9]/.test($wire.password)', 'Letras y números'],
                ['$wire.password.length > 0 && $wire.password !== $wire.password_actual', 'Distinta de la contraseña actual'],
                ['$wire.password.length > 0 && $wire.password === $wire.password_confirmation', 'Las dos contraseñas coinciden'],
            ] as [$condicion, $texto])
                <li class="flex items-center gap-2" :class="({{ $condicion }}) ? 'text-emerald-700' : 'text-gray-400'">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    {{ $texto }}
                </li>
            @endforeach
        </ul>
        <div class="flex items-center justify-between pt-2">
            <button type="button" wire:click="salir" class="text-sm text-gray-500 hover:text-gray-800">Cerrar sesión</button>
            <x-primary-button>Guardar y continuar</x-primary-button>
        </div>
    </form>
</div>
