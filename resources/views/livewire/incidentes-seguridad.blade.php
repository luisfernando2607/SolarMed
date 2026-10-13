<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @php
            $campo = 'w-full rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500';
            $tonosEstado = ['abierto' => 'bg-amber-50 text-amber-700', 'notificado' => 'bg-sky-50 text-sky-700', 'cerrado' => 'bg-gray-100 text-gray-600'];
        @endphp

        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Incidentes de seguridad</h1>
                <p class="text-sm text-gray-500 mt-1">Vulneraciones de datos personales. La LOPDP exige notificar a la autoridad en máximo {{ \App\Models\IncidenteSeguridad::DIAS_NOTIFICACION }} días desde que se conocen.</p>
            </div>
            @can('auditoria.gestionar')
                <button wire:click="nuevo" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Registrar incidente
                </button>
            @endcan
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-5">
            <div class="bg-white rounded-2xl border border-gray-200/80 p-4">
                <p class="text-xs text-gray-500">Abiertos</p>
                <p class="text-2xl font-bold text-gray-900 tabular-nums">{{ $resumen['abiertos'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border p-4 {{ $resumen['por_notificar'] ? 'border-amber-200 bg-amber-50/60' : 'border-gray-200/80' }}">
                <p class="text-xs text-gray-500">Por notificar a la autoridad</p>
                <p class="text-2xl font-bold tabular-nums {{ $resumen['por_notificar'] ? 'text-amber-700' : 'text-gray-900' }}">{{ $resumen['por_notificar'] }}</p>
            </div>
            <div class="bg-white rounded-2xl border p-4 {{ $resumen['vencidos'] ? 'border-red-200 bg-red-50/60' : 'border-gray-200/80' }}">
                <p class="text-xs text-gray-500">Notificación fuera de plazo</p>
                <p class="text-2xl font-bold tabular-nums {{ $resumen['vencidos'] ? 'text-red-700' : 'text-gray-900' }}">{{ $resumen['vencidos'] }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/80 text-xs text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="text-left font-medium px-5 py-3">Detectado</th>
                            <th class="text-left font-medium px-5 py-3">Incidente</th>
                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Afectados</th>
                            <th class="text-left font-medium px-5 py-3">Notificación</th>
                            <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($incidentes as $i)
                            @php [$sevTexto, $sevTono] = \App\Models\IncidenteSeguridad::SEVERIDADES[$i->severidad] ?? [$i->severidad, 'bg-gray-100 text-gray-700']; @endphp
                            <tr wire:key="inc-{{ $i->id }}" class="{{ $i->notificacionVencida() ? 'bg-red-50/50' : '' }}">
                                <td class="px-5 py-3 align-top whitespace-nowrap text-gray-700">{{ $i->fecha_deteccion->format('d/m/Y H:i') }}</td>
                                <td class="px-5 py-3 align-top">
                                    <div class="flex flex-wrap gap-1.5 mb-1">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sevTono }}">{{ $sevTexto }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tonosEstado[$i->estado] ?? '' }}">{{ \App\Models\IncidenteSeguridad::ESTADOS[$i->estado] ?? $i->estado }}</span>
                                    </div>
                                    <p class="text-gray-800 max-w-md">{{ \Illuminate\Support\Str::limit($i->descripcion, 160) }}</p>
                                </td>
                                <td class="px-5 py-3 align-top hidden md:table-cell">
                                    <span class="block text-gray-800 tabular-nums">{{ $i->pacientes_afectados }} {{ $i->pacientes_afectados === 1 ? 'paciente' : 'pacientes' }}</span>
                                    <span class="block text-xs text-gray-500 max-w-[220px]">{{ \Illuminate\Support\Str::limit($i->datos_afectados, 80) }}</span>
                                </td>
                                <td class="px-5 py-3 align-top whitespace-nowrap text-xs">
                                    @if (!$i->requiere_notificacion)
                                        <span class="text-gray-500">No requiere (sin riesgo)</span>
                                    @elseif ($i->notificado_autoridad_at)
                                        <span class="text-emerald-700">Notificado {{ $i->notificado_autoridad_at->format('d/m/Y') }}</span>
                                    @else
                                        <span class="{{ $i->notificacionVencida() ? 'text-red-700 font-medium' : 'text-amber-700' }}">
                                            {{ $i->notificacionVencida() ? 'Venció' : 'Vence' }} {{ $i->fechaLimiteNotificacion()->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 align-top text-right">
                                    @can('auditoria.gestionar')
                                        <button wire:click="editar({{ $i->id }})" class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100">Actualizar</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <p class="text-sm font-medium text-gray-700">No hay incidentes registrados.</p>
                                    <p class="text-xs text-gray-400 mt-1">Registre cualquier acceso indebido, pérdida o filtración de datos de pacientes.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">{{ $incidentes->links() }}</div>
    </div>

    @if ($mostrarForm)
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 pt-10 overflow-y-auto bg-gray-900/40" wire:click.self="$set('mostrarForm', false)">
            <form wire:submit="guardar" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-900">{{ $editId ? 'Actualizar incidente' : 'Registrar incidente' }}</h2>
                    <button type="button" wire:click="$set('mostrarForm', false)" class="text-gray-400 hover:text-gray-600 text-2xl leading-none" aria-label="Cerrar">&times;</button>
                </div>
                <div class="px-6 py-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Detectado el</label>
                            <input type="datetime-local" wire:model="fecha_deteccion" class="{{ $campo }}">
                            @error('fecha_deteccion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Severidad</label>
                            <select wire:model="severidad" class="{{ $campo }}">
                                @foreach (\App\Models\IncidenteSeguridad::SEVERIDADES as $valor => [$texto])
                                    <option value="{{ $valor }}">{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Pacientes afectados</label>
                            <input type="number" min="0" wire:model="pacientes_afectados" class="{{ $campo }}">
                            @error('pacientes_afectados') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">¿Qué ocurrió?</label>
                        <textarea wire:model="descripcion" rows="3" class="{{ $campo }}"></textarea>
                        @error('descripcion') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Datos comprometidos</label>
                        <textarea wire:model="datos_afectados" rows="2" placeholder="Ej.: nombres, cédulas y diagnósticos de pacientes atendidos en marzo" class="{{ $campo }}"></textarea>
                        @error('datos_afectados') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Medidas tomadas (opcional)</label>
                        <textarea wire:model="medidas" rows="2" class="{{ $campo }}"></textarea>
                    </div>
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-gray-200 bg-gray-50/70">
                        <input type="checkbox" wire:model.live="requiere_notificacion" class="mt-0.5 rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm">
                            <span class="block font-medium text-gray-800">Requiere notificación a la autoridad</span>
                            <span class="block text-xs text-gray-500">Desmarque solo si es improbable que el incidente afecte los derechos de los pacientes; deje constancia de la razón en las medidas.</span>
                        </span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @if ($requiere_notificacion)
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Notificado a la autoridad el</label>
                                <input type="datetime-local" wire:model="notificado_autoridad_at" class="{{ $campo }}">
                                @error('notificado_autoridad_at') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
                            <select wire:model="estado" class="{{ $campo }}">
                                @foreach (\App\Models\IncidenteSeguridad::ESTADOS as $valor => $texto)
                                    <option value="{{ $valor }}">{{ $texto }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl">
                    <button type="button" wire:click="$set('mostrarForm', false)" class="px-4 py-2 text-sm text-gray-700 rounded-xl hover:bg-gray-100">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-xl hover:bg-blue-700">Guardar</button>
                </div>
            </form>
        </div>
    @endif
</div>
