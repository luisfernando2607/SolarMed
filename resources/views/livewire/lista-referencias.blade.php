@php
    $tonosEstado = [
        'enviada' => 'bg-amber-50 text-amber-700',
        'contrarreferida' => 'bg-emerald-50 text-emerald-700',
        'cerrada' => 'bg-gray-100 text-gray-600',
    ];
    $campo = 'rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500';
    $accion = 'px-2.5 py-1.5 rounded-lg text-xs font-medium border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition whitespace-nowrap';
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Referencias y contrarreferencias</h1>
            <p class="text-sm text-gray-500 mt-1">Formulario SNS-MSP/HCU-form.053/2021: pacientes referidos o derivados a otros establecimientos y los que llegan referidos a la clínica.</p>
        </div>
        @if ($pacienteFiltro)
            @can('expediente.crear')
                <a href="{{ route('referencias.crear', $pacienteFiltro) }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-xs transition shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Nueva referencia
                </a>
            @endcan
        @endif
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ([
            ['Enviadas sin respuesta', $kpis['enviadas'], null],
            ['Más de ' . \App\Models\Referencia::DIAS_SIN_RESPUESTA . ' días sin respuesta', $kpis['sin_respuesta'], 'alerta'],
            ['Recibidas por contrarreferir', $kpis['entrantes'], null],
            ['Registradas este mes', $kpis['mes'], null],
        ] as [$titulo, $valor, $tipo])
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ $tipo === 'alerta' && $valor > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($valor) }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-col lg:flex-row gap-3">
        <input wire:model.live.debounce.300ms="busqueda" type="search" placeholder="Paciente, cédula, especialidad o establecimiento…" class="flex-1 {{ $campo }}">
        <select wire:model.live="estado" class="{{ $campo }}">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\Referencia::ESTADOS as $valor => $texto)<option value="{{ $valor }}">{{ $texto }}</option>@endforeach
        </select>
        <select wire:model.live="direccion" class="{{ $campo }}">
            <option value="">Enviadas y recibidas</option>
            <option value="saliente">Enviadas por la clínica</option>
            <option value="entrante">Recibidas de otros establecimientos</option>
        </select>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700 px-1">
            <input type="checkbox" wire:model.live="sinRespuesta" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
            Solo sin respuesta &gt; {{ \App\Models\Referencia::DIAS_SIN_RESPUESTA }} días
        </label>
    </div>

    @if ($pacienteFiltro)
        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-500">Paciente:</span>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 text-blue-700">
                {{ $pacienteFiltro->nombre_visible }}
                <button wire:click="quitarPaciente" class="text-blue-400 hover:text-blue-700" aria-label="Quitar filtro de paciente">✕</button>
            </span>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Referencia</th>
                        <th class="text-left font-medium px-5 py-3">Paciente</th>
                        <th class="text-left font-medium px-5 py-3">Destino / origen</th>
                        <th class="text-left font-medium px-5 py-3">Estado</th>
                        <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($referencias as $r)
                        <tr class="{{ $r->sinRespuesta() ? 'bg-red-50/40' : 'hover:bg-gray-50/70' }}">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="block font-mono text-[13px] text-gray-900">{{ $r->numero() }}</span>
                                <span class="block text-xs text-gray-500">{{ $r->fecha->format('d/m/Y') }} · {{ \App\Models\Referencia::TIPOS[$r->tipo] }}{{ $r->direccion === 'entrante' ? ' recibida' : '' }}</span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <a href="{{ route('pacientes.ver', $r->paciente) }}" wire:navigate class="block text-gray-900 hover:text-blue-700">{{ $r->paciente->nombre_visible }}</a>
                                <span class="block text-xs text-gray-500 font-mono">{{ $r->paciente->cedula }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-gray-900">{{ $r->especialidad }}</span>
                                <span class="block text-xs text-gray-500">
                                    {{ $r->direccion === 'entrante' ? 'Desde ' . $r->establecimiento_origen : ($r->establecimiento_destino ?: '—') }}
                                    @if ($r->motivoTexto()) · Motivo {{ $r->motivo }}@endif
                                </span>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tonosEstado[$r->estado] ?? '' }}">{{ \App\Models\Referencia::ESTADOS[$r->estado] }}</span>
                                @if ($r->sinRespuesta())
                                    <span class="block text-[11px] text-red-700 mt-1">{{ (int) $r->fecha->diffInDays(now()) }} días sin respuesta</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('referencias.pdf', $r) }}" class="{{ $accion }}">PDF 053</a>
                                    @if ($r->documento_respuesta_ruta)
                                        <a href="{{ route('referencias.documento', $r) }}" class="{{ $accion }}">Respuesta</a>
                                    @endif
                                    @can('expediente.crear')
                                        @if ($r->editable())
                                            <a href="{{ route('referencias.editar', $r) }}" wire:navigate class="{{ $accion }}">Editar</a>
                                        @endif
                                        @unless ($r->tieneContrarreferencia())
                                            <a href="{{ route('referencias.contrarreferencia', $r) }}" wire:navigate class="{{ $accion }} border-blue-200! text-blue-700! hover:bg-blue-50!">{{ $r->direccion === 'entrante' ? 'Contrarreferir' : 'Registrar respuesta' }}</a>
                                        @endunless
                                        @if ($r->estado === 'contrarreferida')
                                            <button wire:click="cerrar({{ $r->id }})" wire:confirm="¿Cerrar esta referencia? Indica que ya se dio seguimiento a la contrarreferencia." class="{{ $accion }}">Cerrar</button>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-14 text-center">
                                <p class="text-sm font-medium text-gray-700">No hay referencias con estos filtros</p>
                                <p class="text-xs text-gray-400 mt-1">Se crean desde la ficha del paciente o desde una consulta que requiere derivación.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($referencias->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $referencias->links() }}</div>
        @endif
    </div>
</div>
