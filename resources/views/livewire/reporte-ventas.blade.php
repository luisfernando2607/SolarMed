<div class="max-w-7xl mx-auto py-8 px-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reporte de ventas</h1>
            <p class="text-sm text-gray-500">Resumen mensual por RUC emisor para la declaración (servicios de salud, IVA 0 %).</p>
        </div>
        <button wire:click="exportar" class="px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800 transition">Exportar a Excel (CSV)</button>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-6">
        <input wire:model.live="mes" type="month" class="rounded-lg border-gray-300 shadow-sm text-sm">
        <select wire:model.live="ruc" class="rounded-lg border-gray-300 shadow-sm text-sm">
            <option value="">Todos los RUC</option>
            @foreach ($rucs as $r => $nombre)
                <option value="{{ $r }}">{{ $r }} — {{ $nombre }}</option>
            @endforeach
        </select>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input wire:model.live="soloAutorizadas" type="checkbox" class="rounded border-gray-300"> Solo autorizadas por el SRI
        </label>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500">Facturado ({{ $resumen['facturas'] }} facturas)</p>
            <p class="text-2xl font-bold text-gray-900">${{ number_format($resumen['facturado'], 2) }}</p>
            @if ($resumen['descuento'] > 0)<p class="text-xs text-gray-500">Descuentos: ${{ number_format($resumen['descuento'], 2) }}</p>@endif
        </div>
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500">Notas de crédito ({{ $notas->count() }})</p>
            <p class="text-2xl font-bold text-orange-700">-${{ number_format($resumen['notas'], 2) }}</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4">
            <p class="text-xs text-green-700">Ventas netas</p>
            <p class="text-2xl font-bold text-green-800">${{ number_format($resumen['neto'], 2) }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500 mb-1">Por forma de pago (vigentes)</p>
            @forelse ($resumen['por_forma_pago'] as $forma => $monto)
                <p class="text-sm flex justify-between"><span class="capitalize">{{ $forma }}</span><span>${{ number_format($monto, 2) }}</span></p>
            @empty
                <p class="text-sm text-gray-400">—</p>
            @endforelse
        </div>
    </div>

    @if ($resumen['pendientes_sri'] > 0)
        <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-lg p-3 mb-6 text-sm">
            {{ $resumen['pendientes_sri'] }} factura(s) del período aún no están autorizadas por el SRI. Emítalas antes de declarar.
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-2">Fecha</th>
                        <th class="text-left px-4 py-2">Número</th>
                        <th class="text-left px-4 py-2">RUC emisor</th>
                        <th class="text-left px-4 py-2">Cliente</th>
                        <th class="text-right px-4 py-2">Descuento</th>
                        <th class="text-right px-4 py-2">Total</th>
                        <th class="text-left px-4 py-2">Pago</th>
                        <th class="text-left px-4 py-2">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse ($facturas as $f)
                        <tr class="{{ $f->estado === 'anulada' ? 'text-gray-400' : '' }}">
                            <td class="px-4 py-2 whitespace-nowrap">{{ $f->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 font-mono text-xs"><a href="{{ route('facturas.ver', $f->id) }}" wire:navigate class="hover:underline">{{ $f->numero_factura }}</a></td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $f->ruc_emisor }}</td>
                            <td class="px-4 py-2">{{ $f->paciente?->nombre_completo }}</td>
                            <td class="px-4 py-2 text-right">{{ $f->descuento > 0 ? '$' . number_format($f->descuento, 2) : '' }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format($f->total, 2) }}</td>
                            <td class="px-4 py-2 capitalize">{{ $f->forma_pago }}</td>
                            <td class="px-4 py-2 text-xs">{{ $f->estado === 'anulada' ? 'Anulada' : 'Vigente' }} · {{ $f->estado_sri ?? 'pendiente' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">No hay facturas en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($notas->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-4 py-3 border-b font-semibold text-gray-700">Notas de crédito</div>
            <table class="w-full text-sm">
                <tbody class="divide-y">
                    @foreach ($notas as $n)
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap">{{ $n->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $n->numero }}</td>
                            <td class="px-4 py-2 text-xs">modifica {{ $n->factura?->numero_factura }}</td>
                            <td class="px-4 py-2">{{ $n->motivo }}</td>
                            <td class="px-4 py-2 text-right font-medium text-orange-700">-${{ number_format($n->valor_modificacion, 2) }}</td>
                            <td class="px-4 py-2 text-xs">{{ $n->estado_sri }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
