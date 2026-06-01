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
            <h1 class="text-2xl font-bold text-gray-900">Expedientes Clínicos</h1>
            <span class="text-sm text-gray-500">{{ $consultas->total() }} registros</span>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-6">
            <input wire:model.live.debounce.300ms="busqueda" type="text"
                class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Buscar por paciente (nombre, cédula)...">
            <a href="{{ route('pacientes') }}" wire:navigate
               class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                + Nueva consulta
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">#</th>
                            <th class="text-left px-4 py-3">Paciente</th>
                            <th class="text-left px-4 py-3">Cédula</th>
                            <th class="text-left px-4 py-3">Médico</th>
                            <th class="text-left px-4 py-3">Tipo</th>
                            <th class="text-left px-4 py-3">Motivo</th>
                            <th class="text-left px-4 py-3">Diagnóstico</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('fecha')" class="flex items-center gap-1 hover:text-gray-700">
                                    Fecha
                                    @if ($sortBy === 'fecha')
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
                        @forelse ($consultas as $c)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 text-gray-400">{{ $c->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $c->paciente?->nombre_completo ?? '--' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $c->paciente?->cedula ?? '--' }}</td>
                                <td class="px-4 py-3">{{ $c->medico?->nombre_completo ?? '--' }}</td>
                                <td class="px-4 py-3 capitalize">{{ $c->tipo_consulta }}</td>
                                <td class="px-4 py-3 max-w-xs truncate">{{ $c->motivo_consulta }}</td>
                                <td class="px-4 py-3 max-w-xs truncate">{{ $c->diagnostico }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $c->fecha->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('consultas.editar', $c->id) }}" wire:navigate
                                           class="p-1.5 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition"
                                           title="Editar consulta">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No se encontraron expedientes clínicos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($consultas->hasPages())
                <div class="p-4 border-t">{{ $consultas->links() }}</div>
            @endif
        </div>
    </div>
</div>
