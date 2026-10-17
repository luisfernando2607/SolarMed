<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe de calidad de la historia clínica</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #222; }
        .confidencial { text-align: center; font-weight: bold; letter-spacing: 3px; color: #b91c1c; border: 1px solid #b91c1c; padding: 2px; margin-bottom: 6px; font-size: 10px; }
        .header { border-bottom: 2px solid {{ $marca->color() }}; padding-bottom: 6px; margin-bottom: 10px; }
        .header td { vertical-align: middle; }
        .clinica { font-size: 13px; font-weight: bold; color: {{ $marca->color() }}; }
        .muted { color: #666; font-size: 9px; }
        h1 { font-size: 13px; letter-spacing: 1px; text-align: center; margin: 6px 0 2px; }
        .sub { text-align: center; color: #666; font-size: 9px; margin-bottom: 10px; }
        .datos td { padding: 1px 3px; }
        .label { font-weight: bold; color: #555; }
        h2 { font-size: 10px; text-transform: uppercase; color: {{ $marca->color() }}; border-bottom: 1px solid #ddd; margin: 12px 0 4px; padding-bottom: 2px; }
        table.t { border-collapse: collapse; width: 100%; }
        table.t th, table.t td { border: 1px solid #ddd; padding: 3px 5px; vertical-align: top; }
        table.t th { background: #f5f5f5; text-align: left; font-size: 9px; }
        .num { text-align: right; }
        .center { text-align: center; }
        .good { color: #047857; font-weight: bold; }
        .mid { color: #b45309; font-weight: bold; }
        .bad { color: #b91c1c; font-weight: bold; }
        .resumen td { border: 1px solid #ddd; padding: 6px 8px; text-align: center; width: 25%; }
        .resumen .big { font-size: 18px; font-weight: bold; }
        .barra { background: #eee; height: 7px; border-radius: 4px; overflow: hidden; }
        .barra span { display: block; height: 7px; background: {{ $marca->color() }}; }
        .firma { margin-top: 42px; width: 46%; border-top: 1px solid #333; text-align: center; padding-top: 3px; font-size: 9px; }
        .nota { color: #888; font-size: 8px; margin-top: 10px; }
    </style>
</head>
<body>
    @php
        $est = $marca->establecimiento();
        $ubic = collect([$est['provincia'] ?? '', $est['canton'] ?? '', $est['parroquia'] ?? ''])->filter()->implode(' · ');
        $tono = fn ($p) => $p >= \App\Services\CalidadHistoriaService::UMBRAL_BUENO ? 'good' : ($p >= \App\Services\CalidadHistoriaService::UMBRAL_ACEPTABLE ? 'mid' : 'bad');
    @endphp

    <div class="confidencial">CONFIDENCIAL</div>
    <table class="header" width="100%">
        <tr>
            <td style="width: 65px;"><img src="{{ $marca->logoRuta() }}" alt="" style="max-height: 45px; max-width: 60px;"></td>
            <td>
                <div class="clinica">{{ $marca->nombre() }}</div>
                @if ($marca->contacto())<div class="muted">{{ $marca->contacto() }}</div>@endif
            </td>
            <td style="text-align: right;" class="muted">Emitido: {{ now()->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <h1>INFORME DE AUDITORÍA DE CALIDAD DE LA HISTORIA CLÍNICA</h1>
    <div class="sub">Consulta externa — formulario SNS-MSP/HCU-form.002/2021 · Reglamento de la HCU (AM 00115-2021)</div>

    <h2>Establecimiento y período</h2>
    <table class="datos" width="100%">
        <tr>
            <td class="label" style="width: 18%;">Institución:</td><td style="width: 32%;">{{ $est['institucion'] ?: '—' }}</td>
            <td class="label" style="width: 18%;">Unicódigo:</td><td>{{ $est['unicodigo'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tipología:</td><td>{{ $est['tipologia'] ?: '—' }}</td>
            <td class="label">Ubicación:</td><td>{{ $ubic ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Período auditado:</td><td>{{ $desde->format('d/m/Y') }} – {{ $hasta->format('d/m/Y') }}</td>
            <td class="label">Auditor / responsable:</td><td>{{ auth()->user()->name }}</td>
        </tr>
        <tr>
            <td class="label">Profesional:</td><td>{{ $medico?->nombre_completo ?? 'Todos' }}</td>
            <td class="label">Especialidad:</td><td>{{ $especialidad?->nombre ?? 'Todas' }}</td>
        </tr>
    </table>

    <h2>Resumen del período</h2>
    @if ($n === 0)
        <p class="muted">No se registraron consultas de consulta externa en el período y los filtros seleccionados.</p>
    @else
    <table class="resumen" width="100%">
        <tr>
            <td><div class="muted">Consultas evaluadas</div><div class="big">{{ $n }}</div></td>
            <td><div class="muted">Cumplimiento promedio</div><div class="big {{ $tono($promedio) }}">{{ $promedio }}%</div></td>
            <td><div class="muted">Historias con calidad buena (≥ {{ \App\Services\CalidadHistoriaService::UMBRAL_BUENO }}%)</div><div class="big good">{{ $distribucion['bueno'] }}</div><div class="muted">{{ (int) round($distribucion['bueno'] / $n * 100) }}%</div></td>
            <td><div class="muted">Historias deficientes (&lt; {{ \App\Services\CalidadHistoriaService::UMBRAL_ACEPTABLE }}%)</div><div class="big bad">{{ $distribucion['deficiente'] }}</div><div class="muted">{{ (int) round($distribucion['deficiente'] / $n * 100) }}%</div></td>
        </tr>
    </table>
    <p class="muted" style="margin-top:4px;">
        Distribución: {{ $distribucion['bueno'] }} buena · {{ $distribucion['aceptable'] }} aceptable (≥ {{ \App\Services\CalidadHistoriaService::UMBRAL_ACEPTABLE }}%) · {{ $distribucion['deficiente'] }} deficiente.
    </p>

    <h2>Cumplimiento por bloque del formulario 002</h2>
    <table class="t">
        <thead><tr><th style="width:5%;">Bloque</th><th style="width:40%;">Criterio</th><th style="width:40%;">Cumplimiento</th><th style="width:15%;" class="center">%</th></tr></thead>
        <tbody>
            @foreach ($porCriterio as $c)
                <tr>
                    <td class="center">{{ $c['clave'] }}</td>
                    <td>{{ $c['titulo'] }}</td>
                    <td><div class="barra"><span style="width: {{ $c['pct'] }}%;"></span></div></td>
                    <td class="center {{ $tono($c['pct']) }}">{{ $c['pct'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Cumplimiento por profesional</h2>
    <table class="t">
        <thead><tr><th>Profesional</th><th class="center" style="width:18%;">Consultas</th><th class="center" style="width:18%;">Cumplimiento</th></tr></thead>
        <tbody>
            @foreach ($porMedico as $m)
                <tr>
                    <td>{{ $m['medico'] }}</td>
                    <td class="center">{{ $m['consultas'] }}</td>
                    <td class="center {{ $tono($m['pct']) }}">{{ $m['pct'] }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Auditorías manuales cerradas en el período</h2>
    @if ($auditadas->isEmpty())
        <p class="muted">No se cerraron auditorías manuales de historias en este período. El cumplimiento mostrado proviene de la evaluación automática de los criterios del formulario 002.</p>
    @else
    <table class="t">
        <thead><tr><th style="width:14%;">Fecha</th><th>Paciente (N.º HC)</th><th>Profesional</th><th class="center" style="width:12%;">Puntaje</th><th class="center" style="width:14%;">Planes</th></tr></thead>
        <tbody>
            @foreach ($auditadas as $a)
                <tr>
                    <td>{{ $a->cerrada_at?->format('d/m/Y') ?? $a->fecha->format('d/m/Y') }}</td>
                    <td>{{ $a->consulta?->paciente?->cedula ?? '—' }}</td>
                    <td>{{ $a->consulta?->medico?->nombre_completo ?? '—' }}</td>
                    <td class="center {{ $tono($a->puntaje) }}">{{ $a->puntaje }}%</td>
                    <td class="center">{{ $a->planes()->count() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif
    @endif

    @if ($limitado)
        <p class="nota">Se evaluaron las 2000 consultas más recientes del período; para un volumen mayor, acote el rango de fechas.</p>
    @endif
    <p class="nota">Documento generado por {{ $marca->nombre() }}. Los puntajes automáticos verifican la presencia de cada bloque del formulario 002; la auditoría manual confirma su calidad. Base normativa: AM 00115-2021 (HCU) y AM 5216-A (confidencialidad y auditoría).</p>

    <table width="100%"><tr>
        <td><div class="firma">Auditor / responsable de calidad<br>{{ auth()->user()->name }}</div></td>
        <td style="text-align:right;"><div class="firma" style="margin-left:auto;">Director técnico / médico</div></td>
    </tr></table>
</body>
</html>
