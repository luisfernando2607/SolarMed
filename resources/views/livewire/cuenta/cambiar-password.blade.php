<div>
    <h1 class="text-lg font-semibold text-gray-900">Defina su contraseña personal</h1>
    <p class="mt-1 text-sm text-gray-500">Su cuenta tiene una contraseña asignada por otra persona. Por seguridad de los datos de los pacientes, cada usuario debe usar una clave propia que nadie más conozca.</p>

    <form wire:submit="guardar" class="mt-5 space-y-4">
        <div>
            <x-input-label for="password_actual" value="Contraseña actual" />
            <x-text-input wire:model="password_actual" id="password_actual" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
            <x-input-error :messages="$errors->get('password_actual')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password" value="Nueva contraseña" />
            <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
            <p class="mt-1 text-xs text-gray-500">Mínimo 10 caracteres, con letras y números.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Repita la nueva contraseña" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
        </div>
        <div class="flex items-center justify-between pt-2">
            <button type="button" wire:click="salir" class="text-sm text-gray-500 hover:text-gray-800">Cerrar sesión</button>
            <x-primary-button>Guardar y continuar</x-primary-button>
        </div>
    </form>
</div>
