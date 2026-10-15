@php
    $campo = 'rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">WhatsApp</h1>
            <p class="text-sm text-gray-500 mt-1">Mensajes enviados desde el número de la clínica, su entrega y las respuestas de los pacientes.</p>
        </div>
        @can('configuracion.editar')
            <a href="{{ route('configuracion') }}#whatsapp" wire:navigate class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-xs shrink-0">Configurar WhatsApp</a>
        @endcan
    </div>

    @unless ($activo)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            El envío automático por WhatsApp está desactivado o falta configurarlo. Mientras tanto, los botones de WhatsApp abren el mensaje ya escrito para enviarlo desde un teléfono de la clínica.
        </div>
    @endunless

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        @foreach ([
            ['Enviados hoy', $kpis['hoy'], null],
            ['Entregados', $kpis['entregados'] !== null ? $kpis['entregados'] . ' %' : '—', 'últimos 7 días'],
            ['Leídos', $kpis['leidos'] !== null ? $kpis['leidos'] . ' %' : '—', 'últimos 7 días'],
            ['Fallidos', $kpis['fallidos'], 'últimos 7 días'],
            ['Respuestas sin revisar', $kpis['sin_revisar'], null],
        ] as [$titulo, $valor, $nota])
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ ($titulo === 'Fallidos' && $valor > 0) ? 'text-red-700' : (($titulo === 'Respuestas sin revisar' && $valor > 0) ? 'text-blue-700' : 'text-gray-900') }}">{{ $valor }}</p>
                @if ($nota)<p class="text-[11px] text-gray-400">{{ $nota }}</p>@endif
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <input wire:model.live.debounce.300ms="busqueda" type="search" placeholder="Paciente, cédula o teléfono" class="lg:col-span-2 {{ $campo }}">
        <select wire:model.live="estado" class="{{ $campo }}">
            <option value="">Todos los estados</option>
            @foreach (\App\Models\WhatsappMensaje::ESTADOS as $clave => [$texto]) <option value="{{ $clave }}">{{ $texto }}</option> @endforeach
        </select>
        <select wire:model.live="tipo" class="{{ $campo }}">
            <option value="">Todos los tipos</option>
            @foreach (\App\Models\WhatsappMensaje::TIPOS as $clave => $texto) <option value="{{ $clave }}">{{ $texto }}</option> @endforeach
        </select>
        <input wire:model.live="fecha" type="date" class="{{ $campo }}" title="Fecha de envío">
    </div>
    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" wire:model.live="soloRespuestas" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"> Solo mensajes con respuesta del paciente
    </label>

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Fecha</th>
                        <th class="text-left font-medium px-5 py-3">Paciente</th>
                        <th class="text-left font-medium px-5 py-3">Mensaje</th>
                        <th class="text-left font-medium px-5 py-3">Estado</th>
                        <th class="text-left font-medium px-5 py-3">Respuesta</th>
                        <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($mensajes as $m)
                        @php
                            [$estadoTexto, $tono] = \App\Models\WhatsappMensaje::ESTADOS[$m->estado] ?? [$m->estado, 'bg-gray-100 text-gray-600'];
                            $nueva = $m->respondido_at && !$m->respuesta_revisada_at;
                        @endphp
                        <tr wire:key="wa-{{ $m->id }}" class="{{ $nueva ? 'bg-blue-50/40' : 'hover:bg-gray-50/70' }}">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-600 tabular-nums">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if ($m->paciente)
                                    <a href="{{ route('pacientes.ver', $m->paciente) }}" wire:navigate class="text-gray-900 hover:text-blue-700">{{ $m->paciente->nombre_visible }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                                <span class="block text-xs text-gray-400 font-mono">+{{ $m->telefono }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="block text-gray-800">{{ \App\Models\WhatsappMensaje::TIPOS[$m->tipo] ?? $m->tipo }}</span>
                                <span class="block text-xs text-gray-400">{{ $m->plantilla }}{{ $m->cita ? ' · cita ' . $m->cita->fecha_hora->format('d/m H:i') : '' }}</span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tono }}">{{ $estadoTexto }}</span>
                                @if ($m->estado === 'fallido' && $m->error_mensaje)
                                    <span class="block text-xs text-red-700 mt-1 max-w-xs">{{ $m->error_mensaje }}</span>
                                @elseif ($m->leido_at)
                                    <span class="block text-[11px] text-gray-400 mt-0.5">leído {{ $m->leido_at->format('d/m H:i') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 max-w-xs">
                                @if ($m->respondido_at)
                                    <span class="block text-gray-800 {{ $nueva ? 'font-medium' : '' }}">
                                        @if (str_starts_with((string) $m->respuesta_payload, 'CONFIRMAR_CITA')) ✓ Confirmó la cita
                                        @elseif (str_starts_with((string) $m->respuesta_payload, 'CANCELAR_CITA')) ✕ Canceló la cita
                                        @else “{{ $m->respuesta_texto }}”
                                        @endif
                                    </span>
                                    <span class="block text-[11px] text-gray-400">{{ $m->respondido_at->format('d/m H:i') }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                @can('turnos.gestionar')
                                    @if ($m->estado === 'fallido' && $activo)
                                        <button wire:click="reintentar({{ $m->id }})" class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50">Reintentar</button>
                                    @endif
                                    @if ($nueva)
                                        <button wire:click="marcarRevisada({{ $m->id }})" class="px-3 py-1.5 rounded-lg bg-blue-50 text-xs font-medium text-blue-700 hover:bg-blue-100">Revisada</button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">Aún no hay mensajes de WhatsApp con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($mensajes->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $mensajes->links() }}</div>
        @endif
    </div>
</div>
