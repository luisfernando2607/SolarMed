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
            <h1 class="text-2xl font-bold text-gray-900">Pacientes</h1>
            <span class="text-sm text-gray-500">{{ $pacientes->total() }} registros</span>
        </div>

        {{-- Búsqueda --}}
        <div class="mb-6">
            <input wire:model.live.debounce.300ms="busqueda" type="text"
                class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Buscar por nombre, cédula o teléfono...">
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">#</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('nombres')" class="flex items-center gap-1 hover:text-gray-700">
                                    Nombres
                                    @if ($sortBy === 'nombres')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('apellidos')" class="flex items-center gap-1 hover:text-gray-700">
                                    Apellidos
                                    @if ($sortBy === 'apellidos')
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
                            <th class="text-left px-4 py-3">Teléfono</th>
                            <th class="text-left px-4 py-3">Edad</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('sexo')" class="flex items-center gap-1 hover:text-gray-700">
                                    Sexo
                                    @if ($sortBy === 'sexo')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
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
                        @forelse ($pacientes as $p)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-400">{{ $p->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $p->nombres }}</td>
                                <td class="px-4 py-3">{{ $p->apellidos }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $p->cedula }}</td>
                                <td class="px-4 py-3">{{ $p->telefono ?? '--' }}</td>
                                <td class="px-4 py-3">{{ $p->edad ?? '--' }}</td>
                                <td class="px-4 py-3 capitalize">{{ $p->sexo }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $p->created_at->format('d/m/Y') }}</td>
<td class="px-4 py-3 text-center">
    <div class="flex items-center justify-center gap-1.5">
        <button wire:click="seleccionar({{ $p->id }})"
            class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition"
            title="Ver ficha">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
        </button>
        @can('pacientes.editar')
            <button wire:click="editar({{ $p->id }})"
                class="p-1.5 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition"
                title="Editar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </button>
        @endcan
        @can('pacientes.eliminar')
            <button x-data
                x-on:click="Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar paciente?',
                    text: '{{ $p->nombre_completo }}. Esta acción no se puede deshacer.',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => { if (result.isConfirmed) $wire.eliminarPaciente({{ $p->id }}) })"
                class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition"
                title="Eliminar">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        @endcan
    </div>
