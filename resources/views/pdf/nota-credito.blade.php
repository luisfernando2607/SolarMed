<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota de crédito {{ $nota->numero }}</title>
    <style>
        @page { margin: 8mm; size: 80mm auto; }
        body { font-family: 'Courier New', monospace; font-size: 10px; color: #000; margin: 0; padding: 0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 6px 0; }
        .double-line { border-top: 3px double #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; }
        .encabezado { margin-bottom: 10px; }
        .encabezado h1 { font-size: 14px; margin: 0; }
        .encabezado p { margin: 2px 0; font-size: 9px; }
        .item-desc { padding-left: 4px; }
        .item-qty { text-align: center; width: 10%; }
        .item-price { text-align: right; width: 25%; }
        .item-total { text-align: right; width: 25%; }
        .totales td { padding: 2px 4px; }
        .footer { margin-top: 10px; font-size: 8px; text-align: center; }
    </style>
</head>
<body>
    @php $factura = $nota->factura; @endphp
    <div class="encabezado center">
        <img src="{{ public_path('images/SolarMed-bn.jpg') }}" alt="Solar Med" style="height: 50px; margin-bottom: 4px;">
        <h1>{{ $emisor['nombre_comercial'] ?: config('app.name', 'CLÍNICA') }}</h1>
        @if ($emisor['razon_social'])
            <p>{{ $emisor['razon_social'] }}</p>
        @endif
        <p>RUC: {{ $nota->ruc_emisor ?: ($emisor['ruc'] ?: '--') }}</p>
        @if ($emisor['direccion'])
            <p>{{ $emisor['direccion'] }}</p>
        @endif
        @if ($emisor['regimen_rimpe'])
            <p>{{ $emisor['regimen_rimpe'] }}</p>
        @endif
        <p>Obligado a llevar contabilidad: {{ $emisor['obligado_contabilidad'] }}</p>
        <div class="double-line"></div>
        <h2 style="font-size:12px; margin:4px 0;">NOTA DE CRÉDITO</h2>
        <p style="font-size:11px; font-weight:bold;">{{ $nota->numero }}</p>
        <p>Fecha: {{ $nota->fecha->format('d/m/Y') }}</p>
        <div class="double-line"></div>
    </div>

    <table>
        <tr><td class="bold">Cliente:</td><td>{{ $factura->paciente?->nombre_completo ?? 'CONSUMIDOR FINAL' }}</td></tr>
        <tr><td class="bold">Identificación:</td><td>{{ $factura->paciente?->cedula ?: '9999999999999' }}</td></tr>
        <tr><td class="bold">Doc. modificado:</td><td>FACTURA {{ $factura->numero_factura }}</td></tr>
        <tr><td class="bold">Fecha factura:</td><td>{{ ($factura->fecha ?? $factura->created_at)->format('d/m/Y') }}</td></tr>
        <tr><td class="bold">Motivo:</td><td>{{ $nota->motivo }}</td></tr>
    </table>

    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th class="item-desc">Descripción</th>
                <th class="item-qty">Cant</th>
                <th class="item-total">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($factura->items as $item)
                <tr>
                    <td class="item-desc">{{ $item->descripcion }}</td>
                    <td class="item-qty">{{ $item->cantidad }}</td>
                    <td class="item-total">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table class="totales">
        @if ($factura->descuento > 0)
            <tr><td style="width:70%;"></td><td class="right">Descuento:</td><td class="right">-${{ number_format($factura->descuento, 2) }}</td></tr>
        @endif
        <tr><td style="width:70%;"></td><td class="right">IVA 0%:</td><td class="right">$0.00</td></tr>
        <tr class="bold"><td></td><td class="right">VALOR:</td><td class="right">${{ number_format($nota->valor_modificacion, 2) }}</td></tr>
    </table>

    @if ($nota->clave_acceso && in_array($nota->estado_sri, ['enviado', 'autorizado'], true))
        <div class="line"></div>
        <div style="font-size:8px;">
            <p><strong>{{ $nota->estado_sri === 'autorizado' ? 'AUTORIZACIÓN SRI' : 'CLAVE DE ACCESO (en proceso de autorización)' }}</strong></p>
            <p style="word-break:break-all; font-family:monospace;">{{ $nota->numero_autorizacion ?: $nota->clave_acceso }}</p>
            @if ($nota->fecha_autorizacion)
                <p>Fecha autorización: {{ $nota->fecha_autorizacion->format('d/m/Y H:i:s') }}</p>
            @endif
            <p>Ambiente: {{ $nota->ambiente_sri === '2' ? 'PRODUCCIÓN' : 'PRUEBAS' }} · Emisión: NORMAL</p>
        </div>
    @endif

    <div class="double-line"></div>
    <div class="footer">
        <p>{{ $nota->numero }} | {{ $nota->fecha->format('d/m/Y') }}</p>
    </div>
</body>
</html>
