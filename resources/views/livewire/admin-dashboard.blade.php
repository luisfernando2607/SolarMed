<div class="dash">
<style>
    /* Paleta de datos (validada: separación para daltonismo y contraste sobre fondo claro) */
    .dash {
        --serie-1: #2a78d6;   /* azul: serie principal */
        --serie-2: #eb6834;   /* naranja: segunda serie */
        --serie-neutra: #c9c7c0;
        --tinta-1: #1f1f1d;
        --tinta-2: #5f5e5a;
        --tinta-3: #8a8984;
        --borde: #e9e7e2;
    }
    .dash .tarjeta { background: #fff; border: 1px solid var(--borde); border-radius: 16px; }
    .dash .titulo-tarjeta { font-size: 14px; font-weight: 600; color: var(--tinta-1); }
    .dash .subtitulo-tarjeta { font-size: 12px; color: var(--tinta-3); margin-top: 2px; }
    .dash .lienzo { position: relative; width: 100%; }
    .dash .punto { display: inline-block; width: 8px; height: 8px; border-radius: 9999px; }
</style>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

    {{-- ── Encabezado ── --}}
    @php
        $hora = (int) now()->format('H');
        $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    @endphp
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500">{{ ucfirst(now()->translatedFormat('l, d \d\e F \d\e Y')) }}</p>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mt-1">{{ $saludo }}, {{ Str::before(auth()->user()->name, ' ') }}</h1>
            <p class="text-sm text-gray-500 mt-1">Resumen de {{ $marca->nombre() }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('agenda') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75"/></svg>
                Agenda
            </a>
            <a href="{{ route('turnos.crear') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Registrar turno
            </a>
            <a href="{{ route('facturas.crear') }}" wire:navigate class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium text-white shadow-xs hover:opacity-90 transition" style="background: {{ $marca->color() }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Nueva factura
            </a>
        </div>
    </div>

    {{-- ── Indicadores del mes ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ($kpis as $k)
            <div class="tarjeta p-5">
                <p class="text-sm font-medium text-gray-500">{{ $k['titulo'] }}</p>
                <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $k['valor'] }}</p>
                <div class="mt-3 flex items-center gap-2 text-xs">
                    @if ($k['delta'] === null)
                        <span class="text-gray-400">Sin datos del mes anterior</span>
                    @else
                        @php $sube = $k['delta'] >= 0; @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium {{ $sube ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $sube ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}"/>
                            </svg>
                            {{ number_format(abs($k['delta']), 1) }} %
                        </span>
                        <span class="text-gray-400">vs. mes anterior</span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-gray-400">{{ $k['detalle'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- ── Requiere atención ── --}}
    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Requiere atención</h2>
        @if ($estadoSistema['errores'] || $estadoSistema['avisos'])
            <a href="{{ route('sistema') }}" wire:navigate
               class="mb-3 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm transition {{ $estadoSistema['errores'] ? 'border-red-200 bg-red-50 text-red-800 hover:bg-red-100' : 'border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <span class="flex-1">
                    Estado del sistema:
                    @if ($estadoSistema['errores'])
                        {{ $estadoSistema['errores'] }} {{ $estadoSistema['errores'] === 1 ? 'punto necesita' : 'puntos necesitan' }} atención
                    @else
                        {{ $estadoSistema['avisos'] }} {{ $estadoSistema['avisos'] === 1 ? 'punto' : 'puntos' }} para revisar
                    @endif
                </span>
                <span class="font-medium">Ver →</span>
            </a>
        @endif
        <livewire:panel-pendientes />
    </section>

    {{-- ── Tendencias ── --}}
    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Últimos 6 meses</h2>
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            <div class="tarjeta p-5 lg:col-span-3">
                <p class="titulo-tarjeta">Facturación mensual</p>
                <p class="subtitulo-tarjeta">Facturas pagadas · total del período ${{ number_format($totalFacturado6, 2) }}</p>
                <div class="lienzo mt-4" style="height: 260px"><canvas id="chartFacturacion" aria-label="Facturación mensual de los últimos 6 meses" role="img"></canvas></div>
            </div>

            <div class="tarjeta p-5 lg:col-span-2">
                <p class="titulo-tarjeta">Turnos por mes</p>
                <p class="subtitulo-tarjeta">{{ number_format($totalTurnos6) }} turnos · cancelación {{ number_format($tasaCancelacion, 1) }} %</p>
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1.5"><span class="punto" style="background: var(--serie-1)"></span>Completados ({{ array_sum($series['completados']) }})</span>
                    <span class="inline-flex items-center gap-1.5"><span class="punto" style="background: var(--serie-2)"></span>Cancelados ({{ array_sum($series['cancelados']) }})</span>
                    <span class="inline-flex items-center gap-1.5"><span class="punto" style="background: var(--serie-neutra)"></span>Otros ({{ array_sum($series['otros']) }})</span>
                </div>
                <div class="lienzo mt-3" style="height: 230px"><canvas id="chartTurnos" aria-label="Turnos completados, cancelados y otros por mes" role="img"></canvas></div>
            </div>
        </div>
    </section>

    {{-- ── Atención clínica ── --}}
    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Atención clínica</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="tarjeta p-5">
                <p class="titulo-tarjeta">Motivos de consulta más frecuentes</p>
                <p class="subtitulo-tarjeta">Número de consultas</p>
                @if ($motivos->isEmpty())
                    <p class="text-sm text-gray-400 py-10 text-center">Aún no hay consultas registradas.</p>
                @else
                    <div class="lienzo mt-4" style="height: {{ 40 + $motivos->count() * 34 }}px"><canvas id="chartMotivos" role="img" aria-label="Motivos de consulta más frecuentes"></canvas></div>
                @endif
            </div>
            <div class="tarjeta p-5">
                <p class="titulo-tarjeta">Diagnósticos más frecuentes</p>
                <p class="subtitulo-tarjeta">Por código CIE-10</p>
                @if ($diagnosticos->isEmpty())
                    <p class="text-sm text-gray-400 py-10 text-center">Aún no hay diagnósticos con código CIE-10.</p>
                @else
                    <div class="lienzo mt-4" style="height: {{ 40 + $diagnosticos->count() * 34 }}px"><canvas id="chartDiagnosticos" role="img" aria-label="Diagnósticos más frecuentes"></canvas></div>
                @endif
            </div>
        </div>
    </section>

    {{-- ── Pacientes ── --}}
    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Pacientes</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="tarjeta p-5">
                <p class="titulo-tarjeta">Rangos de edad</p>
                <p class="subtitulo-tarjeta">Pacientes con fecha de nacimiento</p>
                <div class="lienzo mt-4" style="height: 220px"><canvas id="chartEdad" role="img" aria-label="Pacientes por rango de edad"></canvas></div>
            </div>

            <div class="tarjeta p-5">
                <p class="titulo-tarjeta">Sexo</p>
                @php
                    $totalSexo = max(1, $porSexo->sum());
                    $etiquetasSexo = ['femenino' => 'Femenino', 'masculino' => 'Masculino', 'otro' => 'Otro'];
                    $coloresSexo = ['femenino' => 'var(--serie-1)', 'masculino' => 'var(--serie-2)', 'otro' => 'var(--serie-neutra)'];
                @endphp
                <p class="subtitulo-tarjeta">{{ number_format($porSexo->sum()) }} pacientes</p>
                <div class="mt-6 flex h-3 rounded-full overflow-hidden gap-0.5 bg-gray-100">
                    @foreach ($etiquetasSexo as $clave => $texto)
                        @if (($porSexo[$clave] ?? 0) > 0)
                            <div style="width: {{ $porSexo[$clave] / $totalSexo * 100 }}%; background: {{ $coloresSexo[$clave] }}" title="{{ $texto }}: {{ $porSexo[$clave] }}"></div>
                        @endif
                    @endforeach
                </div>
                <dl class="mt-5 space-y-3">
                    @foreach ($etiquetasSexo as $clave => $texto)
                        <div class="flex items-center justify-between text-sm">
                            <dt class="inline-flex items-center gap-2 text-gray-600"><span class="punto" style="background: {{ $coloresSexo[$clave] }}"></span>{{ $texto }}</dt>
                            <dd class="text-gray-900"><span class="font-semibold">{{ number_format(($porSexo[$clave] ?? 0) / $totalSexo * 100, 0) }} %</span> <span class="text-gray-400 text-xs">({{ $porSexo[$clave] ?? 0 }})</span></dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            <div class="tarjeta p-5">
                <p class="titulo-tarjeta">Ciudades</p>
                <p class="subtitulo-tarjeta">Donde viven los pacientes</p>
                @php $maxCiudad = max(1, (int) $ciudades->max('total')); @endphp
                <div class="mt-5 space-y-3">
                    @forelse ($ciudades as $c)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700 truncate">{{ $c->ciudad }}</span>
                                <span class="font-semibold text-gray-900">{{ $c->total }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-gray-100"><div class="h-2 rounded-full" style="width: {{ $c->total / $maxCiudad * 100 }}%; background: var(--serie-1)"></div></div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-6 text-center">Sin ciudades registradas.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- ── Mapa de calor ── --}}
    <section>
        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3">Demanda</h2>
        <div class="tarjeta p-5">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <p class="titulo-tarjeta">Turnos por día y hora</p>
                    <p class="subtitulo-tarjeta">Últimos 3 meses · útil para planificar el personal</p>
                </div>
                @php $rampa = ['#f3f2ee', '#cde2fb', '#9ec5f4', '#6da7ec', '#3987e5', '#256abf', '#184f95']; @endphp
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    Menos
                    <div class="flex gap-0.5">@foreach ($rampa as $c)<span class="w-5 h-3 rounded-xs" style="background: {{ $c }}"></span>@endforeach</div>
                    Más
                </div>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-xs border-separate" style="border-spacing: 3px">
                    <thead>
                        <tr>
                            <th class="w-12"></th>
                            @foreach ($diasSemana as $dia)
                                <th class="font-medium text-gray-500 pb-1">{{ $dia }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($horas as $h)
                            <tr>
                                <td class="text-right pr-2 text-gray-400 whitespace-nowrap">{{ sprintf('%02d:00', $h) }}</td>
                                @foreach ($diasSemana as $num => $dia)
                                    @php
                                        $v = $heatGrid[$h][$num] ?? 0;
                                        $nivel = $maxHeat > 0 && $v > 0 ? (int) ceil($v / $maxHeat * (count($rampa) - 1)) : 0;
                                    @endphp
                                    <td class="h-7 rounded-md text-center align-middle {{ $nivel >= 4 ? 'text-white' : 'text-gray-600' }}"
                                        style="background: {{ $rampa[$nivel] }}" title="{{ $dia }} {{ sprintf('%02d:00', $h) }}: {{ $v }} turno(s)">
                                        {{ $v ?: '' }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<script>
(function () {
    const datos = {
        meses: {{ \Illuminate\Support\Js::from($series['meses']) }},
        facturacion: {{ \Illuminate\Support\Js::from($series['facturacion']) }},
        completados: {{ \Illuminate\Support\Js::from($series['completados']) }},
        cancelados: {{ \Illuminate\Support\Js::from($series['cancelados']) }},
        otros: {{ \Illuminate\Support\Js::from($series['otros']) }},
        motivos: {{ \Illuminate\Support\Js::from($motivos) }},
        diagnosticos: {{ \Illuminate\Support\Js::from($diagnosticos) }},
        edad: {{ \Illuminate\Support\Js::from($rangosEdad) }},
    };
    let charts = [];

    function initCharts() {
        charts.forEach(c => c.destroy());
        charts = [];
        // Solo en la página del dashboard (con navegación SPA el evento llega también a otras páginas)
        if (!document.getElementById('chartFacturacion') || !window.Chart) return;

        const css = getComputedStyle(document.querySelector('.dash'));
        const v = n => css.getPropertyValue(n).trim();
        const S1 = v('--serie-1'), S2 = v('--serie-2'), SN = v('--serie-neutra'), T2 = v('--tinta-2'), T3 = v('--tinta-3'), B = v('--borde');
        const dinero = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });

        Chart.defaults.font.family = 'Figtree, ui-sans-serif, system-ui, sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.color = T3;

        const tooltip = {
            backgroundColor: '#ffffff', titleColor: '#1f1f1d', bodyColor: T2, borderColor: B, borderWidth: 1,
            padding: 10, cornerRadius: 10, boxPadding: 4, usePointStyle: true,
        };
        const ejeX = { grid: { display: false }, border: { display: false }, ticks: { color: T3 } };
        const ejeY = { grid: { color: '#f0efea' }, border: { display: false }, ticks: { color: T3, maxTicksLimit: 5, precision: 0 }, beginAtZero: true };

        // Facturación: una serie → área con línea de 2px
        const ctxF = document.getElementById('chartFacturacion').getContext('2d');
        const degradado = ctxF.createLinearGradient(0, 0, 0, 260);
        degradado.addColorStop(0, 'rgba(42, 120, 214, 0.18)');
        degradado.addColorStop(1, 'rgba(42, 120, 214, 0)');
        charts.push(new Chart(ctxF, {
            type: 'line',
            data: { labels: datos.meses, datasets: [{ label: 'Facturado', data: datos.facturacion, borderColor: S1, backgroundColor: degradado, fill: true, borderWidth: 2, cubicInterpolationMode: 'monotone', pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: S1, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2 }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip: { ...tooltip, callbacks: { label: c => ' ' + dinero(c.parsed.y) } } },
                scales: { x: ejeX, y: { ...ejeY, ticks: { ...ejeY.ticks, callback: dinero } } },
            },
        }));

        // Turnos: barras apiladas con un solo eje; 2px de separación del color de fondo entre segmentos
        const segmento = { borderWidth: { top: 2 }, borderColor: '#ffffff', borderSkipped: 'bottom', borderRadius: 4, maxBarThickness: 32 };
        charts.push(new Chart(document.getElementById('chartTurnos'), {
            type: 'bar',
            data: {
                labels: datos.meses,
                datasets: [
                    { label: 'Completados', data: datos.completados, backgroundColor: S1, ...segmento },
                    { label: 'Cancelados', data: datos.cancelados, backgroundColor: S2, ...segmento },
                    { label: 'Otros', data: datos.otros, backgroundColor: SN, ...segmento },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: false }, tooltip },
                scales: { x: { ...ejeX, stacked: true }, y: { ...ejeY, stacked: true } },
            },
        }));

        // Rankings horizontales (una serie)
        const ranking = (id, filas) => {
            const el = document.getElementById(id);
            if (!el || !filas.length) return;
            charts.push(new Chart(el, {
                type: 'bar',
                data: { labels: filas.map(f => f.etiqueta), datasets: [{ label: 'Consultas', data: filas.map(f => f.total), backgroundColor: S1, borderRadius: 4, borderSkipped: 'start', maxBarThickness: 18 }] },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip },
                    scales: {
                        x: { ...ejeY },
                        y: { grid: { display: false }, border: { display: false }, ticks: { color: T2, autoSkip: false } },
                    },
                },
            }));
        };
        ranking('chartMotivos', datos.motivos);
        ranking('chartDiagnosticos', datos.diagnosticos);

        // Edad
        charts.push(new Chart(document.getElementById('chartEdad'), {
            type: 'bar',
            data: { labels: Object.keys(datos.edad), datasets: [{ label: 'Pacientes', data: Object.values(datos.edad), backgroundColor: S1, borderRadius: 4, borderSkipped: 'bottom', maxBarThickness: 28 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip }, scales: { x: ejeX, y: ejeY } },
        }));
    }

    /* El script se vuelve a ejecutar en cada visita; un único escuchador llama a la versión más reciente */
    window.__dashboardInitCharts = initCharts;
    if (!window.__dashboardChartsRegistrado) {
        window.__dashboardChartsRegistrado = true;
        document.addEventListener('DOMContentLoaded', () => window.__dashboardInitCharts?.());
        document.addEventListener('livewire:navigated', () => window.__dashboardInitCharts?.());
    }
})();
</script>
</div>
