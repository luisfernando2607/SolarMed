<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pedido de exámenes {{ $p->numero() }}</title>
    <style>
        @page { margin: 12mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #222; }
        .header { border-bottom: 2px solid {{ $marca->color() }}; padding-bottom: 6px; margin-bottom: 10px; }
        .header td { vertical-align: middle; }
        .clinica { font-size: 13px; font-weight: bold; color: {{ $marca->color() }}; }
        .muted { color: #666; font-size: 9px; }
        h1 { font-size: 13px; letter-spacing: 2px; text-align: center; margin: 6px 0 10px; }
        .datos td { padding: 1px 3px; }
        .label { font-weight: bold; color: #555; width: 22%; }
        h2 { font-size: 10px; text-transform: uppercase; color: {{ $marca->color() }}; border-bottom: 1px solid #ddd; margin: 10px 0 4px; padding-bottom: 2px; }
        ul { margin: 0; padding-left: 14px; columns: 2; }
        li { margin-bottom: 2px; }
        .box { background: #f5f5f5; padding: 6px 8px; border-radius: 3px; white-space: pre-line; }
    </style>
</head>
<body>
    <table class="header" width="100%">
        <tr>
            <td style="width: 65px;"><img src="{{ $marca->logoRuta() }}" alt="" style="max-height: 45px; max-width: 60px;"></td>
            <td>
                <div class="clinica">{{ $marca->nombre() }}</div>
                @if ($marca->contacto())<div class="muted">{{ $marca->contacto() }}</div>@endif
            </td>
            <td style="text-align: right;" class="muted">Pedido N.º {{ $p->numero() }}<br>Fecha: {{ $p->fecha->format('d/m/Y') }}</td>
        </tr>
    </table>

    <h1>PEDIDO DE EXÁMENES</h1>

    <table class="datos" width="100%">
        <tr><td class="label">Paciente:</td><td>{{ $p->paciente->nombre_completo }}</td><td class="label">Cédula:</td><td>{{ $p->paciente->cedula }}</td></tr>
        <tr><td class="label">Edad:</td><td>{{ $p->paciente->edad !== null ? $p->paciente->edad . ' años' : '--' }}</td><td class="label">Sexo:</td><td>{{ ucfirst((string) $p->paciente->sexo) }}</td></tr>
        @if ($p->diagnostico)
            <tr><td class="label">Diagnóstico presuntivo:</td><td colspan="3">{{ $p->diagnostico }}@if ($p->codigo_cie10) (CIE-10 {{ $p->codigo_cie10 }})@endif</td></tr>
        @endif
    </table>

    @if (!empty($p->laboratorio))
        <h2>Laboratorio</h2>
        <ul>@foreach ($p->laboratorio as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif
    @if (!empty($p->imagen))
        <h2>Imagen y procedimientos</h2>
        <ul>@foreach ($p->imagen as $e)<li>{{ $e }}</li>@endforeach</ul>
    @endif
    @if ($p->otros)
        <h2>Otros</h2>
        <div class="box">{{ $p->otros }}</div>
    @endif
    @if ($p->indicaciones)
        <h2>Indicaciones</h2>
        <div class="box">{{ $p->indicaciones }}</div>
    @endif

    @include('pdf.partials.firma', ['medico' => $p->medico])
</body>
</html>
