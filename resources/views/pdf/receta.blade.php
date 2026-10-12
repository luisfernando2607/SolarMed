<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta {{ $receta->id }}</title>
    <style>
        @page { margin: 12mm 12mm 14mm 12mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #222; }
        .header { border-bottom: 2px solid #059669; padding-bottom: 8px; margin-bottom: 12px; }
        .header table td { vertical-align: middle; }
        .medico { font-size: 14px; font-weight: bold; color: #065f46; }
        .muted { color: #666; font-size: 10px; }
        .titulo { text-align: center; font-size: 15px; letter-spacing: 3px; color: #059669; margin: 6px 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        .paciente td { padding: 2px 4px; }
        .label { font-weight: bold; color: #555; width: 22%; }
        .rx { font-size: 26px; font-weight: bold; color: #059669; margin: 10px 0 4px; }
        .med { border-bottom: 1px dashed #bbb; padding: 7px 0; }
        .med-nombre { font-size: 12px; font-weight: bold; }
        .med-detalle { margin-top: 2px; }
        .obs { background: #ecfdf5; padding: 8px; border-radius: 4px; margin-top: 12px; }
        .firma { margin-top: 55px; text-align: center; }
        .firma .linea { border-top: 1px solid #333; width: 60%; margin: 0 auto 4px; }
        .footer { position: fixed; bottom: -6mm; left: 0; right: 0; text-align: center; font-size: 9px; color: #888; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td style="width: 70px;"><img src="{{ $marca->logoRuta() }}" alt="" style="height: 48px;"></td>
                <td>
                    <div class="medico">{{ $receta->medico?->nombre_completo ?? '--' }}</div>
                    <div class="muted">{{ $receta->medico?->especialidad?->nombre }}</div>
                    @if ($receta->medico?->colegiatura)
                        <div class="muted">Reg. profesional: {{ $receta->medico->colegiatura }}</div>
                    @endif
                    @if ($receta->medico?->telefono)
                        <div class="muted">Tel: {{ $receta->medico->telefono }}</div>
                    @endif
                </td>
                <td style="text-align: right;" class="muted">
                    {{ $marca->nombre() }}<br>
                    @if ($marca->telefono())Tel: {{ $marca->telefono() }}<br>@endif
                    Fecha: {{ $receta->fecha->format('d/m/Y') }}<br>
                    Receta N.º {{ str_pad($receta->id, 6, '0', STR_PAD_LEFT) }}
                </td>
            </tr>
        </table>
    </div>

    <table class="paciente">
        <tr>
            <td class="label">Paciente:</td><td>{{ $receta->paciente->nombre_completo }}</td>
            <td class="label">Cédula:</td><td>{{ $receta->paciente->cedula }}</td>
        </tr>
        <tr>
            <td class="label">Edad:</td><td>{{ $receta->paciente->edad !== null ? $receta->paciente->edad . ' años' : '--' }}</td>
            <td class="label">Alergias:</td><td>@if ($receta->paciente->alergias)<strong style="color:#b91c1c;">{{ $receta->paciente->alergias }}</strong>@else Ninguna registrada @endif</td>
        </tr>
        @if ($receta->consulta?->diagnostico)
            <tr>
                <td class="label">Diagnóstico:</td>
                <td colspan="3">{{ $receta->consulta->diagnostico }}@if ($receta->consulta->codigo_cie10) (CIE-10 {{ $receta->consulta->codigo_cie10 }})@endif</td>
            </tr>
        @endif
    </table>

    <div class="rx">Rp/</div>

    @foreach ($receta->medicamentos as $i => $med)
        <div class="med">
            <div class="med-nombre">{{ $i + 1 }}. {{ $med['medicamento'] ?? '' }}@if (!empty($med['presentacion'])) — {{ $med['presentacion'] }}@endif</div>
            <div class="med-detalle">
                @if (!empty($med['dosis'])) <strong>Dosis:</strong> {{ $med['dosis'] }} &nbsp; @endif
                @if (!empty($med['frecuencia'])) <strong>Frecuencia:</strong> {{ $med['frecuencia'] }} &nbsp; @endif
                @if (!empty($med['duracion'])) <strong>Duración:</strong> {{ $med['duracion'] }} @endif
            </div>
            @if (!empty($med['indicaciones']))
                <div class="med-detalle"><em>{{ $med['indicaciones'] }}</em></div>
            @endif
        </div>
    @endforeach

    @if ($receta->observaciones)
        <div class="obs"><strong>Indicaciones generales:</strong> {{ $receta->observaciones }}</div>
    @endif

    <div class="firma">
        <div class="linea"></div>
        {{ $receta->medico?->nombre_completo }}<br>
        <span class="muted">Firma y sello</span>
    </div>

    <div class="footer">Documento generado el {{ now()->format('d/m/Y H:i') }} · {{ $marca->nombre() }}@if ($marca->direccion()) · {{ $marca->direccion() }}@endif</div>
</body>
</html>
