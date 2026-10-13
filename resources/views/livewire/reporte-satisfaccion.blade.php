@php
    $campo = 'rounded-xl border-gray-200 bg-white shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500';
    $tonoNps = fn ($v) => $v === null ? 'text-gray-400' : ($v >= 50 ? 'text-emerald-700' : ($v >= 0 ? 'text-gray-900' : 'text-red-700'));
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Satisfacción de los pacientes</h1>
        <p class="text-sm text-gray-500 mt-1">Encuesta que recibe cada paciente después de su consulta. Las respuestas no muestran quién las escribió.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Desde</span><input wire:model.live="desde" type="date" class="w-full {{ $campo }}"></label>
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Hasta</span><input wire:model.live="hasta" type="date" class="w-full {{ $campo }}"></label>
        <label class="block"><span class="block text-xs text-gray-500 mb-1">Médico</span>
            <select wire:model.live="medico" class="w-full {{ $campo }}">
                <option value="">Todos</option>
                @foreach ($medicos as $m)<option value="{{ $m->id }}">{{ $m->nombre_completo }}</option>@endforeach
            </select>
        </label>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
            <p class="text-xs text-gray-500">Satisfacción promedio</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $kpis['promedio'] ?? '—' }}<span class="text-sm font-normal text-gray-400"> / 5</span></p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5" title="Porcentaje de promotores (9-10) menos porcentaje de detractores (0-6)">
            <p class="text-xs text-gray-500">Índice de recomendación (NPS)</p>
            <p class="mt-1 text-2xl font-semibold tabular-nums {{ $tonoNps($kpis['nps']) }}">{{ $kpis['nps'] !== null ? ($kpis['nps'] > 0 ? '+' : '') . $kpis['nps'] : '—' }}</p>
            <p class="text-[11px] text-gray-400">de −100 a +100</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
            <p class="text-xs text-gray-500">Respuestas</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ number_format($kpis['respuestas']) }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
            <p class="text-xs text-gray-500">Tasa de respuesta</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $kpis['tasa'] !== null ? $kpis['tasa'] . ' %' : '—' }}</p>
            <p class="text-[11px] text-gray-400">{{ $kpis['creadas'] }} consultas con encuesta</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
        <div class="bg-white rounded-2xl border border-gray-200/80 px-5 py-4">
            <h2 class="text-sm font-semibold text-gray-900 mb-3">Promedio por aspecto</h2>
            @foreach ($preguntas as $p)
                <div class="py-2" title="{{ $p['texto'] }}: {{ $p['promedio'] ?? 'sin datos' }} de 5">
                    <div class="flex justify-between text-sm"><span class="text-gray-700">{{ $p['texto'] }}</span><span class="font-medium text-gray-900 tabular-nums">{{ $p['promedio'] ?? '—' }}</span></div>
                    <div class="mt-1.5 h-1.5 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full bg-blue-600" style="width: {{ $p['promedio'] ? round($p['promedio'] * 20) : 0 }}%"></div></div>
                </div>
            @endforeach
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
            <h2 class="text-sm font-semibold text-gray-900 px-5 pt-4 pb-3">Por médico</h2>
            <div class="relative overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-xs text-gray-500 border-y border-gray-100">
                        <tr><th class="text-left font-medium px-5 py-2">Médico</th><th class="text-right font-medium px-3 py-2">Respuestas</th><th class="text-right font-medium px-3 py-2">Promedio</th><th class="text-right font-medium px-5 py-2">NPS</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 tabular-nums">
                        @forelse ($porMedico as $m)
                            <tr>
                                <td class="px-5 py-2 text-gray-900">{{ $m['nombre'] }}</td>
                                <td class="px-3 py-2 text-right">{{ $m['respuestas'] }}</td>
                                <td class="px-3 py-2 text-right font-medium">{{ $m['promedio'] }}</td>
                                <td class="px-5 py-2 text-right {{ $tonoNps($m['nps']) }}">{{ $m['nps'] !== null ? ($m['nps'] > 0 ? '+' : '') . $m['nps'] : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-gray-400">Aún no hay respuestas en este período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <h2 class="text-sm font-semibold text-gray-900 px-5 pt-4 pb-3">Comentarios recientes</h2>
        @forelse ($comentarios as $c)
            <div class="px-5 py-3 border-t border-gray-100">
                <p class="text-sm text-gray-800">“{{ $c->comentario }}”</p>
                <p class="text-xs text-gray-400 mt-1">{{ $c->respondida_at->format('d/m/Y') }} · {{ $c->medico?->nombre_completo }} · {{ $c->promedio() }}/5 · recomendaría {{ $c->recomendacion }}/10</p>
            </div>
        @empty
            <p class="px-5 py-10 border-t border-gray-100 text-center text-sm text-gray-400">Sin comentarios en este período.</p>
        @endforelse
    </div>
</div>
