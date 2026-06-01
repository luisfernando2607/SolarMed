<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->numero_factura }}</title>
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
    <div class="encabezado center">
        <h1>{{ config('app.name', 'CLÍNICA') }}</h1>
        <p>RUC: {{ $factura->medico?->user?->cedula ?? config('app.ruc', '1234567890001') }}</p>
        <p>Tel: {{ config('app.telefono', '044619253') }}</p>
        <p>Ecuador</p>
        <div class="double-line"></div>
        <h2 style="font-size:12px; margin:4px 0;">FACTURA</h2>
        <p style="font-size:11px; font-weight:bold;">{{ $factura->numero_factura }}</p>
        <p>Fecha: {{ $factura->fecha ? $factura->fecha->format('d/m/Y') : $factura->created_at->format('d/m/Y') }}</p>
        <div class="double-line"></div>
    </div>

    <table>
        <tr><td class="bold">Paciente:</td><td>{{ $factura->paciente?->nombre_completo ?? '--' }}</td></tr>
        <tr><td class="bold">Cédula:</td><td>{{ $factura->paciente?->cedula ?? '--' }}</td></tr>
        @if ($factura->paciente?->telefono)
            <tr><td class="bold">Tel:</td><td>{{ $factura->paciente->telefono }}</td></tr>
        @endif
        <tr><td class="bold">Atendió:</td><td>{{ $factura->medico?->nombre_completo ?? '--' }}</td></tr>
        <tr><td class="bold">Pago:</td><td class="capitalize">{{ ucfirst($factura->forma_pago) }}</td></tr>
    </table>

    <div class="line"></div>

    <table>
        <thead>
            <tr style="font-weight:bold;">
                <th class="item-desc">Descripción</th>
                <th class="item-qty">Cant</th>
                <th class="item-price">P/U</th>
                <th class="item-total">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($factura->items as $item)
                <tr>
                    <td class="item-desc">{{ $item->descripcion }}</td>
                    <td class="item-qty">{{ $item->cantidad }}</td>
                    <td class="item-price">${{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="item-total">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table class="totales">
        <tr><td style="width:70%;"></td><td class="right">Subtotal:</td><td class="right" style="width:25%;">${{ number_format($factura->subtotal, 2) }}</td></tr>
        @if ($factura->descuento > 0)
            <tr><td></td><td class="right">Descuento:</td><td class="right">-${{ number_format($factura->descuento, 2) }}</td></tr>
        @endif
        <tr style="font-weight:bold; font-size:12px;">
            <td></td><td class="right">TOTAL:</td><td class="right">${{ number_format($factura->total, 2) }}</td></tr>
    </table>

    @if ($factura->observaciones)
        <div class="line"></div>
        <p style="font-size:9px;"><strong>Obs:</strong> {{ $factura->observaciones }}</p>
    @endif

    <div class="double-line"></div>

    <div class="footer">
        <p>¡Gracias por su preferencia!</p>
        <p>{{ $factura->numero_factura }} | {{ $factura->fecha ? $factura->fecha->format('d/m/Y') : $factura->created_at->format('Y-m-d H:i') }}</p>
    </div>
</body>
</html>
