@php
    $campo = 'rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $pct = fn ($n, $d) => $d > 0 ? round($n * 100 / $d) : 0;
    $maxTop = max(1, $top->max('total') ?? 1);
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Producción y morbilidad</h1>
            <p class="text-sm text-gray-500 mt-1">Atenciones de consulta externa por tipo, sexo, grupo de edad y diagnóstico CIE-10.</p>
        </div>
        <button wire:click="exportar" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm transition shrink-0">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Exportar CSV
        </button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Desde</span><input wire:model.live="desde" type="date" class="w-full {{ $campo }}"></label>
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Hasta</span><input wire:model.live="hasta" type="date" class="w-full {{ $campo }}"></label>
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Profesional</span>
            <select wire:model.live="medico" class="w-full {{ $campo }}">
                <option value="">Todos</option>
                @foreach ($medicos as $m)<option value="{{ $m->id }}">{{ $m->nombre_completo }}</option>@endforeach
            </select>
        </label>
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Especialidad</span>
            <select wire:model.live="especialidad" class="w-full {{ $campo }}">
                <option value="">Todas</option>
                @foreach ($especialidades as $e)<option value="{{ $e->id }}">{{ $e->nombre }}</option>@endforeach
            </select>
        </label>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ([
            ['Atenciones', $kpis['total'], $kpis['pacientes'] . ' ' . ($kpis['pacientes'] === 1 ? 'paciente' : 'pacientes')],
            ['Primera vez', $kpis['primera'], $pct($kpis['primera'], $kpis['total']) . ' % de las atenciones'],
            ['Subsecuentes', $kpis['subsecuente'], $kpis['sin_tipo'] ? $kpis['sin_tipo'] . ' sin tipo registrado' : 'todas con tipo registrado'],
            ['Con código CIE-10', $pct($kpis['con_cie'], $kpis['total']) . ' %', $kpis['con_cie'] . ' de ' . $kpis['total']],
        ] as [$titulo, $valor, $nota])
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ is_int($valor) ? number_format($valor) : $valor }}</p>
                <p class="text-[11px] text-gray-400">{{ $nota }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
        {{-- Sexo y grupo de edad --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="px-5 pt-4 pb-3">
                <h2 class="text-sm font-semibold text-gray-900">Atenciones por grupo de edad y sexo</h2>
                <p class="text-xs text-gray-500">Edad cumplida a la fecha de la atención</p>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 border-y border-gray-100">
                        <tr>
                            <th class="text-left font-medium px-5 py-2">Grupo</th>
                            <th class="text-right font-medium px-3 py-2">Hombres</th>
                            <th class="text-right font-medium px-3 py-2">Mujeres</th>
                            <th class="text-right font-medium px-5 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 tabular-nums">
                        @foreach ($matriz as $grupo => $f)
                            @php $t = array_sum($f); @endphp
                            <tr class="{{ $t === 0 ? 'text-gray-300' : 'text-gray-700' }}">
                                <td class="px-5 py-2 {{ $t ? 'text-gray-900' : '' }}">{{ $grupo }}</td>
                                <td class="px-3 py-2 text-right">{{ $f['H'] }}</td>
                                <td class="px-3 py-2 text-right">{{ $f['M'] }}</td>
                                <td class="px-5 py-2 text-right font-medium">{{ $t }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200 font-semibold text-gray-900 tabular-nums">
                        <tr>
                            <td class="px-5 py-2">Total</td>
                            <td class="px-3 py-2 text-right">{{ array_sum(array_column($matriz, 'H')) }}</td>
                            <td class="px-3 py-2 text-right">{{ array_sum(array_column($matriz, 'M')) }}</td>
                            <td class="px-5 py-2 text-right">{{ $kpis['total'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Morbilidad --}}
        <div class="lg:col-span-3 bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <div class="px-5 pt-4 pb-3">
                <h2 class="text-sm font-semibold text-gray-900">10 diagnósticos más frecuentes</h2>
                <p class="text-xs text-gray-500">Por código CIE-10 · {{ $kpis['total'] - $kpis['con_cie'] }} atenciones sin código no se cuentan</p>
            </div>
            @forelse ($top as $i => $d)
                <div class="px-5 py-2.5 border-t border-gray-100" title="{{ $d['total'] }} atenciones: {{ $d['H'] }} hombres, {{ $d['M'] }} mujeres">
                    <div class="flex items-baseline gap-3 text-sm">
                        <span class="w-5 text-xs text-gray-400 tabular-nums">{{ $i + 1 }}</span>
                        <span class="font-mono text-[13px] text-gray-900 w-14 shrink-0">{{ $d['codigo'] }}</span>
                        <span class="flex-1 min-w-0 truncate text-gray-700">{{ $d['descripcion'] }}</span>
                        <span class="font-medium text-gray-900 tabular-nums">{{ $d['total'] }}</span>
                    </div>
                    <div class="ml-8 mt-1.5 flex items-center gap-3">
                        <div class="flex-1 h-1.5 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full rounded-full bg-blue-600" style="width: {{ round($d['total'] * 100 / $maxTop) }}%"></div>
                        </div>
                        <span class="text-[11px] text-gray-400 w-24 text-right">H {{ $d['H'] }} · M {{ $d['M'] }}</span>
                    </div>
                </div>
            @empty
                <p class="px-5 py-12 border-t border-gray-100 text-center text-sm text-gray-400">No hay diagnósticos con código CIE-10 en el período.</p>
            @endforelse
        </div>
    </div>

    {{-- Por profesional --}}
    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="px-5 pt-4 pb-3"><h2 class="text-sm font-semibold text-gray-900">Producción por profesional</h2></div>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-y border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-2">Profesional</th>
                        <th class="text-right font-medium px-3 py-2">Atenciones</th>
                        <th class="text-right font-medium px-3 py-2">Primera vez</th>
                        <th class="text-right font-medium px-3 py-2">Subsecuentes</th>
                        <th class="text-right font-medium px-5 py-2">Con CIE-10</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 tabular-nums">
                    @forelse ($porMedico as $m)
                        <tr>
                            <td class="px-5 py-2 text-gray-900">{{ $m['nombre'] }}</td>
                            <td class="px-3 py-2 text-right font-medium">{{ $m['total'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $m['primera'] }}</td>
                            <td class="px-3 py-2 text-right">{{ $m['subsecuente'] }}</td>
                            <td class="px-5 py-2 text-right">{{ $pct($m['con_cie'], $m['total']) }} %</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No hay atenciones en el período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-xs text-gray-400">La exportación identifica a cada paciente solo por su número de historia clínica (uso estadístico, AM 5216-A Art. 7) y queda registrada en la auditoría.</p>
</div>
