<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @php
            $tonosEstado = [
                'pendiente' => 'bg-amber-50 text-amber-700',
                'entregada' => 'bg-emerald-50 text-emerald-700',
                'rechazada' => 'bg-gray-100 text-gray-600',
            ];
            $campo = 'w-full rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500';
        @endphp

        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Solicitudes de información</h1>
                <p class="text-sm text-gray-500 mt-1">Copias de historia clínica (48 h, AM 5216-A Art. 30) y derechos del titular de los datos (15 días calendario, LOPDP).</p>
            </div>
            @if ($puedeGestionar)
                <button wire:click="nueva" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Registrar solicitud
                </button>
            @endif
        </div>

        {{-- Resumen --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
            <div class="bg-white rounded-2xl border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500">Pendientes</p>
                <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $resumen['pendientes'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border p-4 {{ $resumen['vencidas'] ? 'border-red-200 bg-red-50/60' : 'border-gray-200/80' }}">
                <p class="text-xs text-gray-500">Fuera de plazo</p>
                <p class="text-2xl font-bold tabular-nums {{ $resumen['vencidas'] ? 'text-red-700' : 'text-gray-900' }}">{{ $resumen['vencidas'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border p-4 {{ $resumen['por_vencer'] ? 'border-amber-200 bg-amber-50/60' : 'border-gray-200/80' }}">
                <p class="text-xs text-gray-500">Vencen en menos de {{ \App\Models\SolicitudInformacion::HORAS_AVISO }} h</p>
                <p class="text-2xl font-bold tabular-nums {{ $resumen['por_vencer'] ? 'text-amber-700' : 'text-gray-900' }}">{{ $resumen['por_vencer'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200/80 p-4" title="Sin atención por más de {{ \App\Models\Paciente::ANIOS_ARCHIVO_ACTIVO }} años (AM 5216-A Art. 17)">
                <p class="text-xs text-gray-500">Historias en archivo pasivo</p>
                <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $resumen['hc_pasivas'] }}</p>
            </div>
        </div>

        {{-- Filtros --}}
        <div class="flex flex-col sm:flex-row gap-3 mb-4">
            <div class="relative flex-1 sm:max-w-md">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="search" wire:model.live.debounce.300ms="busqueda" placeholder="Paciente o solicitante…"
                       class="w-full pl-10 rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm self-start">
                @foreach (['pendiente' => 'Pendientes', 'entregada' => 'Entregadas', 'rechazada' => 'Rechazadas', 'todas' => 'Todas'] as $valor => $texto)
                    <button wire:click="$set('filtroEstado', '{{ $valor }}')" class="px-3 py-1.5 rounded-lg transition {{ $filtroEstado === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
                @endforeach
            </div>
        </div>

        {{-- Lista --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="text-left font-medium px-5 py-3">Solicitud</th>
                            <th class="text-left font-medium px-5 py-3">Paciente</th>
                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Solicitante</th>
                            <th class="text-left font-medium px-5 py-3">Plazo</th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($solicitudes as $s)
                            <tr wire:key="sol-{{ $s->id }}" class="{{ $s->vencida() ? 'bg-red-50/50' : ($s->porVencer() ? 'bg-amber-50/40' : '') }}">
                                <td class="px-5 py-3 align-top">
                                    <span class="block font-medium text-gray-900">{{ $s->tipoTexto() }}</span>
                                    <span class="block text-xs text-gray-500">{{ \App\Models\SolicitudInformacion::TIPOS[$s->tipo][2] ?? '' }} · {{ $s->fecha_solicitud->format('d/m/Y H:i') }}</span>
                                    @if ($s->documento_ruta)
                                        <a href="{{ route('auditoria.solicitudes.documento', $s) }}" target="_blank" class="inline-flex items-center gap-1 text-xs text-blue-700 hover:underline mt-0.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                            Respaldo
                                        </a>
                                    @endif
                                </td>
                                <td class="px-5 py-3 align-top">
                                    @if ($s->paciente)
                                        <a href="{{ route('pacientes.ver', $s->paciente) }}" wire:navigate class="block font-medium text-gray-900 hover:text-blue-700">{{ $s->paciente->nombre_visible }}</a>
                                        <span class="block text-xs text-gray-500 font-mono">{{ $s->paciente->cedula }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 align-top hidden md:table-cell">
                                    <span class="block text-gray-800">{{ $s->solicitante_nombres }}</span>
                                    <span class="block text-xs text-gray-500">{{ \App\Models\SolicitudInformacion::RELACIONES[$s->relacion] ?? $s->relacion }}{{ $s->entidad ? ' · ' . $s->entidad : '' }}</span>
                                </td>
                                <td class="px-5 py-3 align-top whitespace-nowrap">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $tonosEstado[$s->estado] ?? 'bg-gray-100 text-gray-600' }}">{{ \App\Models\SolicitudInformacion::ESTADOS[$s->estado] ?? $s->estado }}</span>
                                    @if ($s->estado === 'pendiente')
                                        <span class="block text-xs mt-1 {{ $s->vencida() ? 'text-red-700 font-medium' : ($s->porVencer() ? 'text-amber-700 font-medium' : 'text-gray-500') }}">
                                            {{ $s->vencida() ? 'Venció' : 'Vence' }} {{ $s->fecha_limite->format('d/m H:i') }}
                                        </span>
                                    @else
                                        <span class="block text-xs text-gray-500 mt-1">{{ $s->fecha_respuesta?->format('d/m/Y H:i') }}{{ $s->fecha_respuesta && $s->fecha_respuesta->gt($s->fecha_limite) ? ' · fuera de plazo' : '' }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 align-top">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($s->estado === 'pendiente' && $s->tipo === 'copia_hc' && $s->paciente)
                                            @can('expediente.ver')
                                                <a href="{{ route('pacientes.pdf', $s->paciente) }}" target="_blank" class="px-3 py-1.5 text-xs font-medium text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50 whitespace-nowrap">Generar copia</a>
                                            @endcan
                                        @endif
                                        @if ($s->estado === 'pendiente' && $puedeGestionar)
                                            <button wire:click="atender({{ $s->id }})" class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 whitespace-nowrap">Responder</button>
                                        @endif
                                    </div>
                                    @if ($s->respuesta)
                                        <p class="text-xs text-gray-500 mt-1 max-w-[220px] ml-auto text-right">{{ $s->respuesta }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-gray-700">{{ ['pendiente' => 'No hay solicitudes pendientes.', 'entregada' => 'No hay solicitudes entregadas.', 'rechazada' => 'No hay solicitudes rechazadas.'][$filtroEstado] ?? 'No hay solicitudes registradas.' }}</p>
                                    <p class="text-xs text-gray-400 mt-1">Registre aquí cada pedido de copia de historia clínica o de ejercicio de derechos sobre los datos.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $solicitudes->links() }}</div>
    </div>

    {{-- Modal: registrar --}}
    @if ($mostrarForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 overflow-y-auto bg-gray-900/40" wire:click.self="$set('mostrarForm', false)">
            <form wire:submit="guardar" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Registrar solicitud</h2>
                    <button type="button" wire:click="$set('mostrarForm', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" aria-label="Cerrar">&times;</button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Paciente</label>
                        @if ($pacienteElegido)
                            <div class="flex items-center justify-between rounded-xl border border-gray-200 px-3 py-2 text-sm">
                                <span>{{ $pacienteElegido->nombre_completo }} <span class="text-gray-500 font-mono">· {{ $pacienteElegido->cedula }}</span></span>
                                <button type="button" wire:click="quitarPaciente" class="text-xs text-blue-700 hover:underline">Cambiar</button>
                            </div>
                        @else
                            <input type="search" wire:model.live.debounce.250ms="pacienteBusqueda" placeholder="Cédula o nombre" class="{{ $campo }}">
                            @if ($coincidencias->isNotEmpty())
                                <div class="mt-1 rounded-xl border border-gray-200 divide-y divide-gray-100">
                                    @foreach ($coincidencias as $c)
                                        <button type="button" wire:click="elegirPaciente({{ $c->id }})" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-50">{{ $c->nombre_completo }} <span class="text-gray-500 font-mono">· {{ $c->cedula }}</span></button>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                        @error('paciente_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de solicitud</label>
                            <select wire:model.live="tipo" class="{{ $campo }}">
                                @foreach (\App\Models\SolicitudInformacion::TIPOS as $valor => [$texto, $horas, $norma])
                                    <option value="{{ $valor }}">{{ $texto }} ({{ $horas <= 48 ? $horas . ' h' : intdiv($horas, 24) . ' días' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fecha y hora de la solicitud</label>
                            <input type="datetime-local" wire:model="fecha_solicitud" class="{{ $campo }}">
                            @error('fecha_solicitud') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Solicitante</label>
                            <input type="text" wire:model="solicitante_nombres" placeholder="Nombres y apellidos" class="{{ $campo }}">
                            @error('solicitante_nombres') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Cédula del solicitante</label>
                            <input type="text" wire:model="solicitante_cedula" class="{{ $campo }}">
                            @error('solicitante_cedula') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Relación con el paciente</label>
                            <select wire:model.live="relacion" class="{{ $campo }}">
                                @foreach (\App\Models\SolicitudInformacion::RELACIONES as $valor => $texto)
                                    <option value="{{ $valor }}">{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Entidad {{ $tipo === 'uso_investigacion' || $relacion === 'autoridad' ? '' : '(opcional)' }}</label>
                            <input type="text" wire:model="entidad" placeholder="Institución, juzgado, universidad…" class="{{ $campo }}">
                            @error('entidad') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $tipo === 'uso_investigacion' ? 'Razón del uso de la información' : 'Detalle de la solicitud (opcional)' }}</label>
                        <textarea wire:model="motivo" rows="2" class="{{ $campo }}"></textarea>
                        @error('motivo') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        @if ($tipo === 'uso_investigacion')
                            <p class="text-xs text-gray-500 mt-1">AM 5216-A Art. 12: requiere autorización del paciente y la identidad debe protegerse en lo que se entregue.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Solicitud firmada y copia de cédula (PDF, JPG o PNG)</label>
                        <input type="file" wire:model="documento" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700">
                        @error('documento') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl">
                    <button type="button" wire:click="$set('mostrarForm', false)" class="px-4 py-2 text-sm text-gray-700 rounded-xl hover:bg-gray-100">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700" wire:loading.attr="disabled">Registrar</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Modal: responder --}}
    @if ($atendiendo)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-16 overflow-y-auto bg-gray-900/40" wire:click.self="cancelarAtencion">
            <form wire:submit="responder" class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">Responder solicitud</h2>
                    <p class="text-sm text-gray-500 mt-0.5">{{ $atendiendo->tipoTexto() }} · {{ $atendiendo->paciente?->nombre_visible }}</p>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm">
                        <button type="button" wire:click="$set('resultado', 'entregada')" class="px-3 py-1.5 rounded-lg {{ $resultado === 'entregada' ? 'bg-emerald-50 text-emerald-700 font-medium' : 'text-gray-600' }}">Entregada</button>
                        <button type="button" wire:click="$set('resultado', 'rechazada')" class="px-3 py-1.5 rounded-lg {{ $resultado === 'rechazada' ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-600' }}">Rechazada</button>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $resultado === 'rechazada' ? 'Motivo del rechazo' : 'Observaciones (opcional)' }}</label>
                        <textarea wire:model="respuesta" rows="3" class="{{ $campo }}"></textarea>
                        @error('respuesta') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @if ($atendiendo->vencida())
                        <p class="text-xs text-red-700">El plazo venció el {{ $atendiendo->fecha_limite->format('d/m/Y H:i') }}; quedará registrada como respondida fuera de plazo.</p>
                    @endif
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl">
                    <button type="button" wire:click="cancelarAtencion" class="px-4 py-2 text-sm text-gray-700 rounded-xl hover:bg-gray-100">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
