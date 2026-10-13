<div>
    <div class="max-w-7xl mx-auto py-8 px-4">
        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Expedientes clínicos</h1>
                <p class="text-sm text-gray-500 mt-1">{{ number_format($consultas->total()) }} {{ $consultas->total() === 1 ? 'consulta registrada' : 'consultas registradas' }}</p>
            </div>
            <a href="{{ route('pacientes') }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva consulta
            </a>
        </div>

        <div class="mb-4">
            <div class="relative sm:max-w-md">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="busqueda" type="search"
                    class="w-full pl-10 rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Buscar por paciente (nombre o cédula)…">
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="text-left font-medium px-5 py-3">Paciente</th>
                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Médico</th>
                            <th class="text-left font-medium px-5 py-3">Motivo y diagnóstico</th>
                            <th class="text-left font-medium px-5 py-3">
                                <button wire:click="ordenarPor('fecha')" class="inline-flex items-center gap-1 hover:text-gray-800">
                                    Fecha
                                    @if ($sortBy === 'fecha')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($consultas as $c)
                            @php
                                $ini = mb_strtoupper(mb_substr((string) $c->paciente?->nombres, 0, 1) . mb_substr((string) $c->paciente?->apellidos, 0, 1));
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex items-center justify-center w-9 h-9 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold shrink-0">{{ $ini ?: '—' }}</span>
                                        <span class="min-w-0">
                                            <span class="block font-medium text-gray-900 truncate max-w-[200px]">{{ $c->paciente?->nombre_completo ?? '—' }}</span>
                                            <span class="block text-xs text-gray-500 font-mono">{{ $c->paciente?->cedula ?? '—' }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 hidden md:table-cell">
                                    <span class="block text-gray-700">{{ $c->medico?->nombre_completo ?? '—' }}</span>
                                    @if ($c->tipo_consulta)
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 capitalize">{{ $c->tipo_consulta }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 max-w-xs">
                                    <span class="block text-gray-900 truncate">{{ $c->motivo_consulta ?: '—' }}</span>
                                    <span class="block text-xs text-gray-500 truncate">{{ $c->diagnostico ?: 'Sin diagnóstico' }}</span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="block text-gray-900">{{ $c->fecha->format('d/m/Y') }}</span>
                                    <span class="block text-xs text-gray-400">{{ $c->fecha->format('H:i') }}</span>
                                </td>
                                <td class="px-5 py-3 text-right">
                                    @can('update', $c)
                                        <a href="{{ route('consultas.editar', $c->id) }}" wire:navigate
                                           class="inline-flex p-2 rounded-lg text-gray-400 hover:text-blue-700 hover:bg-blue-50 transition"
                                           title="Editar consulta" aria-label="Editar consulta">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-gray-700">{{ $busqueda ? 'No hay expedientes para esta búsqueda' : 'Aún no hay consultas registradas' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $busqueda ? 'Pruebe con otro nombre o cédula.' : 'Las consultas aparecen aquí al atender a un paciente.' }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($consultas->hasPages())
                <div class="p-4 border-t border-gray-100">{{ $consultas->links() }}</div>
            @endif
        </div>
    </div>
</div>
