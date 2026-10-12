<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-bold text-gray-900">Auditoría de accesos</h1>
        <span class="text-sm text-gray-500">{{ $registros->total() }} registros</span>
    </div>
    <p class="text-sm text-gray-500 mb-6">Quién vio, descargó o modificó datos clínicos y documentos. Los registros no se pueden editar ni borrar.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 mb-6">
        <input wire:model.live.debounce.300ms="paciente" type="text"
            class="lg:col-span-2 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder="Paciente (nombre o cédula)">
        <select wire:model.live="usuario" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <option value="">Todos los usuarios</option>
            @foreach ($usuarios as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="accion" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <option value="">Todas las acciones</option>
            @foreach (\App\Models\RegistroAcceso::ACCIONES as $clave => $etiqueta)
                <option value="{{ $clave }}">{{ $etiqueta }}</option>
            @endforeach
        </select>
        <input wire:model.live="desde" type="date" title="Desde"
            class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
        <div class="flex gap-2">
            <input wire:model.live="hasta" type="date" title="Hasta"
                class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            <button wire:click="limpiar" class="px-3 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Usuario</th>
                        <th class="text-left px-4 py-3">Acción</th>
                        <th class="text-left px-4 py-3">Recurso</th>
                        <th class="text-left px-4 py-3">Paciente</th>
                        <th class="text-left px-4 py-3">Detalle</th>
                        <th class="text-left px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($registros as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 whitespace-nowrap text-gray-600">{{ $r->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-2">{{ $r->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    @switch($r->accion)
                                        @case('eliminar') bg-red-100 text-red-700 @break
                                        @case('editar') bg-amber-100 text-amber-700 @break
                                        @case('crear') bg-green-100 text-green-700 @break
                                        @case('descargar') bg-purple-100 text-purple-700 @break
                                        @default bg-blue-100 text-blue-700
                                    @endswitch">{{ \App\Models\RegistroAcceso::ACCIONES[$r->accion] ?? $r->accion }}</span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">{{ str_replace('_', ' ', $r->recurso) }} @if ($r->recurso_id)<span class="text-gray-400">#{{ $r->recurso_id }}</span>@endif</td>
                            <td class="px-4 py-2">
                                @if ($r->paciente)
                                    {{ $r->paciente->nombre_completo }} <span class="text-gray-400 text-xs">{{ $r->paciente->cedula }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-gray-600 max-w-xs truncate" title="{{ $r->detalle }}">{{ $r->detalle }}</td>
                            <td class="px-4 py-2 text-gray-400 font-mono text-xs">{{ $r->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No hay registros con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t">{{ $registros->links() }}</div>
    </div>
</div>
