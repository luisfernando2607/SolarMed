// Gráficos de evolución de la ficha del paciente (pestaña "Evolución").
// Lee los datos de <script type="application/json" id="evolucion-datos"> y dibuja un gráfico por medida,
// cada uno con un solo eje. Se inicia una vez por página (también tras wire:navigate) y destruye
// los gráficos anteriores para no dejar instancias sueltas.

import Chart from 'chart.js/auto';

const RANGOS = { '6m': 182, '1a': 365, todo: null };
let graficos = [];
let rango = 'todo';

const fechaCorta = (ms) => new Date(ms).toLocaleDateString('es-EC', { day: '2-digit', month: 'short', year: '2-digit', timeZone: 'America/Guayaquil' });

function alfa(hex, a) {
    const n = parseInt(hex.slice(1), 16);
    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${a})`;
}

// Franjas de referencia (rango habitual) dibujadas detrás de las líneas
const franjas = {
    id: 'franjasReferencia',
    beforeDatasetsDraw(chart, _args, opciones) {
        const { ctx, chartArea: area, scales: { x, y } } = chart;
        if (!area) return;
        ctx.save();
        for (const [min, max, color] of opciones.bandas || []) {
            const arriba = Math.max(y.getPixelForValue(max), area.top);
            const abajo = Math.min(y.getPixelForValue(min), area.bottom);
            if (abajo <= area.top || arriba >= area.bottom) continue;
            ctx.fillStyle = alfa(color, 0.07);
            ctx.fillRect(area.left, arriba, area.right - area.left, abajo - arriba);
        }
        // Altura uterina: semana ± tolerancia entre dos semanas (franja diagonal)
        const d = opciones.diagonal;
        if (d) {
            const px = (s, v) => [x.getPixelForValue(s), y.getPixelForValue(v)];
            const pts = [px(d.desde, d.desde + d.tolerancia), px(d.hasta, d.hasta + d.tolerancia), px(d.hasta, d.hasta - d.tolerancia), px(d.desde, d.desde - d.tolerancia)];
            ctx.beginPath();
            ctx.rect(area.left, area.top, area.right - area.left, area.bottom - area.top);
            ctx.clip();
            ctx.beginPath();
            pts.forEach(([px1, py1], i) => (i ? ctx.lineTo(px1, py1) : ctx.moveTo(px1, py1)));
            ctx.closePath();
            ctx.fillStyle = alfa('#6b6a65', 0.08);
            ctx.fill();
        }
        ctx.restore();
    },
};

function puntosEnRango(g, s) {
    const dias = RANGOS[rango];
    const desde = dias ? Date.now() - dias * 86400000 : null;
    return s.puntos
        .filter((p) => g.eje !== 'fecha' || desde === null || p.x >= desde)
        .map((p) => ({ x: g.eje === 'fecha' ? p.x : p.semana, y: p.valor, fecha: p.fecha, fuente: p.fuente }));
}

function dibujar() {
    graficos.forEach((c) => c.destroy());
    graficos = [];

    const fuente = document.getElementById('evolucion-datos');
    if (!fuente) return;
    const definiciones = JSON.parse(fuente.textContent || '[]');
    const contenedor = fuente.closest('[data-evolucion]');

    contenedor.querySelectorAll('[data-evolucion-rango]').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.evolucionRango === rango)));

    for (const g of definiciones) {
        const figura = contenedor.querySelector(`[data-grafico="${g.id}"]`);
        if (!figura) continue;
        const series = g.series.map((s) => ({ ...s, datos: puntosEnRango(g, s) }));
        const suficientes = series.some((s) => s.datos.length >= 2);

        figura.querySelector('[data-lienzo]').classList.toggle('hidden', !suficientes);
        const sinDatos = figura.querySelector('[data-sin-datos]');
        sinDatos.classList.toggle('hidden', suficientes);
        sinDatos.classList.toggle('flex', !suficientes);

        // La tabla muestra solo las filas del período elegido
        const dias = RANGOS[rango];
        figura.querySelectorAll('tr[data-x]').forEach((tr) => {
            tr.hidden = dias !== null && Number(tr.dataset.x) < Date.now() - dias * 86400000;
        });
        if (!suficientes) continue;

        const unaSerie = series.length === 1;
        // El eje termina en los datos: sin redondeos que muestren fechas futuras
        const valoresX = series.flatMap((s) => s.datos.map((d) => d.x));
        graficos.push(new Chart(figura.querySelector('canvas'), {
            type: 'line',
            data: {
                datasets: series.map((s) => ({
                    label: s.nombre,
                    data: s.datos,
                    borderColor: s.color,
                    backgroundColor: s.color,
                    borderWidth: 2,
                    cubicInterpolationMode: 'monotone',
                    pointRadius: s.datos.length > 40 ? 0 : 4,
                    pointHoverRadius: 6,
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'nearest', axis: 'x', intersect: false },
                plugins: {
                    legend: { display: false },
                    franjasReferencia: { bandas: g.bandas || [], diagonal: g.franjaDiagonal || null },
                    tooltip: {
                        backgroundColor: '#ffffff', titleColor: '#1f1f1d', bodyColor: '#4a4945', borderColor: '#e9e7e2', borderWidth: 1,
                        padding: 10, cornerRadius: 10, boxPadding: 4, usePointStyle: true,
                        callbacks: {
                            title: (items) => (g.eje === 'fecha' ? items[0].raw.fecha : `Semana ${items[0].raw.x} · ${items[0].raw.fecha}`),
                            label: (c) => ` ${unaSerie ? '' : c.dataset.label + ': '}${c.parsed.y.toLocaleString('es-EC')} ${g.unidad}`,
                            afterBody: (items) => items[0] ? `Fuente: ${items[0].raw.fuente}` : '',
                        },
                    },
                },
                scales: {
                    x: {
                        type: 'linear',
                        min: Math.min(...valoresX),
                        max: Math.max(...valoresX),
                        grid: { display: false },
                        border: { display: false },
                        ticks: { color: '#8a8984', maxTicksLimit: 5, maxRotation: 0, autoSkipPadding: 12, callback: (v) => (g.eje === 'fecha' ? fechaCorta(v) : `sem ${v}`) },
                    },
                    y: {
                        grid: { color: '#f0efea' },
                        border: { display: false },
                        ticks: { color: '#8a8984', maxTicksLimit: 5 },
                        grace: '10%',
                    },
                },
            },
            plugins: [franjas],
        }));
    }
}

window.__evolucionInit = dibujar;
if (!window.__evolucionRegistrado) {
    window.__evolucionRegistrado = true;
    document.addEventListener('DOMContentLoaded', () => window.__evolucionInit?.());
    document.addEventListener('livewire:navigated', () => {
        rango = 'todo';
        window.__evolucionInit?.();
    });
    // Al mostrar la pestaña los lienzos dejan de medir 0 px: se vuelven a dibujar con su tamaño real
    window.addEventListener('evolucion:visible', () => window.__evolucionInit?.());
    document.addEventListener('click', (e) => {
        const boton = e.target.closest('[data-evolucion-rango]');
        if (!boton) return;
        rango = boton.dataset.evolucionRango;
        window.__evolucionInit?.();
    });
}
// El módulo puede cargarse después de DOMContentLoaded (script diferido)
if (document.readyState !== 'loading') dibujar();
