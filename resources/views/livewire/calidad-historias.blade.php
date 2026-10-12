<div>
<style>
    .calidad .tarjeta { background: #fff; border: 1px solid #e9e7e2; border-radius: 16px; }
</style>
@php
    $tono = fn (int $p) => \App\Services\CalidadHistoriaService::tono($p);
    $campo = 'rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<div class="calidad max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Calidad de la historia clínica</h1>
            <p class="text-sm text-gray-500 mt-1">Llenado de la consulta externa según el formulario SNS-MSP/HCU-form.002/2021 (AM 00115-2021)</p>
        </div>
        <a href="{{ route('auditoria.planes') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition self-start">
            Planes de acción
            @if ($planesVencidos)
                <span class="px-1.5 py-0.5 rounded-full bg-red-50 text-red-700 text-xs">{{ $planesVencidos }} vencidos</span>
            @endif
        </a>
    </div>

    {{-- Filtros --}}
    <div class="flex flex-wrap items-end gap-3">
        <label class="text-xs text-gray-500">Desde<input type="date" wire:model.live="desde" class="block mt-1 {{ $campo }}"></label>
        <label class="text-xs text-gray-500">Hasta<input type="date" wire:model.live="hasta" class="block mt-1 {{ $campo }}"></label>
        <label class="text-xs text-gray-500">Médico
            <select wire:model.live="medico" class="block mt-1 {{ $campo }}">
                <option value="">Todos</option>
                @foreach ($medicos as $m)
                    <option value="{{ $m->id }}">{{ $m->nombre_completo }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-xs text-gray-500">Especialidad
            <select wire:model.live="especialidad" class="block mt-1 {{ $campo }}">
                <option value="">Todas</option>
                @foreach ($especialidades as $e)
                    <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                @endforeach
            </select>
        </label>
    </div>

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="tarjeta p-5">
            <p class="text-sm text-gray-500">Cumplimiento promedio</p>
            <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ $promedio }}%</p>
            <p class="text-xs text-gray-400 mt-1">{{ number_format($n) }} {{ $n === 1 ? 'consulta evaluada' : 'consultas evaluadas' }}{{ $limitado ? ' (últimas)' : '' }}</p>
        </div>
        <div class="tarjeta p-5">
            <p class="text-sm text-gray-500">Historias ≥ {{ \App\Services\CalidadHistoriaService::UMBRAL_BUENO }}%</p>
            <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ $buenas }}%</p>
            <p class="text-xs text-gray-400 mt-1">de las consultas del período</p>
        </div>
        <div class="tarjeta p-5">
            <p class="text-sm text-gray-500">Auditorías manuales</p>
            <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ $auditoriasPeriodo }}</p>
            <p class="text-xs text-gray-400 mt-1">cerradas en el período</p>
        </div>
        <div class="tarjeta p-5">
            <p class="text-sm text-gray-500">Planes de acción abiertos</p>
            <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums">{{ $planesAbiertos }}</p>
            <p class="text-xs mt-1 {{ $planesVencidos ? 'text-red-600' : 'text-gray-400' }}">{{ $planesVencidos ? "{$planesVencidos} con plazo vencido" : 'ninguno vencido' }}</p>
        </div>
    </div>

    @if ($n)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Cumplimiento por criterio (barras horizontales, un solo tono) --}}
            <div class="tarjeta p-5">
                <h2 class="text-sm font-semibold text-gray-900">Cumplimiento por bloque del formulario 002</h2>
                <p class="text-xs text-gray-400 mb-4">Ordenado de menor a mayor: arriba lo que más se omite</p>
                <ul class="space-y-2.5">
                    @foreach ($porCriterio as $c)
                        <li class="grid grid-cols-[minmax(0,11rem)_1fr_2.5rem] items-center gap-3 text-sm" title="{{ $c['clave'] }}. {{ $c['titulo'] }}: {{ $c['pct'] }}% de las consultas cumple">
                            <span class="truncate text-gray-600"><span class="font-mono text-xs text-gray-400 mr-1">{{ $c['clave'] }}</span>{{ $c['titulo'] }}</span>
                            <span class="h-2.5 rounded bg-gray-100 overflow-hidden"><span class="block h-full rounded bg-blue-600" style="width: {{ max($c['pct'], 1) }}%"></span></span>
                            <span class="text-right tabular-nums text-gray-700">{{ $c['pct'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="tarjeta p-5">
                <h2 class="text-sm font-semibold text-gray-900">Cumplimiento promedio por médico</h2>
                <p class="text-xs text-gray-400 mb-4">Puntaje ponderado de sus consultas en el período</p>
                <ul class="space-y-2.5">
                    @foreach ($porMedico as $m)
                        <li class="grid grid-cols-[minmax(0,11rem)_1fr_2.5rem] items-center gap-3 text-sm" title="{{ $m['medico'] }}: {{ $m['pct'] }}% en {{ $m['consultas'] }} consultas">
                            <span class="truncate text-gray-600">{{ $m['medico'] }} <span class="text-xs text-gray-400">· {{ $m['consultas'] }}</span></span>
                            <span class="h-2.5 rounded bg-gray-100 overflow-hidden"><span class="block h-full rounded bg-blue-600" style="width: {{ max($m['pct'], 1) }}%"></span></span>
                            <span class="text-right tabular-nums text-gray-700">{{ $m['pct'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Consultas / muestra --}}
    <div class="tarjeta">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-5 py-4 border-b border-gray-100">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">{{ $muestra ? 'Muestra aleatoria para auditoría' : 'Consultas del período' }}</h2>
                <p class="text-xs text-gray-400">{{ $muestra ? count($muestra) . ' consultas elegidas al azar' : 'De menor a mayor cumplimiento' }}</p>
            </div>
            @can('auditoria.gestionar')
                <div class="flex items-center gap-2">
                    @if ($muestra)
                        <button wire:click="quitarMuestra" class="px-3 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-100">Ver todas</button>
                    @endif
                    <input type="number" min="1" max="100" wire:model="tamanoMuestra" class="w-20 {{ $campo }}" aria-label="Tamaño de la muestra">
                    <button wire:click="generarMuestra" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm transition">Generar muestra</button>
                </div>
            @endcan
        </div>
        @error('tamanoMuestra') <p class="px-5 pt-2 text-red-500 text-xs">{{ $message }}</p> @enderror

        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 bg-gray-50/60">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Fecha</th>
                        <th class="text-left font-medium px-5 py-3">Paciente</th>
                        <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Médico</th>
                        <th class="text-left font-medium px-5 py-3 hidden lg:table-cell">Bloques incompletos</th>
                        <th class="text-right font-medium px-5 py-3">Puntaje</th>
                        <th class="px-5 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($tabla as $c)
                        @php
                            $ev = $evaluaciones[$c->id];
                            $faltan = collect($ev['criterios'])->reject(fn ($x) => $x['ok'])->keys();
                            $manual = $auditadas[$c->id] ?? null;
                        @endphp
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-5 py-3 text-gray-600 whitespace-nowrap">{{ $c->fecha->format('d/m/Y') }}</td>
                            <td class="px-5 py-3">
                                <span class="block font-medium text-gray-900">{{ $c->paciente?->nombre_completo }}</span>
                                <span class="block text-xs text-gray-500 font-mono">{{ $c->paciente?->cedula }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-600 hidden md:table-cell">{{ $c->medico?->nombre_completo }}</td>
                            <td class="px-5 py-3 hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($faltan as $k)
                                        <span class="px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 text-xs font-mono" title="{{ \App\Services\CalidadHistoriaService::CRITERIOS[$k][0] }}">{{ $k }}</span>
                                    @empty
                                        <span class="text-xs text-gray-400">Completa</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium tabular-nums {{ $tono($ev['puntaje']) }}" title="Evaluación automática">{{ $ev['puntaje'] }}%</span>
                                @if ($manual)
                                    <span class="block text-[11px] text-gray-400 mt-0.5">Auditada: {{ $manual->puntaje }}%</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('auditoria.evaluar', $c) }}" wire:navigate class="px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition">{{ auth()->user()->can('auditoria.gestionar') ? 'Auditar' : 'Ver' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No hay consultas en el período seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tabla->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $tabla->links() }}</div>
        @endif
    </div>
</div>
</div>