</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No se encontraron pacientes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($pacientes->hasPages())
                <div class="p-4 border-t">{{ $pacientes->links() }}</div>
            @endif
        </div>

        {{-- Modal detalle / edición --}}
        @if ($selected)
            <div class="fixed inset-0 bg-black/40 z-50 flex items-start justify-center p-4 pt-12 overflow-y-auto"
                 wire:click.self="cerrarDetalle">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full {{ $editMode ? 'max-w-3xl' : '' }}">
                    <div class="p-6">
                        @if ($editMode)
                            {{-- ====== FORMULARIO DE EDICIÓN ====== --}}
                            <div class="flex justify-between items-start mb-6">
                                <h2 class="text-xl font-bold text-gray-900">Editar paciente</h2>
                                <button wire:click="cancelarEdicion" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                            </div>

                            <form wire:submit="guardarPaciente" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Nombres *</label>
                                        <input wire:model="nombres" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        @error('nombres') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Apellidos *</label>
                                        <input wire:model="apellidos" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        @error('apellidos') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Cédula *</label>
                                        <input wire:model="cedula" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        @error('cedula') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de nacimiento</label>
                                        <input wire:model="fecha_nacimiento" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Sexo *</label>
                                        <select wire:model="sexo" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="">Seleccione...</option>
                                            <option value="masculino">Masculino</option>
                                            <option value="femenino">Femenino</option>
                                        </select>
                                        @error('sexo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
                                        <input wire:model="telefono" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono secundario</label>
                                        <input wire:model="telefono_secundario" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                                        <input wire:model="email" type="email" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
                                        <input wire:model="direccion" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Ciudad</label>
                                        <input wire:model="ciudad" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Ocupación</label>
                                        <input wire:model="ocupacion" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Referido por</label>
                                        <input wire:model="referido_por" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </div>

                                <hr class="border-gray-200">

                                <h3 class="text-sm font-semibold text-gray-900">Datos clínicos</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Grupo sanguíneo</label>
                                        <input wire:model="grupo_sanguineo" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="O+">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Peso (kg)</label>
                                        <input wire:model="peso" type="number" step="0.1" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Altura (m)</label>
                                        <input wire:model="altura" type="number" step="0.01" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Medicamentos actuales</label>
                                        <textarea wire:model="medicamentos" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Cirugías previas</label>
                                        <textarea wire:model="cirugias" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Alergias</label>
                                        <textarea wire:model="alergias" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Antecedentes personales</label>
                                        <textarea wire:model="antecedentes" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Antecedentes familiares</label>
                                        <textarea wire:model="enfermedades_familiares" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"></textarea>
                                    </div>
                                </div>

                                <hr class="border-gray-200">

                                <h3 class="text-sm font-semibold text-gray-900">Datos gineco-obstétricos</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">FUM</label>
                                        <input wire:model="fum" type="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Gestas</label>
                                        <input wire:model="gestas" type="number" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Partos</label>
                                        <input wire:model="partos" type="number" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Cesáreas</label>
                                        <input wire:model="cesareas" type="number" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Abortos</label>
                                        <input wire:model="abortos" type="number" min="0" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Método anticonceptivo</label>
                                        <input wire:model="metodo_anticonceptivo" type="text" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 pt-4 border-t">
                                    <button type="button" wire:click="cancelarEdicion"
                                        class="px-5 py-2.5 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition text-sm">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition shadow-sm text-sm">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        @else
                            {{-- ====== VISTA DE DETALLE ====== --}}
                            <div class="flex justify-between items-start mb-4">
                                <h2 class="text-xl font-bold text-gray-900">{{ $selected->nombre_completo }}</h2>
                                <button wire:click="cerrarDetalle" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-6">
                                <div><span class="text-gray-500">Cédula:</span> {{ $selected->cedula }}</div>
                                <div><span class="text-gray-500">Edad:</span> {{ $selected->edad ?? '--' }} años</div>
                                <div><span class="text-gray-500">Sexo:</span> <span class="capitalize">{{ $selected->sexo }}</span></div>
                                <div><span class="text-gray-500">Teléfono:</span> {{ $selected->telefono ?? '--' }}</div>
                                <div><span class="text-gray-500">Dirección:</span> {{ $selected->direccion ?? '--' }}</div>
                                <div><span class="text-gray-500">Email:</span> {{ $selected->email ?? '--' }}</div>
                                <div><span class="text-gray-500">Ocupación:</span> {{ $selected->ocupacion ?? '--' }}</div>
                                <div><span class="text-gray-500">Referido por:</span> {{ $selected->referido_por ?? '--' }}</div>
                            </div>

                            <div class="border-t pt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div><span class="text-gray-500">Peso:</span> {{ $selected->peso ? $selected->peso.' kg' : '--' }}</div>
                                <div><span class="text-gray-500">Altura:</span> {{ $selected->altura ? $selected->altura.' m' : '--' }}</div>
                                <div class="md:col-span-2"><span class="text-gray-500">Medicamentos:</span> {{ $selected->medicamentos ?? 'Ninguno' }}</div>
                                <div class="md:col-span-2"><span class="text-gray-500">Cirugías:</span> {{ $selected->cirugias ?? 'Ninguna' }}</div>
                                <div class="md:col-span-2"><span class="text-gray-500">Alergias:</span> {{ $selected->alergias ?? 'Ninguna' }}</div>
                                <div class="md:col-span-2"><span class="text-gray-500">Antecedentes personales:</span> {{ $selected->antecedentes ?? 'Ninguno' }}</div>
                                <div class="md:col-span-2"><span class="text-gray-500">Antecedentes familiares:</span> {{ $selected->enfermedades_familiares ?? 'Ninguno' }}</div>
                            </div>

                            {{-- ====== CONSULTAS (Fichas médicas) ====== --}}
                            @if (count($consultas) > 0)
                                <div class="mt-6 pt-4 border-t">
                                    <h3 class="text-md font-semibold text-gray-900 mb-3">Fichas médicas</h3>
                                    <div class="space-y-3">
                                        @foreach ($consultas as $consulta)
                                            <div class="border-l-4 border-blue-500 pl-4 py-2 flex justify-between items-start">
                                                <div class="text-sm flex-1 min-w-0">
                                                    <div class="flex justify-between">
                                                        <span class="font-medium">{{ $consulta->fecha->format('d/m/Y H:i') }}</span>
                                                        <span class="text-gray-500 text-xs">{{ $consulta->medico?->nombre_completo ?? '--' }}</span>
                                                    </div>
                                                    <p class="text-gray-600 mt-1"><strong>Motivo:</strong> {{ $consulta->motivo_consulta }}</p>
                                                    <p class="text-gray-600"><strong>Diagnóstico:</strong> {{ $consulta->diagnostico }}</p>
                                                    @if ($consulta->codigo_cie10)
                                                        <span class="inline-block mt-1 px-2 py-0.5 bg-gray-100 text-xs text-gray-600 rounded">CIE-10: {{ $consulta->codigo_cie10 }}</span>
                                                    @endif
                                                </div>
                                                <a href="{{ route('consultas.editar', $consulta->id) }}" wire:navigate
                                                   class="ml-3 shrink-0 px-3 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                                                    Editar
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="mt-6 pt-4 border-t flex flex-wrap gap-3">
                                @can('expediente.crear')
                                    <a href="{{ route('expedientes') }}" wire:navigate
                                       class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                        + Nueva consulta
                                    </a>
                                @endcan
                                @can('pacientes.editar')
                                    <button wire:click="editar({{ $selected->id }})"
                                        class="px-4 py-2 bg-amber-500 text-white text-sm font-medium rounded-lg hover:bg-amber-600 transition">
                                        Editar paciente
                                    </button>
                                @endcan
                                @can('pacientes.eliminar')
                                    <button x-data
                                        x-on:click="Swal.fire({
                                            icon: 'warning',
                                            title: '¿Eliminar paciente?',
                                            text: '{{ $selected->nombre_completo }}. Esta acción no se puede deshacer.',
                                            showCancelButton: true,
                                            confirmButtonColor: '#dc2626',
                                            cancelButtonColor: '#6b7280',
                                            confirmButtonText: 'Sí, eliminar',
                                            cancelButtonText: 'Cancelar'
                                        }).then((result) => { if (result.isConfirmed) $wire.eliminarPaciente({{ $selected->id }}) })"
                                        class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                                        Eliminar paciente
                                    </button>
                                @endcan
                                @if ($turnoActivo)
                                    <a href="{{ route('turno.atender', $turnoActivo->id) }}" wire:navigate
                                       class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                        Atender turno #{{ $turnoActivo->codigo }}
                                    </a>
                                @else
                                    <a href="{{ route('turno.form') }}" wire:navigate
                                       class="px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                                        Registrar nuevo turno
                                    </a>
                                @endif
                            </div>

                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
