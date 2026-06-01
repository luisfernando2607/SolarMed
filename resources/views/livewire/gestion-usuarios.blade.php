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
            <h1 class="text-2xl font-bold text-gray-900">Usuarios</h1>
            <span class="text-sm text-gray-500">{{ $usuarios->total() }} registros</span>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input wire:model.live.debounce.300ms="busqueda" type="text"
                class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Buscar por nombre o email...">
            @can('usuarios.gestionar')
                <button wire:click="crear"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    + Nuevo usuario
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
                                <button wire:click="ordenarPor('name')" class="flex items-center gap-1 hover:text-gray-700">
                                    Nombre
                                    @if ($sortBy === 'name')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('email')" class="flex items-center gap-1 hover:text-gray-700">
                                    Email
                                    @if ($sortBy === 'email')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('cedula')" class="flex items-center gap-1 hover:text-gray-700">
                                    Cédula
                                    @if ($sortBy === 'cedula')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">Rol</th>
                            <th class="text-left px-4 py-3">Certificado</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('created_at')" class="flex items-center gap-1 hover:text-gray-700">
                                    Registro
                                    @if ($sortBy === 'created_at')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center px-4 py-3">
                                <svg class="w-4 h-4 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Acciones">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                </svg>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($usuarios as $u)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-400">{{ $u->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $u->name }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $u->email }}</td>
                                <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $u->cedula ?? '--' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full
                                        @switch($u->roles->first()?->name)
                                            @case('admin') bg-purple-100 text-purple-700 @break
                                            @case('medico') bg-blue-100 text-blue-700 @break
                                            @case('secretaria') bg-amber-100 text-amber-700 @break
                                            @case('enfermeria') bg-green-100 text-green-700 @break
                                            @default bg-gray-100 text-gray-600
                                        @endswitch">
                                        {{ $u->roles->first()?->name ?? 'Sin rol' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($u->medico && $u->medico->p12_path)
                                        <span class="inline-flex items-center gap-0.5 text-xs text-green-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            .p12
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $u->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    @can('usuarios.gestionar')
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="editar({{ $u->id }})"
                                                class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition"
                                                title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            @if ($u->id !== auth()->id())
                                                <button x-data
                                                    x-on:click="Swal.fire({
                                                        icon: 'warning',
                                                        title: '¿Eliminar usuario?',
                                                        text: '{{ $u->name }}. Esta acción no se puede deshacer.',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#dc2626',
                                                        cancelButtonColor: '#6b7280',
                                                        confirmButtonText: 'Sí, eliminar',
                                                        cancelButtonText: 'Cancelar'
                                                    }).then((result) => { if (result.isConfirmed) $wire.eliminar({{ $u->id }}) })"
                                                    class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                                                    title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No se encontraron usuarios</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($usuarios->hasPages())
                <div class="p-4 border-t">{{ $usuarios->links() }}</div>
            @endif
        </div>

        {{-- Modal crear / editar --}}
        @if ($editMode || $editId === 0)
            <div class="fixed inset-0 bg-black/40 z-50 flex items-start justify-center p-4 pt-12 overflow-y-auto"
                 wire:click.self="resetForm">
                <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full p-6">
                    <div class="flex justify-between items-start mb-6">
                        <h2 class="text-xl font-bold text-gray-900">{{ $editMode ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                        <button wire:click="resetForm" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>

                    <form wire:submit="guardar" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
                            <input wire:model="name" type="text"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Nombre completo">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Email *</label>
                            <input wire:model="email" type="email"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="correo@ejemplo.com">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cédula / RUC</label>
                            <input wire:model="cedula" type="text" maxlength="13"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Cédula o RUC">
                            @error('cedula') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Contraseña {{ $editMode ? '(dejar vacío para mantener)' : '*' }}</label>
                            <input wire:model="password" type="password"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="{{ $editMode ? 'Nueva contraseña' : 'Contraseña' }}">
                            @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Confirmar contraseña {{ $editMode ? '(dejar vacío para mantener)' : '*' }}</label>
                            <input wire:model="password_confirmation" type="password"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="Repetir contraseña">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Rol *</label>
                            <select wire:model.live="rol"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="">Seleccione un rol...</option>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->name }}">{{ ucfirst($r->name) }}</option>
                                @endforeach
                            </select>
                            @error('rol') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        @if ($editMode && $rol === 'medico')
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Certificado digital .p12</label>
                                <input wire:model="p12_file" type="file" accept=".p12,.pfx"
                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4
                                        file:rounded-lg file:border-0 file:text-sm file:font-medium
                                        file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                @error('p12_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @if ($p12_file)
                                    <p class="text-xs text-gray-400 mt-1">{{ $p12_file->getClientOriginalName() }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="button" wire:click="resetForm"
                                class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-sm">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm">
                                {{ $editMode ? 'Guardar cambios' : 'Crear usuario' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
