<div>
    <div class="max-w-7xl mx-auto py-8 px-4">
        @php
            $estados = [
                'esperando' => ['Esperando', 'bg-sky-50 text-sky-700', 'bg-sky-500'],
                'en_atencion' => ['En atención', 'bg-amber-50 text-amber-700', 'bg-amber-500'],
                'completado' => ['Completado', 'bg-emerald-50 text-emerald-700', 'bg-emerald-500'],
                'cancelado' => ['Cancelado', 'bg-red-50 text-red-700', 'bg-red-500'],
            ];
            $iniciales = function ($turno) {
                $nombre = trim((string) ($turno->paciente?->nombre_visible ?? $turno->nombre_temporal));
                $partes = preg_split('/\s+/', $nombre) ?: [];
                return mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1)) ?: '?';
            };
        @endphp

        {{-- Encabezado --}}
        <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Sala de espera</h1>
                <p class="text-sm text-gray-500 mt-1 first-letter:uppercase">
                    {{ now()->isoFormat('dddd D [de] MMMM [de] YYYY') }}
                    <span class="inline-flex items-center gap-1.5 ml-2 text-xs text-gray-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Se actualiza cada 10 s
                    </span>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if ($vista === 'historial')
                    <input type="date" wire:model.live="fecha" class="rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500">
                @endif
                <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm">
                    <button wire:click="$set('vista', 'activos')" class="px-3 py-1.5 rounded-lg transition {{ $vista === 'activos' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">Activos</button>
                    @can('expediente.ver')
                        <button wire:click="$set('vista', 'historial')" class="px-3 py-1.5 rounded-lg transition {{ $vista === 'historial' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">Historial</button>
                    @endcan
                </div>
                @can('turnos.ver')
                    <a href="{{ route('turnos.crear') }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-xs transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Registrar turno
                    </a>
                @endcan
            </div>
        </div>

        @if ($vista === 'activos')
            @php
                $totalEsperando = collect($colas)->sum(fn ($c) => $c['esperando']->count());
                $totalAtencion = collect($colas)->filter(fn ($c) => $c['en_atencion'])->count();
                $kpis = [
                    ['En atención', $totalAtencion, 'bg-amber-500'],
                    ['En espera', $totalEsperando, 'bg-sky-500'],
                    ['Completados hoy', \App\Models\Turno::whereDate('created_at', today())->where('estado', 'completado')->count(), 'bg-emerald-500'],
                    ['Cancelados hoy', \App\Models\Turno::whereDate('created_at', today())->where('estado', 'cancelado')->count(), 'bg-red-500'],
                ];
            @endphp

            {{-- Indicadores --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
                @foreach ($kpis as [$etiqueta, $valor, $punto])
                    <div class="bg-white rounded-2xl border border-gray-200/80 px-4 py-3.5">
                        <p class="flex items-center gap-2 text-xs text-gray-500"><span class="w-2 h-2 rounded-full {{ $punto }}"></span>{{ $etiqueta }}</p>
                        <p class="text-2xl font-semibold text-gray-900 mt-1 tabular-nums">{{ $valor }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Colas por especialidad --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @forelse ($colas as $cola)
                    @php $color = $cola['especialidad']->color_agenda ?: '#64748b'; @endphp
                    <section class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
                        <header class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $color }}"></span>
                                <h2 class="font-semibold text-gray-900 truncate">{{ $cola['especialidad']->nombre }}</h2>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 whitespace-nowrap">{{ $cola['esperando']->count() }} en espera</span>
                        </header>

                        {{-- En atención --}}
                        <div class="p-4">
                            @if ($t = $cola['en_atencion'])
                                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                                    <p class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wide text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        En atención ahora
                                    </p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <span class="flex items-center justify-center min-w-12 h-10 px-2 rounded-xl bg-white border border-amber-200 font-bold text-gray-900 tabular-nums">{{ $t->codigo }}</span>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 truncate">{{ $t->paciente?->nombre_visible ?? $t->nombre_temporal }}</p>
                                            <p class="text-xs text-gray-500 truncate">
                                                <span class="font-mono">{{ $t->cedula }}</span> · {{ $t->motivo }} · {{ $t->hora_registro->format('H:i') }}
                                                @if ($tel = $t->paciente?->telefono ?? $t->telefono) · {{ $tel }} @endif
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap gap-2 mt-3">
                                        @can('expediente.crear')
                                            <a href="{{ route('turno.atender', $t->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0"/></svg>
                                                Atender
                                            </a>
                                        @endcan
                                        @can('turnos.gestionar')
                                            <button wire:click="completar({{ $t->id }})" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                                Completar
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            @else
                                <div class="rounded-xl border border-dashed border-gray-200 py-5 text-center text-sm text-gray-400">Sin paciente en atención</div>
                            @endif
                        </div>

                        {{-- En espera --}}
                        <div class="px-4 pb-4">
                            @if ($cola['esperando']->count() > 0)
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-2">Próximos</p>
                                <ul class="space-y-1.5 max-h-88 overflow-y-auto">
                                    @foreach ($cola['esperando'] as $turno)
                                        <li wire:key="espera-{{ $turno->id }}" class="flex items-center gap-3 px-3 py-2 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/70 transition">
                                            <span class="text-sm font-semibold text-gray-900 tabular-nums min-w-11">{{ $turno->codigo }}</span>
                                            <button wire:click="verDetalle({{ $turno->id }})" class="flex-1 min-w-0 text-left group">
                                                <span class="block text-sm font-medium text-gray-900 group-hover:text-blue-700 truncate">{{ $turno->paciente?->nombre_visible ?? $turno->nombre_temporal }}</span>
                                                <span class="block text-xs text-gray-500 truncate"><span class="font-mono">{{ $turno->cedula }}</span> · {{ $turno->motivo }}</span>
                                            </button>
                                            <span class="text-xs text-gray-400 whitespace-nowrap hidden sm:inline" title="Tiempo de espera">{{ $turno->hora_registro->diffForHumans(null, true, true, 1) }}</span>
                                            @include('livewire.partials.boton-preparacion', ['turno' => $turno])
                                            @can('turnos.gestionar')
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button wire:click="llamar({{ $turno->id }})" class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">Llamar</button>
                                                    <button x-data
                                                        x-on:click="Swal.fire({
                                                            icon: 'warning',
                                                            title: '¿Cancelar turno?',
                                                            text: {{ \Illuminate\Support\Js::from(($turno->codigo . ' · ' . ($turno->paciente?->nombre_visible ?? $turno->nombre_temporal))) }},
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#dc2626',
                                                            cancelButtonColor: '#6b7280',
                                                            confirmButtonText: 'Sí, cancelar',
                                                            cancelButtonText: 'Volver'
                                                        }).then((r) => { if (r.isConfirmed) $wire.cancelar({{ $turno->id }}) })"
                                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition" title="Cancelar turno" aria-label="Cancelar turno">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            @endcan
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="py-4 text-center text-sm text-gray-400">No hay pacientes en espera</p>
                            @endif
                        </div>
                    </section>
                @empty
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200/80 px-5 py-14 text-center">
                        <p class="text-sm font-medium text-gray-700">No hay especialidades activas</p>
                        <p class="text-xs text-gray-400 mt-1">Active una especialidad para abrir su cola de turnos.</p>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Historial del día --}}
            @can('expediente.ver')
                <div class="space-y-4">
                    @foreach ($historial as $h)
                        @php $color = $h['especialidad']->color_agenda ?: '#64748b'; @endphp
                        <section class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
                            <header class="flex items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $color }}"></span>
                                    <h2 class="font-semibold text-gray-900 truncate">{{ $h['especialidad']->nombre }}</h2>
                                </div>
                                <span class="text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }} · {{ $h['turnos']->count() }} {{ $h['turnos']->count() === 1 ? 'turno' : 'turnos' }}</span>
                            </header>
                            <div class="relative overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="text-xs text-gray-500 border-b border-gray-100">
                                        <tr>
                                            <th class="text-left font-medium px-5 py-3">Turno</th>
                                            <th class="text-left font-medium px-5 py-3">Paciente</th>
                                            <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Motivo</th>
                                            <th class="text-left font-medium px-5 py-3 hidden lg:table-cell">Teléfono</th>
                                            <th class="text-left font-medium px-5 py-3">Hora</th>
                                            <th class="text-left font-medium px-5 py-3">Estado</th>
                                            <th class="px-5 py-3"><span class="sr-only">Ver</span></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($h['turnos'] as $turno)
                                            @php [$estTexto, $estClase, $estPunto] = $estados[$turno->estado] ?? [ucfirst((string) $turno->estado), 'bg-gray-100 text-gray-600', 'bg-gray-400']; @endphp
                                            <tr class="hover:bg-gray-50/70 transition">
                                                <td class="px-5 py-3 font-semibold text-gray-900 tabular-nums">{{ $turno->codigo }}</td>
                                                <td class="px-5 py-3">
                                                    <div class="flex items-center gap-3">
                                                        <span class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold shrink-0">{{ $iniciales($turno) }}</span>
                                                        <span class="min-w-0">
                                                            <span class="block font-medium text-gray-900 truncate max-w-[200px]">{{ $turno->paciente?->nombre_visible ?? $turno->nombre_temporal }}</span>
                                                            <span class="block text-xs text-gray-500 font-mono">{{ $turno->cedula }}</span>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3 hidden md:table-cell text-gray-600 max-w-[220px] truncate">{{ $turno->motivo }}</td>
                                                <td class="px-5 py-3 hidden lg:table-cell text-gray-600">{{ $turno->paciente?->telefono ?? $turno->telefono }}</td>
                                                <td class="px-5 py-3 text-gray-600 tabular-nums">{{ $turno->hora_registro->format('H:i') }}</td>
                                                <td class="px-5 py-3">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $estClase }}"><span class="w-1.5 h-1.5 rounded-full {{ $estPunto }}"></span>{{ $estTexto }}</span>
                                                </td>
                                                <td class="px-5 py-3 text-right">
                                                    <button wire:click="verDetalle({{ $turno->id }})" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-blue-700 hover:bg-blue-50 transition whitespace-nowrap">Ver ficha</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="px-5 py-8 text-center text-sm text-gray-400">Sin turnos en esta fecha</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endcan
        @endif
    </div>

    {{-- Detalle del turno --}}
    @if ($detalle)
        @php [$estTexto, $estClase, $estPunto] = $estados[$detalle->estado] ?? [ucfirst((string) $detalle->estado), 'bg-gray-100 text-gray-600', 'bg-gray-400']; @endphp
        <div class="fixed inset-0 bg-gray-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4" wire:click.self="cerrarDetalle">
            <div class="bg-white rounded-2xl shadow-2xl ring-1 ring-black/5 max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-4 px-6 pt-6 pb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Turno {{ $detalle->codigo }}</h2>
                        <p class="text-sm text-gray-500">{{ $detalle->especialidad->nombre }}</p>
                    </div>
                    <button wire:click="cerrarDetalle" class="p-1.5 -m-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" aria-label="Cerrar">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <div>
                        <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Turno</h3>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div><dt class="text-xs text-gray-500">Paciente</dt><dd class="font-medium text-gray-900">{{ $detalle->paciente?->nombre_visible ?? $detalle->nombre_temporal }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Cédula</dt><dd class="font-medium text-gray-900 font-mono">{{ $detalle->cedula }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Teléfono</dt><dd class="font-medium text-gray-900">{{ $detalle->paciente?->telefono ?? $detalle->telefono ?: '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Motivo</dt><dd class="font-medium text-gray-900">{{ $detalle->motivo }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Hora de registro</dt><dd class="font-medium text-gray-900 tabular-nums">{{ $detalle->hora_registro->format('H:i') }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Estado</dt><dd><span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium {{ $estClase }}"><span class="w-1.5 h-1.5 rounded-full {{ $estPunto }}"></span>{{ $estTexto }}</span></dd></div>
                        </dl>
                    </div>

                    @if ($detalle->paciente)
                        <div class="pt-5 border-t border-gray-100">
                            <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Datos clínicos</h3>
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                                <div><dt class="text-xs text-gray-500">Edad</dt><dd class="font-medium text-gray-900">{{ $detalle->paciente->edad !== null ? $detalle->paciente->edad . ' años' : '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Sexo</dt><dd class="font-medium text-gray-900 capitalize">{{ $detalle->paciente->sexo ?: '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Peso</dt><dd class="font-medium text-gray-900">{{ $detalle->paciente->peso ? $detalle->paciente->peso . ' kg' : '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Altura</dt><dd class="font-medium text-gray-900">{{ $detalle->paciente->altura ? $detalle->paciente->altura . ' m' : '—' }}</dd></div>
                                <div class="col-span-2"><dt class="text-xs text-gray-500">Dirección</dt><dd class="font-medium text-gray-900">{{ $detalle->paciente->direccion ?: '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Email</dt><dd class="font-medium text-gray-900 break-all">{{ $detalle->paciente->email ?: '—' }}</dd></div>
                                <div>
                                    <dt class="text-xs text-gray-500">Alergias</dt>
                                    <dd class="font-medium {{ $detalle->paciente->alergias ? 'text-red-700' : 'text-gray-900' }}">{{ $detalle->paciente->alergias ?: '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap justify-end gap-2 px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl">
                    @if ($detalle->estado === 'esperando')
                        @can('turnos.gestionar')
                            <button wire:click="cancelar({{ $detalle->id }})" class="px-4 py-2 rounded-xl border border-red-200 bg-white text-sm font-medium text-red-700 hover:bg-red-50 transition">Cancelar turno</button>
                            <button wire:click="llamar({{ $detalle->id }})" class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 shadow-xs transition">Llamar paciente</button>
                        @endcan
                    @endif
                    @if ($detalle->estado === 'en_atencion')
                        @can('turnos.gestionar')
                            <button wire:click="completar({{ $detalle->id }})" class="px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Marcar completado</button>
                        @endcan
                    @endif
                    @can('expediente.crear')
                        <a href="{{ route('turno.atender', $detalle->id) }}" wire:navigate class="px-4 py-2 rounded-xl bg-blue-600 text-sm font-medium text-white hover:bg-blue-700 shadow-xs transition">Atender (médico)</a>
                    @endcan
                </div>
            </div>
        </div>
    @endif

    {{-- Actualización automática cada 10 s --}}
    <div wire:poll.10s></div>
</div>
