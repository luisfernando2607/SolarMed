{{-- Receta médica según la Resolución ACESS-2023-0030, Art. 5. A4 horizontal: Receta | Indicaciones (desprendible) --}}
@php
    $p = $receta->paciente;
    $m = $receta->medico;
    $edad = \App\Models\Receta::edadTexto($p?->fecha_nacimiento, $receta->fecha);
    $validaHasta = $receta->validaHasta();
    $contacto = $m?->telefono ?: $marca->telefono();
    $establecimiento = $marca->nombre();
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receta N.º {{ $receta->numeroFormateado() }}</title>
    <style>
        @page { margin: 9mm 9mm 10mm 9mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        table { width: 100%; border-collapse: collapse; }
        .mitad { width: 50%; vertical-align: top; }
        .izq { padding-right: 9mm; border-right: 1px dashed #999; }
        .der { padding-left: 9mm; }
        .cab td { vertical-align: top; }
        .titulo { font-size: 13px; font-weight: bold; letter-spacing: 1px; }
        .muted { color: #666; }
        .num { font-size: 11px; font-weight: bold; text-align: right; }
        .caja { border: 1px solid #cfcfcf; border-radius: 3px; padding: 5px 6px; margin-top: 6px; }
        .lbl { color: #555; font-weight: bold; }
        .meds th { text-align: left; font-size: 8px; color: #555; border-bottom: 1px solid #999; padding: 3px 2px; }
        .meds td { border-bottom: 1px solid #e3e3e3; padding: 4px 2px; vertical-align: top; }
        .dci { font-weight: bold; font-size: 10px; }
        .alerta { color: #b91c1c; font-weight: bold; }
        .ind { border-bottom: 1px solid #e3e3e3; padding: 4px 0; }
        .bloque { margin-top: 7px; }
        .bloque h4 { margin: 0 0 2px; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: #444; }
        .pie { font-size: 8px; color: #666; margin-top: 4px; }
    </style>
</head>
<body>
<table>
    <tr>
        {{-- ===== RECETA ===== --}}
        <td class="mitad izq">
            @include('pdf.partials.confidencial')
            <table class="cab">
                <tr>
                    <td style="width: 48px;"><img src="{{ $marca->logoRuta() }}" alt="" style="height: 38px;"></td>
                    <td>
                        <div class="titulo">RECETA MÉDICA</div>
                        <div class="muted">{{ $establecimiento }}</div>
                    </td>
                    <td class="num">N.º {{ $receta->numeroFormateado() }}</td>
                </tr>
            </table>

            <div class="caja">
                <table>
                    <tr>
                        <td><span class="lbl">Ciudad y fecha:</span> {{ $receta->ciudad }}, {{ $receta->fecha->format('d/m/Y') }}</td>
                        <td style="text-align: right;"><span class="lbl">Válida hasta:</span> {{ $validaHasta?->format('d/m/Y') }} ({{ $receta->vigencia_dias }} días)</td>
                    </tr>
                </table>
            </div>

            <div class="caja">
                <div><span class="lbl">Paciente:</span> {{ $p?->apellidos }} {{ $p?->nombres }} &nbsp; <span class="lbl">C.I.:</span> {{ $p?->cedula }}</div>
                <div><span class="lbl">Edad:</span> {{ $edad ?? 'No registrada' }}</div>
                <div><span class="lbl">Diagnóstico (CIE-10 {{ $receta->codigo_cie10 }}):</span> {{ $receta->diagnostico }}</div>
                <div><span class="lbl">Alergias:</span> <span class="{{ $receta->alergias && mb_strtolower($receta->alergias) !== 'no refiere' ? 'alerta' : '' }}">{{ $receta->alergias }}</span></div>
            </div>

            <div style="font-size: 18px; font-weight: bold; margin: 7px 0 2px;">Rp/</div>
            <table class="meds">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th>Medicamento (DCI) · concentración · forma</th>
                        <th style="width: 14%;">Vía</th>
                        <th style="width: 24%;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($receta->items as $n => $item)
                        <tr>
                            <td>{{ $n + 1 }}</td>
                            <td>
                                <span class="dci">{{ $item->dci }}</span> {{ $item->concentracion }}<br>
                                <span class="muted">{{ $item->forma_farmaceutica }}</span>
                                <div>{{ $item->dosis }} · {{ $item->frecuencia }} · {{ $item->duracion }}</div>
                            </td>
                            <td>{{ $item->via }}</td>
                            <td>{{ $item->cantidadEnLetras() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @include('pdf.partials.firma', ['medico' => $m])
            <div class="pie" style="text-align: center;">Sello</div>
        </td>

        {{-- ===== INDICACIONES (desprendible) ===== --}}
        <td class="mitad der">
            <table class="cab">
                <tr>
                    <td><div class="titulo">INDICACIONES</div><div class="muted">Receta N.º {{ $receta->numeroFormateado() }}</div></td>
                    <td class="num" style="font-weight: normal;">{{ $receta->fecha->format('d/m/Y') }}</td>
                </tr>
            </table>
            <div class="caja"><span class="lbl">Paciente:</span> {{ $p?->apellidos }} {{ $p?->nombres }}</div>

            <div class="bloque">
                @foreach ($receta->items as $n => $item)
                    <div class="ind">
                        <span class="dci">{{ $n + 1 }}. {{ $item->dci }}</span> {{ $item->concentracion }} ({{ mb_strtolower($item->forma_farmaceutica) }})<br>
                        Tomar/aplicar <strong>{{ $item->dosis }}</strong>, {{ mb_strtolower($item->frecuencia) }}, vía {{ mb_strtolower($item->via) }}, durante {{ $item->duracion }}.
                        @if ($item->indicacion)<br><em>{{ $item->indicacion }}</em>@endif
                    </div>
                @endforeach
            </div>

            <div class="bloque">
                <h4>Signos de alarma</h4>
                <div>{{ $receta->signos_alarma ?: 'Acuda a emergencia o llame a su médico si los síntomas empeoran o aparecen nuevos síntomas.' }}</div>
            </div>
            @if ($receta->recomendaciones)
                <div class="bloque">
                    <h4>Recomendaciones no farmacológicas</h4>
                    <div>{{ $receta->recomendaciones }}</div>
                </div>
            @endif

            @include('pdf.partials.firma', ['medico' => $m])
            <div class="pie" style="text-align: center;">
                @if ($contacto)Contacto permanente del prescriptor: {{ $contacto }}@endif
            </div>
        </td>
    </tr>
</table>
<div class="pie" style="position: fixed; bottom: -5mm; left: 0; right: 0; text-align: center;">
    {{ $establecimiento }}@if ($marca->direccion()) · {{ $marca->direccion() }}@endif · Generado el {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>
