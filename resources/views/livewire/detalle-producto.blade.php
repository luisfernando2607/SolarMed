@php
    $p = $producto;
    $campo = 'w-full rounded-xl border-gray-200 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $boton = 'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
    $tonosMov = ['entrada' => 'bg-emerald-50 text-emerald-700', 'salida' => 'bg-blue-50 text-blue-700', 'ajuste' => 'bg-amber-50 text-amber-800', 'baja' => 'bg-red-50 text-red-700'];
    $titulos = ['entrada' => 'Registrar entrada', 'salida' => 'Registrar salida', 'ajuste' => 'Ajuste por conteo físico', 'baja' => 'Dar de baja', 'editar' => 'Editar producto'];
    $error = fn ($k) => $errors->first($k) ? '<p class="text-red-600 text-xs mt-1">' . e($errors->first($k)) . '</p>' : '';
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <a href="{{ route('inventario') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Inventario
    </a>

    <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-500">{{ \App\Models\Producto::TIPOS[$p->tipo] }}{{ $p->presentacion ? ' · ' . $p->presentacion : '' }}{{ $p->dci && $p->dci !== $p->nombre ? ' · ' . $p->dci : '' }}</p>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 break-words">{{ $p->descripcion() }}</h1>
                @if (!$p->activo)<span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">Inactivo</span>@endif
                @if ($p->notas)<p class="text-sm text-gray-500 mt-1">{{ $p->notas }}</p>@endif
            </div>
            <div class="flex gap-6 shrink-0">
                <div>
                    <p class="text-xs text-gray-500">Disponible</p>
                    <p class="text-2xl font-semibold tabular-nums {{ $p->stock_minimo && $disponible <= $p->stock_minimo ? 'text-amber-700' : 'text-gray-900' }}">{{ number_format($disponible) }} <span class="text-sm font-normal text-gray-500">{{ $p->unidad }}</span></p>
                    @if ($p->stock_minimo)<p class="text-[11px] text-gray-400">mínimo {{ $p->stock_minimo }}</p>@endif
                </div>
                @if ($vencido)
                    <div>
                        <p class="text-xs text-gray-500">Vencido (no se entrega)</p>
                        <p class="text-2xl font-semibold tabular-nums text-red-700">{{ number_format($vencido) }}</p>
                    </div>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2 mt-5 pt-5 border-t border-gray-100">
            @can('inventario.gestionar')
                <button wire:click="abrir('entrada')" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">+ Entrada</button>
                <button wire:click="abrir('salida')" class="{{ $boton }}" @disabled(!$disponible)>− Salida</button>
                <button wire:click="abrir('editar')" class="{{ $boton }}">Editar</button>
                <button wire:click="alternarActivo" wire:confirm="{{ $p->activo ? '¿Desactivar este producto? Dejará de aparecer en la lista.' : '¿Reactivar este producto?' }}" class="{{ $boton }}">{{ $p->activo ? 'Desactivar' : 'Reactivar' }}</button>
            @endcan
            <a href="{{ route('inventario.kardex', $p) }}" class="{{ $boton }} sm:ml-auto">Exportar kardex</a>
        </div>
    </div>

    {{-- Lotes --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <h2 class="text-sm font-semibold text-gray-900 px-5 pt-4 pb-3">Lotes</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-y border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-2">Lote</th>
                        <th class="text-left font-medium px-5 py-2">Vencimiento</th>
                        <th class="text-right font-medium px-5 py-2">Cantidad</th>
                        <th class="text-left font-medium px-5 py-2 hidden md:table-cell">Proveedor</th>
                        <th class="px-5 py-2"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($lotes as $l)
                        <tr class="{{ $l->cantidad === 0 ? 'text-gray-400' : '' }}" wire:key="lote-{{ $l->id }}">
                            <td class="px-5 py-2.5 font-mono text-[13px]">{{ $l->codigo_lote }}
                                @if ($l->registro_sanitario)<span class="block text-[11px] font-sans text-gray-400">RS {{ $l->registro_sanitario }}</span>@endif
                            </td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                {{ $l->fecha_vencimiento?->format('d/m/Y') ?? 'Sin vencimiento' }}
                                @if ($l->cantidad && $l->vencido())
                                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700">Vencido</span>
                                @elseif ($l->cantidad && $l->porVencer())
                                    <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-800">Por vencer</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-right tabular-nums font-medium">{{ number_format($l->cantidad) }}</td>
                            <td class="px-5 py-2.5 hidden md:table-cell text-gray-600">{{ $l->proveedor ?: '—' }}</td>
                            <td class="px-5 py-2.5 text-right whitespace-nowrap">
                                @can('inventario.gestionar')
                                    <button wire:click="abrir('ajuste', {{ $l->id }})" class="px-2.5 py-1 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-100">Ajustar</button>
                                    @if ($l->cantidad)
                                        <button wire:click="abrir('baja', {{ $l->id }})" class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $l->vencido() ? 'text-red-700 bg-red-50 hover:bg-red-100' : 'text-gray-700 hover:bg-gray-100' }}">Dar de baja</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">Sin lotes. Registre la primera entrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Kardex --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <h2 class="text-sm font-semibold text-gray-900 px-5 pt-4 pb-3">Kardex</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-y border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-2">Fecha</th>
                        <th class="text-left font-medium px-5 py-2">Movimiento</th>
                        <th class="text-right font-medium px-5 py-2">Cantidad</th>
                        <th class="text-left font-medium px-5 py-2">Lote</th>
                        <th class="text-right font-medium px-5 py-2 hidden sm:table-cell">Saldo lote</th>
                        <th class="text-left font-medium px-5 py-2 hidden lg:table-cell">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($movimientos as $m)
                        <tr>
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-600 tabular-nums">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tonosMov[$m->tipo] ?? '' }}">{{ \App\Models\MovimientoInventario::TIPOS[$m->tipo] }}</span>
                                <span class="text-xs text-gray-500">{{ $m->motivoTexto() }}</span>
                            </td>
                            <td class="px-5 py-2.5 text-right tabular-nums font-medium {{ $m->signo() > 0 ? 'text-emerald-700' : 'text-gray-900' }}">{{ $m->signo() > 0 ? '+' : '−' }}{{ number_format($m->cantidad) }}</td>
                            <td class="px-5 py-2.5 font-mono text-[13px]">{{ $m->lote?->codigo_lote ?? '—' }}</td>
                            <td class="px-5 py-2.5 text-right tabular-nums hidden sm:table-cell text-gray-600">{{ $m->saldo_lote_despues }}</td>
                            <td class="px-5 py-2.5 text-gray-600 hidden lg:table-cell">
                                {{ $m->user?->name ?? '—' }}
                                @if ($m->paciente) · {{ $m->paciente->nombre_visible }}@endif
                                @if ($m->observacion)<span class="block text-xs text-gray-400 truncate max-w-xs" title="{{ $m->observacion }}">{{ $m->observacion }}</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">Sin movimientos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($movimientos->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $movimientos->links() }}</div>@endif
    </div>

    {{-- Formularios de movimiento --}}
    @if ($accion)
        <div class="fixed inset-0 z-50 flex items-start justify-center bg-gray-900/40 backdrop-blur-sm p-4 pt-12 overflow-y-auto" wire:click.self="cerrar">
            <form wire:submit="guardar" class="w-full {{ $accion === 'editar' ? 'max-w-2xl' : 'max-w-lg' }} bg-white rounded-2xl shadow-2xl p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $titulos[$accion] }}</h2>
                    <p class="text-sm text-gray-500">{{ $p->descripcion() }}{{ $loteActual ? ' · lote ' . $loteActual->codigo_lote : '' }}</p>
                </div>

                @if ($accion === 'editar')
                    @include('livewire.partials.form-producto')
                @elseif ($accion === 'entrada')
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="{{ $etiqueta }}">Lote *</label><input wire:model="entrada.codigo_lote" type="text" class="{{ $campo }} font-mono">{!! $error('entrada.codigo_lote') !!}</div>
                        <div><label class="{{ $etiqueta }}">Vencimiento {{ $p->tipo === 'medicamento' ? '*' : '' }}</label><input wire:model="entrada.fecha_vencimiento" type="date" class="{{ $campo }}">{!! $error('entrada.fecha_vencimiento') !!}</div>
                        <div><label class="{{ $etiqueta }}">Cantidad ({{ $p->unidad }}) *</label><input wire:model="entrada.cantidad" type="number" min="1" class="{{ $campo }}">{!! $error('entrada.cantidad') !!}</div>
                        <div><label class="{{ $etiqueta }}">Motivo</label>
                            <select wire:model="entrada.motivo" class="{{ $campo }}">@foreach (\App\Models\MovimientoInventario::MOTIVOS['entrada'] as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select>
                        </div>
                        <div><label class="{{ $etiqueta }}">Proveedor</label><input wire:model="entrada.proveedor" type="text" class="{{ $campo }}"></div>
                        <div><label class="{{ $etiqueta }}">Costo unitario ($)</label><input wire:model="entrada.costo_unitario" type="number" step="0.0001" min="0" class="{{ $campo }}">{!! $error('entrada.costo_unitario') !!}</div>
                        <div class="col-span-2"><label class="{{ $etiqueta }}">Registro sanitario</label><input wire:model="entrada.registro_sanitario" type="text" class="{{ $campo }}"></div>
                        <div class="col-span-2"><label class="{{ $etiqueta }}">Observación</label><input wire:model="entrada.observacion" type="text" class="{{ $campo }}"></div>
                    </div>
                @elseif ($accion === 'salida')
                    <p class="text-xs text-gray-500">Se descuenta primero del lote que vence antes. Los lotes vencidos no se entregan. Disponible: <span class="font-medium text-gray-800">{{ $disponible }} {{ $p->unidad }}</span>.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="{{ $etiqueta }}">Cantidad ({{ $p->unidad }}) *</label><input wire:model="salida.cantidad" type="number" min="1" max="{{ $disponible }}" class="{{ $campo }}">{!! $error('salida.cantidad') !!}</div>
                        <div><label class="{{ $etiqueta }}">Motivo</label>
                            <select wire:model="salida.motivo" class="{{ $campo }}">@foreach (\App\Models\MovimientoInventario::MOTIVOS['salida'] as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select>
                        </div>
                        <div class="col-span-2">
                            <label class="{{ $etiqueta }}">Paciente (opcional)</label>
                            @if ($pacienteElegido)
                                <div class="flex items-center justify-between rounded-xl bg-gray-50 px-3 py-2 text-sm">
                                    <span>{{ $pacienteElegido->nombre_visible }} <span class="text-xs text-gray-500 font-mono">{{ $pacienteElegido->cedula }}</span></span>
                                    <button type="button" wire:click="$set('salida.paciente_id', null)" class="text-xs text-gray-500 hover:text-gray-800">Quitar</button>
                                </div>
                            @else
                                <input wire:model.live.debounce.300ms="buscarPaciente" type="search" placeholder="Cédula o apellido (mín. 3 letras)" class="{{ $campo }}">
                                @if ($resultadosPacientes->isNotEmpty())
                                    <ul class="mt-1 rounded-xl border border-gray-200 divide-y divide-gray-100 text-sm">
                                        @foreach ($resultadosPacientes as $r)
                                            <li><button type="button" wire:click="elegirPaciente({{ $r->id }})" class="w-full text-left px-3 py-2 hover:bg-gray-50">{{ $r->nombre_visible }} <span class="text-xs text-gray-500 font-mono">{{ $r->cedula }}</span></button></li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </div>
                        <div class="col-span-2"><label class="{{ $etiqueta }}">Observación</label><input wire:model="salida.observacion" type="text" class="{{ $campo }}"></div>
                    </div>
                @elseif ($accion === 'ajuste' && $loteActual)
                    <p class="text-xs text-gray-500">El sistema registra <span class="font-medium text-gray-800">{{ $loteActual->cantidad }} {{ $p->unidad }}</span> en este lote. Escriba lo que contó físicamente; se guarda la diferencia en el kardex.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="{{ $etiqueta }}">Cantidad contada *</label><input wire:model="ajuste.conteo" type="number" min="0" class="{{ $campo }}">{!! $error('ajuste.conteo') !!}</div>
                        <div class="col-span-2"><label class="{{ $etiqueta }}">Motivo del ajuste *</label><input wire:model="ajuste.observacion" type="text" placeholder="Ej.: conteo mensual, error de registro…" class="{{ $campo }}">{!! $error('ajuste.observacion') !!}</div>
                    </div>
                @elseif ($accion === 'baja' && $loteActual)
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="{{ $etiqueta }}">Motivo *</label>
                            <select wire:model="baja.motivo" class="{{ $campo }}">@foreach (\App\Models\MovimientoInventario::MOTIVOS['baja'] as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select>
                        </div>
                        <div><label class="{{ $etiqueta }}">Cantidad (máx. {{ $loteActual->cantidad }}) *</label><input wire:model="baja.cantidad" type="number" min="1" max="{{ $loteActual->cantidad }}" class="{{ $campo }}">{!! $error('baja.cantidad') !!}</div>
                        <div class="col-span-2"><label class="{{ $etiqueta }}">Observación</label><input wire:model="baja.observacion" type="text" placeholder="Ej.: destruido según procedimiento" class="{{ $campo }}"></div>
                    </div>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="cerrar" class="px-4 py-2 rounded-xl border border-gray-200 text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl {{ $accion === 'baja' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700' }} text-sm font-medium text-white">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
