<div>
    <div class="max-w-7xl mx-auto py-8 px-4">
        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Facturación</h1>
                <p class="text-sm text-gray-500 mt-1">{{ number_format($facturas->total()) }} {{ $facturas->total() === 1 ? 'factura' : 'facturas' }}{{ $filtroEstado ? ' · ' . ($filtroEstado === 'pagada' ? 'pagadas' : 'anuladas') : '' }}</p>
            </div>
            <a href="{{ route('facturas.crear') }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva factura
            </a>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1 sm:max-w-md">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input wire:model.live.debounce.300ms="busqueda" type="search"
                    class="w-full pl-10 rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500"
                    placeholder="Buscar por número, paciente o cédula…">
            </div>
            <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm self-start">
                @foreach (['' => 'Todas', 'pagada' => 'Pagadas', 'anulada' => 'Anuladas'] as $valor => $texto)
                    <button wire:click="$set('filtroEstado', '{{ $valor }}')" class="px-3 py-1.5 rounded-lg transition {{ $filtroEstado === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 border-b border-gray-100">
                        <tr>
                            <th class="text-left font-medium px-5 py-3"><button wire:click="ordenarPor('numero_factura')" class="inline-flex items-center gap-1 hover:text-gray-800">Factura @if ($sortBy === 'numero_factura')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="text-left font-medium px-5 py-3">Paciente</th>
                            <th class="text-right font-medium px-5 py-3"><button wire:click="ordenarPor('total')" class="inline-flex items-center gap-1 hover:text-gray-800">Total @if ($sortBy === 'total')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell"><button wire:click="ordenarPor('forma_pago')" class="inline-flex items-center gap-1 hover:text-gray-800">Pago @if ($sortBy === 'forma_pago')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="text-left font-medium px-5 py-3"><button wire:click="ordenarPor('estado')" class="inline-flex items-center gap-1 hover:text-gray-800">Estado @if ($sortBy === 'estado')<svg class="w-3 h-3 text-gray-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7' }}"/></svg>@else<svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/></svg>@endif</button></th>
                            <th class="text-left font-medium px-5 py-3 hidden lg:table-cell">SRI</th>
                            <th class="px-5 py-3"><span class="sr-only">Abrir</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php
                            $sri = [
                                'autorizado' => ['Autorizada', 'bg-emerald-50 text-emerald-700'],
                                'enviado' => ['En proceso', 'bg-sky-50 text-sky-700'],
                                'error_envio' => ['Reintentando', 'bg-amber-50 text-amber-700'],
                                'error_autorizacion' => ['Reintentando', 'bg-amber-50 text-amber-700'],
                                'rechazado' => ['Devuelta', 'bg-red-50 text-red-700'],
                                'no_autorizado' => ['No autorizada', 'bg-red-50 text-red-700'],
                                'xml_generado' => ['Sin emitir', 'bg-gray-100 text-gray-600'],
                                'firmado' => ['Sin emitir', 'bg-gray-100 text-gray-600'],
                            ];
                        @endphp
                        @forelse ($facturas as $f)
                            @php [$sriTexto, $sriClase] = $sri[$f->estado_sri] ?? ['Sin emitir', 'bg-gray-100 text-gray-600']; @endphp
                            <tr class="hover:bg-gray-50/70 transition cursor-pointer {{ $f->estado === 'anulada' ? 'text-gray-400' : '' }}" onclick="Livewire.navigate('{{ route('facturas.ver', $f->id) }}')">
                                <td class="px-5 py-3">
                                    <span class="block font-mono text-[13px] {{ $f->estado === 'anulada' ? 'line-through' : 'text-gray-900' }}">{{ $f->numero_factura }}</span>
                                    <span class="block text-xs text-gray-400">{{ $f->fecha?->format('d/m/Y') }}</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="block {{ $f->estado === 'anulada' ? '' : 'text-gray-900' }} truncate max-w-[220px]">{{ $f->paciente?->nombre_completo }}</span>
                                    <span class="block text-xs text-gray-400 font-mono">{{ $f->paciente?->cedula }}</span>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold {{ $f->estado === 'anulada' ? '' : 'text-gray-900' }}">${{ number_format($f->total, 2) }}</td>
                                <td class="px-5 py-3 hidden md:table-cell capitalize text-gray-600">{{ $f->forma_pago }}</td>
                                <td class="px-5 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $f->estado === 'anulada' ? 'bg-gray-100 text-gray-500' : 'bg-emerald-50 text-emerald-700' }}">{{ $f->estado === 'anulada' ? 'Anulada' : 'Pagada' }}</span>
                                </td>
                                <td class="px-5 py-3 hidden lg:table-cell"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sriClase }}">{{ $sriTexto }}</span></td>
                                <td class="px-5 py-3 text-right">
                                    <a href="{{ route('facturas.ver', $f->id) }}" wire:navigate onclick="event.stopPropagation()" class="inline-flex p-2 rounded-lg text-gray-400 hover:text-blue-700 hover:bg-blue-50" aria-label="Abrir factura {{ $f->numero_factura }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-gray-700">{{ $busqueda || $filtroEstado ? 'No hay facturas con estos filtros' : 'Aún no hay facturas' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $busqueda || $filtroEstado ? 'Cambie la búsqueda o el filtro.' : 'Cree la primera desde "Nueva factura".' }}</p>
                                </td>
                            </tr>
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
