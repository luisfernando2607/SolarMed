<div x-data x-on:notify.window="Swal.fire({
    icon: $event.detail.type,
    title: $event.detail.type === 'success' ? 'Guardado' : 'Error',
    text: $event.detail.message,
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
})">
    <div class="max-w-7xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Tarifario</h1>
            @can('configuracion.editar')
                <button wire:click="crear"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    + Nuevo servicio
                </button>
            @endcan
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">#</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('nombre')" class="flex items-center gap-1 hover:text-gray-700">
                                    Servicio
                                    @if ($sortBy === 'nombre')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('especialidad_id')" class="flex items-center gap-1 hover:text-gray-700">
                                    Especialidad
                                    @if ($sortBy === 'especialidad_id')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">Descripción</th>
                            <th class="text-right px-4 py-3">
                                <button wire:click="ordenarPor('precio')" class="flex items-center gap-1 hover:text-gray-700">
                                    Precio
                                    @if ($sortBy === 'precio')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center px-4 py-3">Activo</th>
                            <th class="text-center px-4 py-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($servicios as $s)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-400">{{ $s->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $s->nombre }}</td>
                                <td class="px-4 py-3">{{ $s->especialidad?->nombre ?? '--' }}</td>
                                <td class="px-4 py-3 text-gray-500 max-w-xs truncate">{{ $s->descripcion ?? '--' }}</td>
                                <td class="px-4 py-3 text-right font-semibold">${{ number_format($s->precio, 2) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($s->activo)
                                        <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Sí</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 bg-gray-100 text-gray-500 text-xs font-medium rounded-full">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @can('configuracion.editar')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="editar({{ $s->id }})"
                                                class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition"
                                                title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button x-data
                                                x-on:click="Swal.fire({
                                                    icon: 'warning',
                                                    title: '¿Eliminar servicio?',
                                                    text: '{{ $s->nombre }}',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#dc2626',
                                                    cancelButtonColor: '#6b7280',
                                                    confirmButtonText: 'Sí, eliminar',
                                                    cancelButtonText: 'Cancelar'
                                                }).then((r) => { if (r.isConfirmed) $wire.eliminar({{ $s->id }}) })"
                                                class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                                                title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hay servicios registrados</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($servicios->hasPages())
                <div class="p-4 border-t">{{ $servicios->links() }}</div>
            @endif
        </div>

        {{-- Modal crear / editar --}}
        @if ($editMode || $editId === 0)
            <div class="fixed inset-0 bg-black/40 z-50 flex items-start justify-center p-4 pt-12 overflow-y-auto"
                 wire:click.self="resetForm">
                <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-gray-900">{{ $editMode ? 'Editar servicio' : 'Nuevo servicio' }}</h2>
                        <button wire:click="resetForm" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>

                    <form wire:submit="guardar" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Especialidad *</label>
                            <select wire:model="especialidad_id"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Seleccione...</option>
                                @foreach ($especialidades as $e)
                                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                            @error('especialidad_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre del servicio *</label>
                            <input wire:model="nombre" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Ej: Consulta general">
                            @error('nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Descripción</label>
                            <textarea wire:model="descripcion" rows="2"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Descripción opcional"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Precio * ($)</label>
                            <input wire:model="precio" type="number" step="0.01" min="0"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="0.00">
                            @error('precio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model="activo" class="rounded border-gray-300 text-blue-600">
                                <span class="text-sm font-medium text-gray-700">Servicio activo</span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="resetForm"
                                class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-sm">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm">
                                {{ $editMode ? 'Guardar cambios' : 'Crear servicio' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
