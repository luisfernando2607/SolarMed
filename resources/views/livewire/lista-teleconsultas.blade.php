@php $campo = 'rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500'; @endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Telemedicina</h1>
            <p class="text-sm text-gray-500 mt-1">Videoconsultas agendadas y realizadas (Norma Técnica de Telesalud del MSP).</p>
        </div>
        @can('turnos.gestionar')
            @if ($activa)
                <a href="{{ route('agenda') }}" wire:navigate class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-xs">
                    Agendar teleconsulta
                </a>
            @endif
        @endcan
    </div>

    @unless ($activa)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
            La telemedicina está desactivada. Se activa en Configuración → Telemedicina, una vez que la clínica cuente con el permiso de ACESS para servicios de telesalud.
        </div>
    @endunless

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ([
            ['Próximas', $kpis['proximas'], $kpis['sin_consentimiento'] . ' sin consentimiento aún'],
            ['Realizadas este mes', $kpis['realizadas_mes'], $kpis['duracion'] !== null ? 'duración promedio ' . $kpis['duracion'] . ' min' : 'sin duración registrada'],
            ['Conexión efectiva', $kpis['efectivas'] !== null ? $kpis['efectivas'] . ' %' : '—', 'de las finalizadas este mes'],
            ['No se conectaron', $kpis['no_asistio_mes'], 'este mes'],
        ] as [$titulo, $valor, $nota])
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900 tabular-nums">{{ $valor }}</p>
                <p class="text-[11px] text-gray-400">{{ $nota }}</p>
            </div>
        @endforeach
    </div>

    <div class="flex flex-col sm:flex-row gap-3">
        <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 text-sm self-start">
            @foreach (['proximas' => 'Próximas', 'pasadas' => 'Pasadas', 'todas' => 'Todas'] as $valor => $texto)
                <button wire:click="$set('vista', '{{ $valor }}')" class="px-3 py-1.5 rounded-lg transition {{ $vista === $valor ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-600 hover:text-gray-900' }}">{{ $texto }}</button>
            @endforeach
        </div>
        <select wire:model.live="medico" class="sm:w-64 {{ $campo }}">
            <option value="">Todos los médicos</option>
            @foreach ($medicos as $m)<option value="{{ $m->id }}">{{ $m->nombre_completo }}</option>@endforeach
        </select>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Fecha</th>
                        <th class="text-left font-medium px-5 py-3">Paciente</th>
                        <th class="text-left font-medium px-5 py-3 hidden md:table-cell">Médico</th>
                        <th class="text-left font-medium px-5 py-3">Estado</th>
                        <th class="text-left font-medium px-5 py-3 hidden sm:table-cell">Consentimiento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($teleconsultas as $tc)
                        <tr class="hover:bg-gray-50/70 cursor-pointer" onclick="Livewire.navigate({{ \Illuminate\Support\Js::from(route('telemedicina.ver', $tc)) }})">
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700 tabular-nums">{{ $tc->fecha_hora->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('telemedicina.ver', $tc) }}" wire:navigate class="font-medium text-gray-900 hover:text-blue-700">{{ $tc->paciente?->nombre_visible }}</a>
                            </td>
                            <td class="px-5 py-3 text-gray-600 hidden md:table-cell">{{ $tc->medico?->nombre_completo }}</td>
                            <td class="px-5 py-3"><span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $tc->estadoTono() }}">{{ $tc->estadoTexto() }}</span></td>
                            <td class="px-5 py-3 hidden sm:table-cell text-xs {{ $tc->tieneConsentimiento() ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $tc->tieneConsentimiento() ? 'Aceptado' : 'Pendiente' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-gray-400">No hay teleconsultas en esta vista.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($teleconsultas->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $teleconsultas->links() }}</div>
        @endif
    </div>
</div>
