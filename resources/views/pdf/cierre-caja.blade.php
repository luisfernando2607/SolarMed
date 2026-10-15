<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Acta de cierre de caja {{ $c->hasta->format('d/m/Y H:i') }}</title>
    <style>
        @page { margin: 16mm 18mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #222; line-height: 1.45; }
        .header { border-bottom: 2px solid {{ $marca->color() }}; padding-bottom: 8px; margin-bottom: 14px; }
        .header td { vertical-align: middle; }
        .clinica { font-size: 15px; font-weight: bold; color: {{ $marca->color() }}; }
        .muted { color: #666; font-size: 10px; }
        h1 { text-align: center; font-size: 16px; letter-spacing: 2px; margin: 6px 0 2px; }
        .sub { text-align: center; font-size: 10px; color: #666; margin-bottom: 14px; }
        table.datos { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.datos th, table.datos td { border: 1px solid #ddd; padding: 4px 6px; }
        table.datos th { background: #f3f4f6; text-align: left; font-size: 10px; }
        .num { text-align: right; }
        .total td { font-weight: bold; background: #fafafa; }
        .reabierto { border: 1px solid #f59e0b; background: #fffbeb; padding: 6px 8px; margin-bottom: 12px; }
        .firmas { width: 100%; margin-top: 50px; }
        .firmas td { width: 50%; text-align: center; padding: 0 20px; }
        .linea { border-top: 1px solid #333; padding-top: 4px; }
    </style>
</head>
<body>
    @php
        $formas = \App\Models\CierreCaja::FORMAS_PAGO;
        $d = fn ($v) => '$' . number_format((float) $v, 2);
    @endphp
    <table class="header" width="100%">
        <tr>
            <td style="width: 80px;"><img src="{{ $marca->logoRuta() }}" alt="" style="max-height: 55px; max-width: 75px;"></td>
            <td>
                <div class="clinica">{{ $marca->nombre() }}</div>
                @if ($marca->contacto())<div class="muted">{{ $marca->contacto() }}</div>@endif
            </td>
        </tr>
    </table>

    <h1>ACTA DE CIERRE DE CAJA</h1>
    <div class="sub">Del {{ $c->desde->format('d/m/Y H:i') }} al {{ $c->hasta->format('d/m/Y H:i') }} · Cierre N.º {{ $c->id }}</div>

    @if ($c->estado === 'reabierto')
        <div class="reabierto">Este cierre fue <strong>reabierto</strong> el {{ $c->reabierto_at?->format('d/m/Y H:i') }} por {{ $c->reabiertoPor?->name }}. Motivo: {{ $c->motivo_reapertura }}. Sus facturas se incluyeron en un cierre posterior.</div>
    @endif

    <table class="datos">
        <tr><th>Forma de pago</th><th class="num">Cobrado</th><th class="num">Devoluciones</th><th class="num">Neto</th></tr>
        @foreach ($formas as $clave => $texto)
            <tr>
                <td>{{ $texto }}</td>
                <td class="num">{{ $d($c->cobrado[$clave] ?? 0) }}</td>
                <td class="num">{{ $d($c->devoluciones[$clave] ?? 0) }}</td>
                <td class="num">{{ $d(($c->cobrado[$clave] ?? 0) - ($c->devoluciones[$clave] ?? 0)) }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td>Total</td>
            <td class="num">{{ $d($c->totalCobrado()) }}</td>
            <td class="num">{{ $d($c->notas_credito_total) }}</td>
            <td class="num">{{ $d($c->totalCobrado() - $c->notas_credito_total) }}</td>
        </tr>
    </table>

    <table class="datos">
        <tr><th>Cuadre del efectivo</th><th class="num">Valor</th></tr>
        <tr><td>Fondo inicial</td><td class="num">{{ $d($c->fondo_inicial) }}</td></tr>
        <tr><td>+ Efectivo cobrado − devoluciones en efectivo</td><td class="num">{{ $d($c->efectivo_esperado - $c->fondo_inicial) }}</td></tr>
        <tr class="total"><td>Efectivo esperado en caja</td><td class="num">{{ $d($c->efectivo_esperado) }}</td></tr>
        <tr><td>Efectivo contado</td><td class="num">{{ $d($c->contado_efectivo) }}</td></tr>
        <tr class="total"><td>{{ $c->diferencia == 0 ? 'Diferencia (cuadra)' : ($c->diferencia > 0 ? 'Sobrante' : 'Faltante') }}</td><td class="num">{{ $d(abs($c->diferencia)) }}</td></tr>
    </table>

    <p>Facturas cobradas: {{ $c->facturas_count }} · Anuladas en el período: {{ $c->anuladas_count }}.@if ($c->observaciones) Observaciones: {{ $c->observaciones }}@endif</p>

    @if (!empty($c->detalle['facturas']))
        <table class="datos">
            <tr><th>Fecha</th><th>Factura</th><th>Paciente</th><th>Forma</th><th class="num">Total</th></tr>
            @foreach ($c->detalle['facturas'] as $f)
                <tr>
                    <td>{{ $f['hora'] }}</td>
                    <td>{{ $f['numero'] }}{{ $f['estado'] === 'anulada' ? ' (anulada)' : '' }}</td>
                    <td>{{ $f['paciente'] }}</td>
                    <td>{{ $formas[$f['forma_pago']] ?? $f['forma_pago'] }}</td>
                    <td class="num">{{ $d($f['total']) }}</td>
                </tr>
            @endforeach
            @foreach ($c->detalle['notas'] ?? [] as $n)
                <tr><td></td><td>NC {{ $n['numero'] }}</td><td>Devolución de {{ $n['factura'] }}</td><td>{{ $formas[$n['forma_pago']] ?? '' }}</td><td class="num">− {{ $d($n['valor']) }}</td></tr>
            @endforeach
        </table>
    @endif

    <table class="firmas">
        <tr>
            <td><div class="linea">Entrega: {{ $c->user?->name }}</div></td>
            <td><div class="linea">Recibe</div></td>
        </tr>
    </table>
</body>
</html>
