<div x-data x-on:notify.window="Swal.fire({
    icon: $event.detail.type, title: $event.detail.type === 'success' ? 'Listo' : 'Error',
    text: $event.detail.message, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end'
})">
<div class="max-w-6xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Especialidades</h1>
            <p class="text-sm text-gray-500">Prefijo del número de turno, color en la agenda y motivos que eligen los pacientes al registrarse.</p>
        </div>
        <button wire:click="crear" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">+ Nueva especialidad</button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($especialidades as $e)
            <div class="bg-white rounded-xl shadow-sm border p-5 {{ $e->activo ? '' : 'opacity-60' }}">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-white font-bold" style="background-color: {{ $e->color_agenda ?: '#3B82F6' }}">{{ $e->prefijo_turno }}</span>
                        <div>
                            <h2 class="font-semibold text-gray-900">{{ $e->nombre }}</h2>
                            <p class="text-xs text-gray-500">Código {{ $e->codigo }} · {{ $e->medicos_count }} médico(s) · {{ $e->activo ? 'Activa' : 'Inactiva' }}</p>
                        </div>
                    </div>
                    <button wire:click="editar({{ $e->id }})" class="text-sm text-blue-600 hover:text-blue-800">Editar</button>
                </div>
                <div class="flex flex-wrap gap-1.5 mt-4">
                    @foreach ($e->motivos ?? [] as $m)
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700">{{ $m }}</span>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($modal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:keydown.escape.window="cerrar">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ $editId ? 'Editar especialidad' : 'Nueva especialidad' }}</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                        <input wire:model.live.debounce.400ms="nombre" type="text" class="w-full rounded-lg border-gray-300 shadow-sm text-sm">
                        @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Código interno *</label>
                        <input wire:model="codigo" type="text" @disabled($editId) class="w-full rounded-lg border-gray-300 shadow-sm text-sm disabled:bg-gray-100">
                        @error('codigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Prefijo del turno *</label>
                        <input wire:model="prefijo_turno" type="text" maxlength="3" placeholder="G" class="w-full rounded-lg border-gray-300 shadow-sm text-sm uppercase">
                        @error('prefijo_turno') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Color en la agenda *</label>
                        <input wire:model="color_agenda" type="color" class="w-full h-10 rounded-lg border-gray-300">
                        @error('color_agenda') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input wire:model="activo" type="checkbox" class="rounded border-gray-300"> Activa
                        </label>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Motivos de consulta * <span class="font-normal text-gray-400">(uno por línea)</span></label>
                        <textarea wire:model="motivosTexto" rows="8" class="w-full rounded-lg border-gray-300 shadow-sm text-sm"></textarea>
                        @error('motivosTexto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3">Una especialidad inactiva deja de aparecer en el registro de turnos; sus datos históricos se conservan.</p>
                <div class="flex justify-end gap-3 mt-5">
                    <button wire:click="cerrar" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button wire:click="guardar" class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">Guardar</button>
                </div>
            </div>
        </div>
    @endif
</div>
</div>
