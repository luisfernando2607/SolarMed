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
            <h1 class="text-2xl font-bold text-gray-900">Facturación</h1>
            <div class="flex gap-2">
                <select wire:model.live="filtroEstado"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="pagada">Pagadas</option>
                    <option value="anulada">Anuladas</option>
                </select>
                <a href="{{ route('facturas.crear') }}" wire:navigate
                   class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                    + Nueva factura
                </a>
            </div>
        </div>

        <div class="mb-6">
            <input wire:model.live.debounce.300ms="busqueda" type="text"
                class="w-full max-w-md rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                placeholder="Buscar por número o paciente...">
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('numero_factura')" class="flex items-center gap-1 hover:text-gray-700">
                                    # Factura
                                    @if ($sortBy === 'numero_factura')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">Paciente</th>
                            <th class="text-left px-4 py-3">Cédula</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('total')" class="flex items-center gap-1 hover:text-gray-700">
                                    Total
                                    @if ($sortBy === 'total')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('forma_pago')" class="flex items-center gap-1 hover:text-gray-700">
                                    Pago
                                    @if ($sortBy === 'forma_pago')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">SRI</th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('estado')" class="flex items-center gap-1 hover:text-gray-700">
                                    Estado
                                    @if ($sortBy === 'estado')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-left px-4 py-3">
                                <button wire:click="ordenarPor('created_at')" class="flex items-center gap-1 hover:text-gray-700">
                                    Fecha
                                    @if ($sortBy === 'created_at')
                                        <svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>
                                    @else
                                        <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>
                                    @endif
                                </button>
                            </th>
                            <th class="text-center px-4 py-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($facturas as $f)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium">{{ $f->numero_factura }}</td>
                                <td class="px-4 py-3">{{ $f->paciente?->nombre_completo ?? '--' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $f->paciente?->cedula ?? '--' }}</td>
                                <td class="px-4 py-3 font-semibold text-gray-900">${{ number_format($f->total, 2) }}</td>
                                <td class="px-4 py-3 capitalize">{{ $f->forma_pago }}</td>
                                <td class="px-4 py-3">
                                    @if ($f->estado_sri === 'autorizado')
                                        <span class="inline-flex items-center gap-0.5 text-xs text-green-600 font-medium" title="Autorizado SRI">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            SRI
                                        </span>
                                    @elseif ($f->estado_sri && $f->estado_sri !== 'pendiente')
                                        <span class="inline-flex items-center gap-0.5 text-xs text-amber-600 font-medium" title="Enviado SRI">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            {{ ucfirst($f->estado_sri) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">--</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($f->estado === 'pagada')
                                        <span class="inline-flex px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">Pagada</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 bg-red-100 text-red-700 text-xs font-medium rounded-full">Anulada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $f->fecha ? $f->fecha->format('d/m/Y') : $f->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3 text-center">
                                    <a href="{{ route('facturas.ver', $f->id) }}" wire:navigate
                                       class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">No se encontraron facturas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($facturas->hasPages())
                <div class="p-4 border-t">{{ $facturas->links() }}</div>
            @endif
        </div>
    </div>
</div>
