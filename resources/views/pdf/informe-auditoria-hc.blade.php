<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe de auditoría {{ $a->numero() }}</title>
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
        .label { font-weight: bold; color: #555; width: 20%; }
        h2 { font-size: 10px; text-transform: uppercase; color: {{ $marca->color() }}; border-bottom: 1px solid #ddd; margin: 10px 0 4px; padding-bottom: 2px; }
        table.criterios { border-collapse: collapse; width: 100%; }
        table.criterios th, table.criterios td { border: 1px solid #ddd; padding: 3px 4px; vertical-align: top; }
        table.criterios th { background: #f5f5f5; text-align: left; font-size: 9px; }
        .si { color: #047857; font-weight: bold; }
        .no { color: #b91c1c; font-weight: bold; }
        .puntaje { font-size: 16px; font-weight: bold; }
        .box { background: #f5f5f5; padding: 6px 8px; border-radius: 3px; white-space: pre-line; }
        .firma { margin-top: 40px; width: 45%; border-top: 1px solid #333; text-align: center; padding-top: 3px; }
    </style>
</head>
<body>
    {{-- AM 5216-A, Disposición General Primera: leyenda «confidencial» en el margen superior --}}
    <div class="confidencial">CONFIDENCIAL</div>
    <table class="header" width="100%">
        <tr>
            <td style="width: 65px;"><img src="{{ $marca->logoRuta() }}" alt="" style="max-height: 45px; max-width: 60px;"></td>
            <td>
                <div class="clinica">{{ $marca->nombre() }}</div>
                @if ($marca->contacto())<div class="muted">{{ $marca->contacto() }}</div>@endif
            </td>
            <td style="text-align: right;" class="muted">Informe N.º {{ $a->numero() }}<br>Fecha: {{ $a->cerrada_at?->format('d/m/Y H:i') ?? $a->fecha->format('d/m/Y') }}</td>
        </tr>
    </table>

    <h1>INFORME DE AUDITORÍA DE LA HISTORIA CLÍNICA</h1>
    <div class="sub">Calidad del llenado — formulario SNS-MSP/HCU-form.002/2021 · AM 00115-2021</div>

    <table class="datos" width="100%">
        <tr><td class="label">Historia clínica:</td><td>{{ $c->paciente?->cedula }}</td><td class="label">Fecha de atención:</td><td>{{ $c->fecha->format('d/m/Y H:i') }}</td></tr>
        <tr><td class="label">Paciente:</td><td>{{ $c->paciente?->nombre_completo }}</td><td class="label">Especialidad:</td><td>{{ $c->especialidad?->nombre }}</td></tr>
        <tr><td class="label">Profesional auditado:</td><td>{{ $c->medico?->nombre_completo }}</td><td class="label">Auditor:</td><td>{{ $a->auditor?->name }}</td></tr>
        <tr><td class="label">Puntaje:</td><td><span class="puntaje">{{ $a->puntaje }}%</span></td><td class="label">Evaluación automática:</td><td>{{ $a->puntaje_automatico }}%</td></tr>
    </table>

    <h2>Verificación por bloque</h2>
    <table class="criterios">
        <thead><tr><th style="width: 4%;">Bloque</th><th style="width: 30%;">Criterio</th><th style="width: 10%;">Cumple</th><th>Observación</th></tr></thead>
        <tbody>
            @foreach (\App\Services\CalidadHistoriaService::CRITERIOS as $clave => [$titulo, $detalle, $peso])
                @php $r = $a->resultado[$clave] ?? ['ok' => false, 'obs' => '']; @endphp
                <tr>
                    <td>{{ $clave }}</td>
                    <td>{{ $titulo }}<br><span class="muted">{{ $detalle }}</span></td>
                    <td class="{{ $r['ok'] ? 'si' : 'no' }}">{{ $r['ok'] ? 'Sí' : 'No' }}</td>
                    <td>{{ $r['obs'] ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($a->observacion)
        <h2>Observación general</h2>
        <div class="box">{{ $a->observacion }}</div>
    @endif

    <h2>Planes de acción</h2>
    @forelse ($a->planes as $plan)
        <div class="box" style="margin-bottom: 4px;"><strong>{{ $plan->accion }}</strong>
Hallazgo: {{ $plan->hallazgo }}
Responsable: {{ $plan->responsable?->name ?? '—' }} · Fecha límite: {{ $plan->fecha_limite->format('d/m/Y') }} · Estado: {{ \App\Models\PlanAccion::ESTADOS[$plan->estado] }}</div>
    @empty
        <p class="muted">Sin planes de acción registrados.</p>
    @endforelse

    <div class="firma">{{ $a->auditor?->name }}<br><span class="muted">Auditor</span></div>
</body>
</html>
