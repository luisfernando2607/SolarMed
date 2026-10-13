@php
    $campo = 'rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $tonosTipo = ['medicamento' => 'bg-sky-50 text-sky-700', 'insumo' => 'bg-gray-100 text-gray-600', 'material' => 'bg-violet-50 text-violet-700'];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Insumos y medicamentos</h1>
            <p class="text-sm text-gray-500 mt-1">Existencias por lote, vencimientos y kardex. Las salidas usan primero el lote que vence antes.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventario.exportar') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                Exportar stock
            </a>
            @can('inventario.gestionar')
                <button wire:click="nuevoProducto" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nuevo producto
                </button>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ([
            ['Productos activos', $activos, null, ''],
            ['Con stock bajo', $alertas['stock_bajo'], 'stock_bajo', $alertas['stock_bajo'] ? 'text-amber-700' : ''],
            ['Lotes por vencer', $alertas['por_vencer'], 'por_vencer', $alertas['por_vencer'] ? 'text-amber-700' : ''],
            ['Lotes vencidos con stock', $alertas['vencidos'], 'vencidos', $alertas['vencidos'] ? 'text-red-700' : ''],
        ] as [$titulo, $valor, $f, $tono])
            <button type="button" @if ($f) wire:click="$set('filtro', '{{ $filtro === $f ? '' : $f }}')" @endif
                    class="flex flex-col items-start text-left rounded-2xl border bg-white px-4 py-3.5 transition {{ $filtro === $f && $f ? 'border-blue-300 ring-1 ring-blue-200' : 'border-gray-200' }} {{ $f ? 'hover:border-gray-300' : 'cursor-default' }}">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ $tono ?: 'text-gray-900' }}">{{ number_format($valor) }}</p>
                @if ($f === 'por_vencer')<p class="text-[11px] text-gray-400">en {{ \App\Models\Producto::DIAS_POR_VENCER }} días o menos</p>@endif
            </button>
        @endforeach
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1 sm:max-w-md">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input wire:model.live.debounce.300ms="busqueda" type="search" placeholder="Buscar por nombre o genérico…" class="w-full pl-10 {{ $campo }}">
        </div>
        <div class="inline-flex flex-wrap rounded-xl border border-gray-200 bg-white p-1 text-sm self-start">
            @foreach (['' => 'Todos'] + \App\Models\Producto::TIPOS as $valor => $texto)
                <button wire:click="$set('tipo', '{{ $valor }}')" class="px-3 py-1.5 rounded-lg transition {{ $tipo === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
            @endforeach
        </div>
        @if ($filtro)
            <button wire:click="$set('filtro', '')" class="self-start inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm">
                {{ \App\Livewire\ListaInventario::FILTROS[$filtro] ?? $filtro }}
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        @endif
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Producto</th>
                        <th class="text-right font-medium px-5 py-3">Disponible</th>
                        <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Próximo vencimiento</th>
                        <th class="text-left font-medium px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($productos as $p)
                        <tr class="hover:bg-gray-50/70 cursor-pointer transition" wire:key="prod-{{ $p->id }}"
                            onclick="Livewire.navigate('{{ route('inventario.producto', $p) }}')">
                            <td class="px-5 py-3">
                                <a href="{{ route('inventario.producto', $p) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-700">{{ $p->descripcion() }}</a>
                                <span class="block text-xs text-gray-500">
                                    <span class="px-1.5 py-0.5 rounded {{ $tonosTipo[$p->tipo] ?? '' }}">{{ \App\Models\Producto::TIPOS[$p->tipo] }}</span>
                                    {{ $p->presentacion }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap tabular-nums">
                                <span class="font-semibold text-gray-900">{{ number_format($p->disponible) }}</span> <span class="text-xs text-gray-500">{{ $p->unidad }}</span>
                                @if ($p->stock_minimo)<span class="block text-[11px] text-gray-400">mínimo {{ $p->stock_minimo }}</span>@endif
                            </td>
                            <td class="px-5 py-3 text-gray-600 hidden md:table-cell whitespace-nowrap">
                                {{ $p->proximo_vencimiento ? \Carbon\Carbon::parse($p->proximo_vencimiento)->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @if ($p->vencido)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">{{ $p->vencido }} vencidas</span>
                                    @endif
                                    @if ($p->disponible === 0)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">Sin stock</span>
                                    @elseif ($p->stock_bajo)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800">Stock bajo</span>
                                    @endif
                                    @if ($p->por_vencer)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800">Por vencer</span>
                                    @endif
                                    @if (!$p->vencido && $p->disponible > 0 && !$p->stock_bajo && !$p->por_vencer)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">Bien</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-700">{{ $busqueda || $tipo || $filtro ? 'No hay productos con estos filtros' : 'Aún no hay productos en el inventario' }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $busqueda || $tipo || $filtro ? 'Pruebe con otros filtros.' : 'Cree el primero con "Nuevo producto" y registre su entrada.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Nuevo producto --}}
    @if ($mostrarFormulario)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-gray-900/40 backdrop-blur-sm p-4 pt-12 overflow-y-auto" wire:click.self="$set('mostrarFormulario', false)">
            <form wire:submit="guardarProducto" class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900">Nuevo producto</h2>
                @include('livewire.partials.form-producto')
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('mostrarFormulario', false)" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700">Crear producto</button>
                </div>
            </form>
        </div>
    @endif
</div>
