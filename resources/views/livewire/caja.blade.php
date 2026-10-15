@php
    $formas = \App\Models\CierreCaja::FORMAS_PAGO;
    $campo = 'w-full rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500 tabular-nums';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $dinero = fn ($v) => '$' . number_format((float) $v, 2);
    $esperado = round((float) ($fondo_inicial ?: 0) + $periodo['efectivo_neto'], 2);
    $hayConteo = $contado_efectivo !== '' && is_numeric($contado_efectivo);
    $diferencia = $hayConteo ? round((float) $contado_efectivo - $esperado, 2) : null;@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cierre de caja</h1>
            <p class="text-sm text-gray-500 mt-1">
                Caja abierta desde {{ $periodo['desde']->format('d/m/Y H:i') }}{{ $ultimo ? ' (último cierre: ' . $ultimo->hasta->format('d/m/Y H:i') . ')' : '' }}.
            </p>
        </div>
        @can('facturas.ver')
            <a href="{{ route('caja.exportar') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-xs shrink-0">Exportar historial CSV</a>
        @endcan
    </div>

    {{-- Cobrado en el período --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ($formas as $clave => $texto)
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
                <p class="text-xs text-gray-500">{{ $texto }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $dinero($periodo['cobrado'][$clave] ?? 0) }}</p>
                @if (($periodo['devoluciones'][$clave] ?? 0) > 0)
                    <p class="text-[11px] text-red-700">− {{ $dinero($periodo['devoluciones'][$clave]) }} devuelto</p>
                @endif
            </div>
        @endforeach
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
            <p class="text-xs text-gray-500">Facturas del período</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $periodo['pagadas'] }}</p>
            <p class="text-[11px] text-gray-400">{{ $periodo['anuladas'] }} {{ $periodo['anuladas'] === 1 ? 'anulada' : 'anuladas' }} (no suman)</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
        {{-- Facturas incluidas --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="px-5 pt-4 pb-3">
                <h2 class="text-sm font-semibold text-gray-900">Movimientos que entran en este cierre</h2>
                <p class="text-xs text-gray-500">Facturas aún no cerradas. Las notas de crédito de facturas de cierres anteriores se restan como devolución.</p>
            </div>
            <div class="relative overflow-auto max-h-[32rem]">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white text-xs text-gray-500 border-y border-gray-100">
                        <tr>
                            <th class="text-left font-medium px-5 py-2">Hora</th>
                            <th class="text-left font-medium px-3 py-2">Factura</th>
                            <th class="text-left font-medium px-3 py-2">Paciente</th>
                            <th class="text-left font-medium px-3 py-2">Forma</th>
                            <th class="text-right font-medium px-5 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 tabular-nums">
                        @forelse ($periodo['facturas'] as $f)
                            <tr class="{{ $f->estado === 'anulada' ? 'text-gray-400' : 'text-gray-700' }}">
                                <td class="px-5 py-2 whitespace-nowrap">{{ $f->created_at?->format('d/m H:i') }}</td>
                                <td class="px-3 py-2 font-mono text-[13px] whitespace-nowrap {{ $f->estado === 'anulada' ? 'line-through' : 'text-gray-900' }}">{{ $f->numero_factura }}</td>
                                <td class="px-3 py-2 truncate max-w-[180px]">{{ $f->paciente?->nombre_completo }}</td>
                                <td class="px-3 py-2">{{ $formas[$f->forma_pago] ?? $f->forma_pago }}</td>
                                <td class="px-5 py-2 text-right {{ $f->estado === 'anulada' ? '' : 'font-medium text-gray-900' }}">{{ $dinero($f->total) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No hay facturas pendientes de cierre.</td></tr>
                        @endforelse
                        @foreach ($periodo['notas'] as $n)
                            <tr class="text-red-700">
                                <td class="px-5 py-2 whitespace-nowrap">{{ $n->created_at?->format('d/m H:i') }}</td>
                                <td class="px-3 py-2 font-mono text-[13px]">NC {{ $n->numero }}</td>
                                <td class="px-3 py-2">Devolución de {{ $n->factura?->numero_factura }}</td>
                                <td class="px-3 py-2">{{ $formas[$n->factura?->forma_pago] ?? '—' }}</td>
                                <td class="px-5 py-2 text-right font-medium">− {{ $dinero($n->valor_modificacion) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cuadre --}}
        <div class="lg:col-span-2 lg:sticky lg:top-6 bg-white rounded-2xl border border-gray-200/80 p-5">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Cuadre del efectivo</h2>
            @can('caja.cerrar')
                <form wire:submit="cerrar" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="fondo_inicial" class="{{ $etiqueta }}">Fondo inicial</label>
                            <input id="fondo_inicial" wire:model.live.debounce.400ms="fondo_inicial" type="number" step="0.01" min="0" class="{{ $campo }}">
                            @error('fondo_inicial') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <span class="{{ $etiqueta }}">Efectivo esperado</span>
                            <p class="h-[38px] flex items-center px-3 rounded-xl bg-gray-50 text-sm font-semibold text-gray-900 tabular-nums" title="Fondo + efectivo cobrado − devoluciones en efectivo">{{ $dinero($esperado) }}</p>
                        </div>
                    </div>

                    <details class="rounded-xl border border-gray-200 px-3 py-2">
                        <summary class="cursor-pointer text-sm font-medium text-gray-700">Contar billetes y monedas</summary>
                        <div class="grid grid-cols-3 gap-2 mt-3">
                            @foreach (\App\Models\CierreCaja::DENOMINACIONES as $d => [$textoDenominacion])
                                <label class="block">
                                    <span class="block text-[11px] text-gray-500">{{ $textoDenominacion }}</span>
                                    <input wire:model.live.debounce.400ms="billetes.{{ $d }}" type="number" min="0" step="1" inputmode="numeric" class="{{ $campo }} py-1.5">
                                </label>
                            @endforeach
                        </div>
                    </details>

                    <div>
                        <label for="contado_efectivo" class="{{ $etiqueta }}">Efectivo contado en caja</label>
                        <input id="contado_efectivo" wire:model.live.debounce.400ms="contado_efectivo" type="number" step="0.01" min="0" class="{{ $campo }}">
                        @error('contado_efectivo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    @if ($diferencia !== null)
                        <div class="rounded-xl px-4 py-3 text-sm {{ $diferencia == 0 ? 'bg-emerald-50 text-emerald-800' : ($diferencia > 0 ? 'bg-amber-50 text-amber-800' : 'bg-red-50 text-red-800') }}">
                            @if ($diferencia == 0)
                                La caja cuadra.
                            @else
                                {{ $diferencia > 0 ? 'Sobrante' : 'Faltante' }} de <strong class="tabular-nums">{{ $dinero(abs($diferencia)) }}</strong>. Explíquelo en las observaciones.
                            @endif
                        </div>
                    @endif

                    <div>
                        <label for="observaciones" class="{{ $etiqueta }}">Observaciones</label>
                        <textarea id="observaciones" wire:model="observaciones" rows="2" class="{{ $campo }}"></textarea>
                    </div>

                    <button type="button"
                            x-data x-on:click="Swal.fire({ icon: 'question', title: '¿Cerrar la caja?', text: 'Las facturas del período quedarán cerradas y el cierre no se podrá modificar.', showCancelButton: true, confirmButtonText: 'Sí, cerrar', cancelButtonText: 'Volver', confirmButtonColor: {{ \Illuminate\Support\Js::from($marca->color()) }} }).then(r => { if (r.isConfirmed) $wire.cerrar() })"
                            class="w-full py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-xs">Cerrar caja</button>
                </form>
            @else
                <p class="text-sm text-gray-500">Efectivo esperado: <strong>{{ $dinero($esperado) }}</strong>. Solo recepción o administración puede cerrar la caja.</p>
            @endcan
        </div>
    </div>

    {{-- Historial --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <h2 class="text-sm font-semibold text-gray-900 px-5 pt-4 pb-3">Cierres anteriores</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-y border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-2">Cierre</th>
                        <th class="text-left font-medium px-3 py-2">Cerró</th>
                        <th class="text-right font-medium px-3 py-2">Cobrado</th>
                        <th class="text-right font-medium px-3 py-2">Esperado efectivo</th>
                        <th class="text-right font-medium px-3 py-2">Contado</th>
                        <th class="text-right font-medium px-3 py-2">Diferencia</th>
                        <th class="px-5 py-2"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 tabular-nums">
                    @forelse ($cierres as $c)
                        <tr class="{{ $c->estado === 'reabierto' ? 'text-gray-400' : 'text-gray-700' }}">
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                <span class="text-gray-900">{{ $c->hasta->format('d/m/Y H:i') }}</span>
                                @if ($c->estado === 'reabierto')
                                    <span class="ml-1 px-1.5 py-0.5 rounded text-[11px] bg-gray-100 text-gray-600" title="{{ $c->motivo_reapertura }}">Reabierto por {{ $c->reabiertoPor?->name }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5">{{ $c->user?->name ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right">{{ $dinero($c->totalCobrado()) }}</td>
                            <td class="px-3 py-2.5 text-right">{{ $dinero($c->efectivo_esperado) }}</td>
                            <td class="px-3 py-2.5 text-right">{{ $dinero($c->contado_efectivo) }}</td>
                            <td class="px-3 py-2.5 text-right">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $c->diferencia == 0 ? 'bg-emerald-50 text-emerald-700' : ($c->diferencia > 0 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700') }}">
                                    {{ $c->diferencia == 0 ? 'Cuadra' : ($c->diferencia > 0 ? '+' : '−') . $dinero(abs($c->diferencia)) }}
                                </span>
                            </td>
                            <td class="px-5 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('caja.acta', $c) }}" class="text-xs font-medium text-blue-700 hover:underline">Acta PDF</a>
                                @if ($c->estado === 'cerrado')
                                    @can('configuracion.editar')
                                        <button wire:click="pedirReapertura({{ $c->id }})" class="ml-3 text-xs font-medium text-gray-500 hover:text-gray-800">Reabrir</button>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Todavía no hay cierres de caja.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cierres->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $cierres->links() }}</div>
        @endif
    </div>

    @if ($reabrirId)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/40" wire:click.self="$set('reabrirId', null)">
            <form wire:submit="reabrir" class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl space-y-3">
                <h3 class="text-base font-semibold text-gray-900">Reabrir cierre de caja</h3>
                <p class="text-sm text-gray-600">El cierre quedará como constancia y sus facturas volverán a la caja abierta para cerrarlas de nuevo. Indique el motivo; queda en la auditoría.</p>
                <textarea wire:model="motivoReapertura" rows="3" class="{{ $campo }}" placeholder="Ej.: se registró una factura con la forma de pago equivocada"></textarea>
                @error('motivoReapertura') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('reabrirId', null)" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-700">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium">Reabrir</button>
                </div>
            </form>
        </div>
    @endif
</div>
