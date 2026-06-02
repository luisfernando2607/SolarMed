<div>
<style>
/* ─── Grid & Cards ─────────────────────────────────────────── */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
    padding: 8px 0;
}
.chart-card {
    background: white;
    border: 0.5px solid rgba(0,0,0,0.08);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
}
.chart-label  { font-size: 10px; font-weight: 500; text-transform: uppercase; letter-spacing: .06em; color: #888780; margin: 0; }
.chart-title  { font-size: 13px; font-weight: 500; color: #2C2C2A; margin: 2px 0 12px; }
.chart-wrap   { position: relative; width: 100%; }
.chart-legend { display: flex; gap: 14px; flex-wrap: wrap; margin-top: 8px; }
.chart-legend span { display: flex; align-items: center; gap: 5px; font-size: 10px; color: #888780; }
.chart-legend i    { width: 9px; height: 9px; border-radius: 2px; flex-shrink: 0; }

</style>


<div class="charts-grid mb-8">

    {{-- ── Row 1: Turnos + Facturación (span2) ── --}}
    <div class="chart-card">
        <p class="chart-label">Operación</p>
        <p class="chart-title">Turnos por mes</p>
        <div class="chart-wrap" style="height:210px"><canvas id="chartTurnos"></canvas></div>
        <div class="chart-legend">
            <span><i style="background:#639922"></i>Completados</span>
            <span><i style="background:#E24B4A"></i>Cancelados</span>
            <span><i style="background:#BA7517;border-radius:50%"></i>% Cancelación</span>
        </div>
    </div>

    <div class="chart-card" style="grid-column:span 2">
        <p class="chart-label">Finanzas</p>
        <p class="chart-title">Facturación mensual</p>
        <div class="chart-wrap" style="height:210px"><canvas id="chartFacturacion"></canvas></div>
        <div class="chart-legend">
            <span><i style="background:#378ADD"></i>Facturado</span>
        </div>
    </div>

    {{-- ── Row 2: Motivos (span2) + Diagnósticos ── --}}
    <div class="chart-card" style="grid-column:span 2">
        <p class="chart-label">Servicio</p>
        <p class="chart-title">Motivos de consulta más frecuentes</p>
        <div class="chart-wrap" style="height:400px"><canvas id="chartEspecialidad"></canvas></div>
        <div class="chart-legend">
            <span><i style="background:#1D9E75"></i>Med. General</span>
            <span><i style="background:#7F77DD"></i>Ginecología</span>
        </div>
    </div>

    <div class="chart-card">
        <p class="chart-label">Clínica</p>
        <p class="chart-title">Diagnósticos más frecuentes</p>
        <div class="chart-wrap" style="height:400px"><canvas id="chartDiagnosticos"></canvas></div>
        <p style="font-size:10px;color:#888780;margin-top:6px">* Escala logarítmica — hover para ver nombre completo del diagnóstico</p>
    </div>

        {{-- Ciudad: barras top 10 --}}
    <div class="chart-card">
        <p class="chart-label">Geografía</p>
        <p class="chart-title">Top ciudades</p>
        <div class="chart-wrap" style="height:160px"><canvas id="chartCiudad"></canvas></div>
    </div>
    
    {{-- ── Row 3: Sexo + Edad + Ciudad ── --}}
    <div class="chart-card">
        <p class="chart-label">Distribución</p>
        <p class="chart-title">Pacientes por sexo</p>
        <div class="chart-wrap" style="height:160px;position:relative">
            <canvas id="chartSexo"></canvas>
            <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-60%);text-align:center;pointer-events:none">
                <div style="font-size:20px;font-weight:500;color:#2C2C2A">{{ $porSexo->sum() }}</div>
                <div style="font-size:10px;color:#888780">pacientes</div>
            </div>
        </div>
        <div class="chart-legend" style="justify-content:center">
            <span><i style="background:#378ADD"></i>Masculino</span>
            <span><i style="background:#D4537E"></i>Femenino</span>
            <span><i style="background:#888780"></i>Otro</span>
        </div>
    </div>

    <div class="chart-card">
        <p class="chart-label">Demografía</p>
        <p class="chart-title">Rangos de edad</p>
        <div class="chart-wrap" style="height:160px"><canvas id="chartEdad"></canvas></div>
        <div class="chart-legend">
            <span><i style="background:#7F77DD"></i>Pacientes</span>
            <span><i style="background:#BA7517;border-radius:50%"></i>Acumulado %</span>
        </div>
    </div>

    {{-- ── Row 5: Heatmap (span3) ── --}}
    @php $maxH = $maxHeat ?: 1; @endphp
    <div class="chart-card" style="grid-column:span 3">
        <p class="chart-label">Optimización</p>
        <p class="chart-title">Mapa de calor — Turnos por hora y día (últimos 3 meses)</p>
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:separate;border-spacing:3px;font-size:11px">
                <thead>
                    <tr>
                        <th style="padding:3px 8px;color:#888780;font-size:10px;font-weight:500;text-align:left;min-width:38px">Hora</th>
                        @foreach ([2=>'Lun',3=>'Mar',4=>'Mié',5=>'Jue',6=>'Vie',7=>'Sáb',1=>'Dom'] as $l)
                            <th style="padding:3px 8px;color:#5F5E5A;font-size:10px;font-weight:500;text-align:center;min-width:80px">{{ $l }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach (range(8, 19) as $h)
                        <tr>
                            <td style="padding:4px 8px;color:#888780;font-size:10px">{{ str_pad($h,2,'0',STR_PAD_LEFT) }}</td>
                            @foreach ([2,3,4,5,6,7,1] as $d)
                                @php
                                    $v = $heatGrid[$h][$d] ?? 0;
                                    $p = $v / $maxH;
                                    if ($v === 0)       { $bg = '#f5f5f3'; $fg = 'transparent'; }
                                    elseif ($p < 0.33)  { $bg = '#b0c8e4'; $fg = '#2C2C2A'; }
                                    elseif ($p < 0.66)  { $bg = '#6496c8'; $fg = '#fff'; }
                                    else                { $bg = '#1e488c'; $fg = '#fff'; }
                                @endphp
                                <td style="padding:5px 8px;text-align:center;background:{{ $bg }};color:{{ $fg }};border-radius:6px;font-size:11px;font-weight:500">
                                    {{ $v ?: '' }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;gap:6px;margin-top:10px;font-size:10px;color:#888780">
            <span>Menos</span>
            <span style="width:16px;height:10px;border-radius:3px;display:inline-block;background:#f0f0f0"></span>
            <span style="width:16px;height:10px;border-radius:3px;display:inline-block;background:#b0c8e4"></span>
            <span style="width:16px;height:10px;border-radius:3px;display:inline-block;background:#6496c8"></span>
            <span style="width:16px;height:10px;border-radius:3px;display:inline-block;background:#1e488c"></span>
            <span>Más</span>
            <span style="margin-left:12px">Máx: {{ $maxHeat }} turnos</span>
        </div>
    </div>

</div>

{{-- ═══════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════ --}}
<script>
(function () {

    /* ── Shared config ────────────────────────────────────────── */
    let charts = [];
    const tc  = 'rgba(0,0,0,0.42)';
    const gc  = 'rgba(0,0,0,0.06)';

    const TT = (sym = '') => ({
        backgroundColor: '#fff',
        borderColor: 'rgba(0,0,0,0.10)',
        borderWidth: 1,
        titleColor: '#2C2C2A',
        bodyColor: '#5F5E5A',
        padding: 10,
        cornerRadius: 8,
        boxPadding: 4,
        callbacks: sym
            ? { label: c => ` ${sym}${c.parsed.y.toLocaleString('es-EC', { minimumFractionDigits: 2 })}` }
            : {}
    });

    const BASE = { responsive: true, maintainAspectRatio: false, animation: { duration: 500, easing: 'easeOutQuart' } };
    const XS   = { grid: { display: false }, border: { display: false }, ticks: { color: tc, font: { size: 10 }, maxRotation: 30 } };
    const YS   = { grid: { color: gc }, border: { display: false }, ticks: { color: tc, precision: 0, font: { size: 10 } } };

    /* ── Delta-% plugin para Facturación ─────────────────────── */
    const deltaPlugin = {
        id: 'deltaLabels',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            const ds = data.datasets[0].data;
            ctx.save();
            ctx.font = '500 10px ui-sans-serif,system-ui,sans-serif';
            ctx.textAlign = 'center';
            ds.forEach((v, i) => {
                if (i === 0) return;
                const delta = Math.round((v - ds[i - 1]) / ds[i - 1] * 100);
                const meta  = chart.getDatasetMeta(0);
                const pt    = meta.data[i];
                ctx.fillStyle = delta >= 0 ? '#639922' : '#E24B4A';
                ctx.fillText((delta >= 0 ? '▲' : '▼') + Math.abs(delta) + '%', pt.x, pt.y - 12);
            });
            ctx.restore();
        }
    };

    /* ── Main init ────────────────────────────────────────────── */
    function initCharts() {
        charts.forEach(c => c.destroy());
        charts = [];
        /* 1 · Facturación — Line + Área + Δ% anotado */
        charts.push(new Chart(document.getElementById('chartFacturacion'), {
            type: 'line',
            plugins: [deltaPlugin],
            data: {
                labels: {!! json_encode($facturacionMensual->pluck('mes')) !!},
                datasets: [{
                    label: 'Facturado',
                    data: {!! json_encode($facturacionMensual->pluck('monto')) !!},
                    borderColor: '#378ADD',
                    borderWidth: 2,
                    backgroundColor: 'rgba(55,138,221,0.12)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#378ADD',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                ...BASE,
                layout: { padding: { top: 22 } },
                plugins: { legend: { display: false }, tooltip: TT('$') },
                scales: { x: XS, y: YS }
            }
        }));

        /* 2 · Turnos — Chart Mixto: Bar agrupado + Línea ratio cancelación */
        @php
            $completadosData = $turnosUltimosMeses->pluck('completados');
            $canceladosData  = $turnosUltimosMeses->pluck('cancelados');
            $ratioData = $turnosUltimosMeses->map(function ($row) {
                $total = ($row->completados + $row->cancelados);
                return $total > 0
                    ? round($row->cancelados / $total * 100, 1)
                    : 0;
            });
        @endphp
        charts.push(new Chart(document.getElementById('chartTurnos'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($turnosUltimosMeses->pluck('mes')) !!},
                datasets: [
                    {
                        type: 'bar',
                        label: 'Completados',
                        data: {!! json_encode($completadosData) !!},
                        backgroundColor: '#639922',
                        borderRadius: 5,
                        borderSkipped: false,
                        yAxisID: 'y'
                    },
                    {
                        type: 'bar',
                        label: 'Cancelados',
                        data: {!! json_encode($canceladosData) !!},
                        backgroundColor: '#E24B4A',
                        borderRadius: 5,
                        borderSkipped: false,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: '% Cancelación',
                        data: {!! json_encode($ratioData) !!},
                        borderColor: '#BA7517',
                        borderWidth: 2,
                        borderDash: [4, 3],
                        pointBackgroundColor: '#BA7517',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                ...BASE,
                plugins: { legend: { display: false }, tooltip: TT() },
                scales: {
                    x: XS,
                    y: { ...YS },
                    y2: {
                        position: 'right',
                        border: { display: false },
                        grid: { display: false },
                        ticks: { color: '#BA7517', font: { size: 10 }, callback: v => v + '%' },
                        max: 100
                    }
                }
            }
        }));

        /* 3 · Motivos — Radar */
        @php
        $radarColors = [
            '#1D9E75' => ['border'=>'#1D9E75','bg'=>'rgba(29,158,117,0.15)','point'=>'#1D9E75'],
            '#378ADD' => ['border'=>'#378ADD','bg'=>'rgba(55,138,221,0.15)','point'=>'#378ADD'],
            '#D4537E' => ['border'=>'#D4537E','bg'=>'rgba(212,83,126,0.15)','point'=>'#D4537E'],
            '#BA7517' => ['border'=>'#BA7517','bg'=>'rgba(186,117,23,0.15)','point'=>'#BA7517'],
        ];
        $radarDatasets = collect($motivosDatasets)->map(function ($ds) use ($radarColors) {
            $dsArr = is_array($ds) ? $ds : (array) $ds;
            $bgKey = $dsArr['backgroundColor'] ?? '#888780';
            $c = $radarColors[$bgKey] ?? ['border'=>$bgKey,'bg'=>'rgba(136,135,128,0.15)','point'=>$bgKey];
            return array_merge($dsArr, [
                'borderColor'          => $c['border'],
                'backgroundColor'      => $c['bg'],
                'pointBackgroundColor' => $c['point'],
                'borderWidth'          => 2,
                'pointRadius'          => 4,
            ]);
        })->values()->toArray();
        @endphp
        charts.push(new Chart(document.getElementById('chartEspecialidad'), {
            type: 'radar',
            data: {
                labels: {!! json_encode($motivosLabels) !!},
                datasets: {!! json_encode($radarDatasets) !!}
            },
            options: {
                ...BASE,
                plugins: { legend: { display: false }, tooltip: TT() },
                scales: {
                    r: {
                        grid:         { color: gc },
                        angleLines:   { color: gc },
                        pointLabels:  { color: tc, font: { size: 9 } },
                        ticks:        { color: tc, font: { size: 9 }, stepSize: 1, backdropColor: 'transparent' },
                        min: 0
                    }
                }
            }
        }));

        /* 4 · Diagnósticos — Horizontal + escala logarítmica (original) */
        @php
            $diagLabels = $topDiagnosticos->pluck('codigo');
            $diagData   = $topDiagnosticos->pluck('total');
            $diagMap    = $topDiagnosticos->pluck('diagnostico', 'codigo');
        @endphp
        charts.push(new Chart(document.getElementById('chartDiagnosticos'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($diagLabels) !!},
                datasets: [{
                    data: {!! json_encode($diagData) !!},
                    backgroundColor: '#D4537E',
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: {
                ...BASE,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff', borderColor: 'rgba(0,0,0,0.1)', borderWidth: 1,
                        titleColor: '#2C2C2A', bodyColor: '#5F5E5A', padding: 10, cornerRadius: 8,
                        callbacks: {
                            afterTitle: c => {
                                const m = {!! json_encode($diagMap) !!};
                                return m[c[0].label] || '';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'logarithmic',
                        grid: { color: gc },
                        border: { display: false },
                        ticks: { color: tc, font: { size: 10 }, callback: v => Number.isInteger(Math.log10(v)) ? v : '' }
                    },
                    y: { grid: { display: false }, border: { display: false }, ticks: { color: tc, font: { size: 10 } } }
                }
            }
        }));

        /* 5 · Sexo — Doughnut 68% (original) */
        charts.push(new Chart(document.getElementById('chartSexo'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($porSexo->keys()->map(fn($k) => match($k) { 'masculino' => 'Masculino', 'femenino' => 'Femenino', default => 'Otro' })) !!},
                datasets: [{
                    data: {!! json_encode($porSexo->values()) !!},
                    backgroundColor: ['#378ADD', '#D4537E', '#888780'],
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 5
                }]
            },
            options: {
                ...BASE,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        ...TT(),
                        callbacks: {
                            label: c => {
                                const t = {!! json_encode($porSexo->sum()) !!};
                                return ` ${c.label}: ${c.parsed} (${Math.round(c.parsed / t * 100)}%)`;
                            }
                        }
                    }
                }
            }
        }));

        /* 6 · Edad — Bar + línea acumulada % (combo) */
        @php
            $edadValues = array_values($rangosEdad);
            $edadTotal  = array_sum($edadValues);
            $edadAcum   = [];
            $acum       = 0;
            foreach ($edadValues as $v) {
                $acum += $v;
                $edadAcum[] = $edadTotal > 0 ? round($acum / $edadTotal * 100, 1) : 0;
            }
        @endphp
        charts.push(new Chart(document.getElementById('chartEdad'), {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($rangosEdad)) !!},
                datasets: [
                    {
                        type: 'bar',
                        label: 'Pacientes',
                        data: {!! json_encode($edadValues) !!},
                        backgroundColor: '#7F77DD',
                        borderRadius: 5,
                        borderSkipped: false,
                        yAxisID: 'y'
                    },
                    {
                        type: 'line',
                        label: 'Acumulado %',
                        data: {!! json_encode($edadAcum) !!},
                        borderColor: '#BA7517',
                        borderWidth: 2,
                        borderDash: [3, 3],
                        pointBackgroundColor: '#BA7517',
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        fill: false,
                        tension: 0.3,
                        yAxisID: 'y2'
                    }
                ]
            },
            options: {
                ...BASE,
                plugins: { legend: { display: false }, tooltip: TT() },
                scales: {
                    x: XS,
                    y: { ...YS },
                    y2: {
                        position: 'right',
                        border: { display: false },
                        grid: { display: false },
                        ticks: { color: '#BA7517', font: { size: 10 }, callback: v => v + '%' },
                        max: 110
                    }
                }
            }
        }));

        /* 7 · Ciudad — Horizontal Bar (escala de color proporcional) */
        @php
            $ciudadLabels = $porCiudad->pluck('ciudad')->toArray();
            $ciudadValues = $porCiudad->pluck('total')->toArray();
        @endphp
        (function () {
            const vals   = {!! json_encode($ciudadValues) !!};
            const maxVal = vals[0] || 1;
            function lerpHex(a, b, t) {
                const ah = parseInt(a, 16), bh = parseInt(b, 16);
                const mix = c => Math.round(((ah >> c) & 0xff) * (1 - t) + ((bh >> c) & 0xff) * t);
                return [16, 8, 0].map(mix).map(x => x.toString(16).padStart(2, '0')).join('');
            }
            const bgColors     = vals.map(v => '#' + lerpHex('e6f1fb', '185fa5', v / maxVal) + 'cc');
            const borderColors = vals.map(v => '#' + lerpHex('b0c8e4', '0d3a6e', v / maxVal));

            window.__chartCiudad = new Chart(document.getElementById('chartCiudad'), {
                type: 'bar',
                data: {
                    labels: {!! json_encode($ciudadLabels) !!},
                    datasets: [{
                        data: vals,
                        backgroundColor: bgColors,
                        borderColor: borderColors,
                        borderWidth: 0.75,
                        borderRadius: 5,
                        borderSkipped: false
                    }]
                },
                options: {
                    ...BASE,
                    indexAxis: 'y',
                    plugins: { legend: { display: false }, tooltip: { ...TT(), callbacks: { label: c => ` ${c.parsed.x} pacientes` } } },
                    scales: {
                        x: { grid: { color: gc }, border: { display: false }, ticks: { color: tc, precision: 0, font: { size: 10 } } },
                        y: { grid: { display: false }, border: { display: false }, ticks: { color: tc, font: { size: 10 } } }
                    }
                }
            });
            charts.push(window.__chartCiudad);
        })();

    }

    /* Boot */
    document.addEventListener('DOMContentLoaded', initCharts);
    document.addEventListener('livewire:navigated', initCharts);

})();
</script>
</div>
