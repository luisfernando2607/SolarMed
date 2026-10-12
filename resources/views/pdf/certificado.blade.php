<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificado médico {{ $c->numero() }}</title>
    <style>
        @page { margin: 18mm 20mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #222; line-height: 1.6; }
        .header { border-bottom: 2px solid {{ $marca->color() }}; padding-bottom: 8px; margin-bottom: 18px; }
        .header td { vertical-align: middle; }
        .clinica { font-size: 15px; font-weight: bold; color: {{ $marca->color() }}; }
        .muted { color: #666; font-size: 10px; }
        h1 { text-align: center; font-size: 17px; letter-spacing: 3px; margin: 10px 0 4px; }
        .numero { text-align: center; font-size: 10px; color: #666; margin-bottom: 18px; }
        p { text-align: justify; margin: 0 0 10px; }
        .verificacion { margin-top: 30px; border-top: 1px dashed #bbb; padding-top: 8px; font-size: 9px; color: #555; }
    </style>
</head>
<body>
    @php $p = $c->paciente; @endphp
    <table class="header" width="100%">
        <tr>
            <td style="width: 80px;"><img src="{{ $marca->logoRuta() }}" alt="" style="max-height: 55px; max-width: 75px;"></td>
            <td>
                <div class="clinica">{{ $marca->nombre() }}</div>
                @if ($marca->subtitulo())<div class="muted">{{ $marca->subtitulo() }}</div>@endif
                @if ($marca->contacto())<div class="muted">{{ $marca->contacto() }}</div>@endif
            </td>
        </tr>
    </table>

    <h1>CERTIFICADO MÉDICO</h1>
    <div class="numero">N.º {{ $c->numero() }} · {{ $c->tipo === 'reposo' ? 'Reposo' : 'Atención médica' }}</div>

    <p>
        Certifico que <strong>{{ $p->nombre_completo }}</strong>, con cédula de identidad N.º <strong>{{ $p->cedula }}</strong>{{ $p->edad !== null ? ', de ' . $p->edad . ' años de edad' : '' }},
        fue atendido(a) en consulta médica el día <strong>{{ $c->fecha_atencion->translatedFormat('d \d\e F \d\e Y') }}</strong>@if ($c->hora_atencion) a las {{ $c->hora_atencion }}@endif.
    </p>

    @if ($c->mostrar_diagnostico && $c->diagnostico)
        <p>Diagnóstico: <strong>{{ $c->diagnostico }}</strong>@if ($c->codigo_cie10) (CIE-10: {{ $c->codigo_cie10 }})@endif.</p>
    @endif

    @if ($c->tipo === 'reposo')
        <p>
            Por lo expuesto, se indica <strong>reposo médico de {{ $c->dias_reposo }} {{ $c->dias_reposo === 1 ? 'día' : 'días' }}</strong>,
            desde el <strong>{{ $c->reposo_desde->translatedFormat('d \d\e F \d\e Y') }}</strong>
            hasta el <strong>{{ $c->reposo_hasta->translatedFormat('d \d\e F \d\e Y') }}</strong> inclusive.
        </p>
    @endif

    @if ($c->observaciones)
        <p>Observaciones: {{ $c->observaciones }}</p>
    @endif

    <p>Se extiende el presente certificado a petición del interesado(a), para los fines que estime convenientes.</p>
    <p style="text-align: right;">{{ $c->fecha_emision->translatedFormat('d \d\e F \d\e Y') }}</p>

    @include('pdf.partials.firma', ['medico' => $c->medico])

    <table class="verificacion" width="100%">
        <tr>
            <td style="width: 90px;"><img src="data:image/svg+xml;base64,{{ $qr }}" style="width: 80px; height: 80px;"></td>
            <td>
                Verifique la autenticidad de este certificado escaneando el código QR o en:<br>
                <strong>{{ $c->urlVerificacion() }}</strong><br>
                Código de verificación: <strong>{{ $c->codigo_verificacion }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
