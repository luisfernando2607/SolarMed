@php
    use App\Models\Referencia;
    $p = $r->paciente;
    $apellidos = preg_split('/\s+/', trim((string) $p->apellidos), 2);
    $nombres = preg_split('/\s+/', trim((string) $p->nombres), 2);
    // Condición de la edad: H (horas), D (días), M (meses), A (años)
    $nac = $p->fecha_nacimiento;
    [$edad, $condicion] = !$nac ? [null, null] : (
        $nac->diffInDays(now()) < 1 ? [(int) $nac->diffInHours(now()), 'H'] : (
        $nac->diffInMonths(now()) < 1 ? [(int) $nac->diffInDays(now()), 'D'] : (
        $nac->diffInYears(now()) < 1 ? [(int) $nac->diffInMonths(now()), 'M'] : [(int) $nac->diffInYears(now()), 'A'])));
    $x = fn ($cond) => $cond ? 'X' : '';
    $entrante = $r->direccion === 'entrante';
    $medicoF = $entrante ? null : $r->medico;
    $dxs = array_pad($r->diagnosticos ?? [], Referencia::MAX_DIAGNOSTICOS, null);
    $cdxs = array_pad($r->contra_diagnosticos ?? [], Referencia::MAX_DIAGNOSTICOS_CONTRA, null);
    $siNo = fn ($v) => $v === null ? ['', ''] : ($v ? ['X', ''] : ['', 'X']);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Formulario 053 · {{ $r->numero() }}</title>
    <style>
        @page { margin: 7mm 9mm 11mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        td, th { border: 0.6px solid #555; padding: 1.5px 3px; vertical-align: top; }
        th { background: #eef0f2; font-weight: normal; font-size: 6px; text-transform: uppercase; color: #333; text-align: center; }
        .bloque td.t { background: #dfe3e8; font-weight: bold; font-size: 7.5px; text-transform: uppercase; border-color: #555; padding: 1.5px 3px; }
        .v { font-size: 7.5px; text-align: center; height: 10px; }
        .txt { font-size: 7.5px; white-space: pre-line; }
        .firma { height: 30px; text-align: center; }
        .caja { width: 12px; text-align: center; font-weight: bold; }
        .seccion { font-size: 9px; font-weight: bold; margin: 6px 0 3px; }
        .pie { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7px; color: #444; }
        .pie .d { float: right; font-weight: bold; }
    </style>
</head>
<body>
    @include('pdf.partials.confidencial')
    <div class="pie">SNS-MSP/HCU-form.053/2021 <span class="d">REFERENCIA - DERIVACIÓN - CONTRARREFERENCIA - REFERENCIA INVERSA · {{ $r->numero() }}</span></div>

    {{-- ===== SECCIÓN REFERENCIA ===== --}}
    <table class="bloque"><tr><td class="t">A. Datos del establecimiento de origen y usuario / paciente</td></tr></table>
    <table>
        <tr><th>Institución del sistema</th><th>Unicódigo</th><th>Establecimiento de salud</th><th>Tipología</th><th>Número de historia clínica única</th><th>Número de archivo</th></tr>
        <tr>
            @if ($entrante)
                <td class="v">{{ $r->institucion_origen }}</td><td class="v"></td><td class="v">{{ $r->establecimiento_origen }}</td><td class="v"></td>
            @else
                <td class="v">{{ $est['institucion'] }}</td><td class="v">{{ $est['unicodigo'] }}</td><td class="v">{{ $est['nombre'] }}</td><td class="v">{{ $est['tipologia'] }}</td>
            @endif
            <td class="v">{{ $p->cedula }}</td><td class="v">{{ $p->cedula }}</td>
        </tr>
    </table>
    <table>
        <tr><th>Primer apellido</th><th>Segundo apellido</th><th>Primer nombre</th><th>Segundo nombre</th><th>Sexo</th><th>Fecha nacimiento</th><th>Edad</th><th class="caja">H</th><th class="caja">D</th><th class="caja">M</th><th class="caja">A</th></tr>
        <tr>
            <td class="v">{{ $apellidos[0] ?? '' }}</td><td class="v">{{ $apellidos[1] ?? '' }}</td>
            <td class="v">{{ $nombres[0] ?? '' }}</td><td class="v">{{ $nombres[1] ?? '' }}</td>
            <td class="v">{{ $p->sexo === 'femenino' ? 'M' : ($p->sexo === 'masculino' ? 'H' : '') }}</td>
            <td class="v">{{ $nac?->format('Y-m-d') }}</td>
            <td class="v">{{ $edad }}</td>
            @foreach (['H', 'D', 'M', 'A'] as $c)<td class="v caja">{{ $x($condicion === $c) }}</td>@endforeach
        </tr>
    </table>
    <table>
        <tr>
            <td style="width: 45%; padding: 0; border: 0;">
                <table style="margin: 0;">
                    <tr><th colspan="3">N.º teléfono (celular o convencional)</th></tr>
                    <tr><td class="v" colspan="3">{{ collect([$p->telefono, $p->telefono_secundario])->filter()->implode(' / ') }}</td></tr>
                    <tr><th colspan="3">Lugar de residencia actual</th></tr>
                    <tr><th>Provincia</th><th>Cantón</th><th>Parroquia</th></tr>
                    <tr><td class="v">{{ $p->provincia }}</td><td class="v">{{ $p->canton ?: $p->ciudad }}</td><td class="v">{{ $p->parroquia }}</td></tr>
                </table>
            </td>
            <td style="border: 0; padding: 0 0 0 6px;">
                <table style="margin: 0;">
                    <tr>
                        <th style="text-align: left;">Referencia</th><td class="caja">{{ $x($r->tipo === 'referencia') }}</td>
                        <th style="text-align: left;">Derivación</th><td class="caja">{{ $x($r->tipo === 'derivacion') }}</td>
                    </tr>
                    <tr><th colspan="4" style="text-align: left;">Motivo</th></tr>
                    @php $izq = array_slice(Referencia::MOTIVOS, 0, 5, true); $der = array_slice(Referencia::MOTIVOS, 5, null, true); @endphp
                    @for ($i = 0; $i < 5; $i++)
                        @php $ki = array_keys($izq)[$i] ?? null; $kd = array_keys($der)[$i] ?? null; @endphp
                        <tr>
                            <td style="font-size: 7px;">{{ $ki ? "{$ki}. {$izq[$ki]}" : '' }}</td><td class="caja">{{ $ki ? $x($r->motivo === $ki) : '' }}</td>
                            <td style="font-size: 7px;">{{ $kd ? "{$kd}. {$der[$kd]}" : '' }}</td><td class="caja">{{ $kd ? $x($r->motivo === $kd) : '' }}</td>
                        </tr>
                    @endfor
                </table>
            </td>
        </tr>
    </table>

    <table class="bloque"><tr><td class="t">B. Datos del establecimiento al que se refiere - deriva</td></tr></table>
    <table>
        <tr><th>Institución del sistema</th><th>Establecimiento de salud</th><th>Servicio</th><th>Especialidad</th></tr>
        <tr><td class="v">{{ $r->institucion_destino }}</td><td class="v">{{ $r->establecimiento_destino ?: ($entrante ? $est['nombre'] : '') }}</td><td class="v">{{ $r->servicio }}</td><td class="v">{{ $r->especialidad }}</td></tr>
    </table>

    <table class="bloque"><tr><td class="t">C. Resumen del cuadro clínico</td></tr></table>
    <table><tr><td class="txt">{{ $r->resumen_cuadro_clinico }}</td></tr></table>

    <table class="bloque"><tr><td class="t">D. Hallazgos relevantes de exámenes y procedimientos diagnósticos</td></tr></table>
    <table><tr><td class="txt">{{ $r->hallazgos ?: 'NA' }}</td></tr></table>

    <table class="bloque"><tr><td class="t">E. Diagnóstico <span style="font-weight: normal; font-size: 6.5px;">PRE = presuntivo · DEF = definitivo</span></td></tr></table>
    <table>
        <tr><th style="width: 3%;"></th><th>Diagnóstico</th><th style="width: 9%;">CIE</th><th style="width: 5%;">PRE</th><th style="width: 5%;">DEF</th></tr>
        @foreach ($dxs as $i => $d)
            <tr>
                <td class="v">{{ $i + 1 }}.</td>
                <td style="font-size: 8px;">{{ $d['descripcion'] ?? '' }}</td>
                <td class="v">{{ $d['cie10'] ?? '' }}</td>
                <td class="v">{{ $d ? $x(($d['tipo'] ?? '') === 'pre') : '' }}</td>
                <td class="v">{{ $d ? $x(($d['tipo'] ?? '') === 'def') : '' }}</td>
            </tr>
        @endforeach
    </table>

    <table class="bloque"><tr><td class="t">F. Datos del profesional responsable</td></tr></table>
    <table>
        <tr><th>Fecha (aaaa-mm-dd)</th><th>Hora (hh:mm)</th><th>Nombres y apellidos</th><th>N.º documento de identificación</th><th style="width: 30%;">Firma y sello</th></tr>
        <tr>
            <td class="v">{{ $r->fecha->format('Y-m-d') }}</td>
            <td class="v">{{ $r->fecha->format('H:i') }}</td>
            <td class="v">{{ $entrante ? $r->profesional_origen : ($medicoF ? trim($medicoF->nombres . ' ' . $medicoF->apellidos) : '') }}</td>
            <td class="v">{{ $entrante ? '' : $medicoF?->user?->cedula }}</td>
            <td class="firma">
                @if ($medicoF?->firmaRuta())<img src="{{ $medicoF->firmaRuta() }}" alt="" style="max-height: 28px; max-width: 140px;">@endif
            </td>
        </tr>
    </table>

    @php [$rjSi, $rjNo] = $siNo($r->referencia_justificada); [$djSi, $djNo] = $siNo($r->derivacion_justificada); @endphp
    <table class="bloque"><tr><td class="t">G. Evaluación de la referencia / derivación</td></tr></table>
    <table>
        <tr>
            <th style="text-align: left;">Referencia justificada</th><th class="caja">SI</th><td class="caja">{{ $rjSi }}</td><th class="caja">NO</th><td class="caja">{{ $rjNo }}</td>
            <th style="text-align: left;">Derivación justificada</th><th class="caja">SI</th><td class="caja">{{ $djSi }}</td><th class="caja">NO</th><td class="caja">{{ $djNo }}</td>
        </tr>
    </table>

    {{-- ===== SECCIÓN CONTRARREFERENCIA / REFERENCIA INVERSA ===== --}}
    @if ($r->tieneContrarreferencia())
        @php $mc = $r->contraMedico; @endphp
        <table style="margin-top: 8px;">
            <tr>
                <td style="border: 0; font-weight: bold;">CONTRARREFERENCIA</td><td class="caja">{{ $x($r->contra_tipo === 'contrarreferencia') }}</td>
                <td style="border: 0; text-align: right; font-weight: bold;">REFERENCIA INVERSA</td><td class="caja">{{ $x($r->contra_tipo === 'referencia_inversa') }}</td>
            </tr>
        </table>

        <table class="bloque"><tr><td class="t">A. Datos del establecimiento que contrarrefiere o realiza la referencia inversa</td></tr></table>
        <table>
            <tr><th>Institución del sistema</th><th>Unicódigo</th><th>Establecimiento de salud</th><th>Tipología</th><th>Número de historia clínica única</th><th>Número de archivo</th></tr>
            <tr>
                <td class="v">{{ $r->contra_institucion }}</td>
                <td class="v">{{ $entrante ? $est['unicodigo'] : '' }}</td>
                <td class="v">{{ $r->contra_establecimiento }}</td>
                <td class="v">{{ $entrante ? $est['tipologia'] : '' }}</td>
                <td class="v">{{ $p->cedula }}</td><td class="v">{{ $p->cedula }}</td>
            </tr>
        </table>

        <table class="bloque"><tr><td class="t">B. Datos del establecimiento al cual se contrarrefiere o se realiza referencia inversa</td></tr></table>
        <table>
            <tr><th>Institución del sistema</th><th>Establecimiento de salud</th><th>Distrito</th><th>Fecha (aaaa-mm-dd)</th></tr>
            <tr>
                @if ($entrante)
                    <td class="v">{{ $r->institucion_origen }}</td><td class="v">{{ $r->establecimiento_origen }}</td><td class="v"></td>
                @else
                    <td class="v">{{ $est['institucion'] }}</td><td class="v">{{ $est['nombre'] }}</td><td class="v">{{ $est['distrito'] }}</td>
                @endif
                <td class="v">{{ $r->contra_fecha?->format('Y-m-d') }}</td>
            </tr>
        </table>

        <table class="bloque"><tr><td class="t">C. Resumen del cuadro clínico</td></tr></table>
        <table><tr><td class="txt">{{ $r->contra_resumen }}</td></tr></table>

        <table class="bloque"><tr><td class="t">D. Hallazgos relevantes de exámenes y procedimientos diagnósticos</td></tr></table>
        <table><tr><td class="txt">{{ $r->contra_hallazgos ?: 'NA' }}</td></tr></table>

        <table class="bloque"><tr><td class="t">E. Tratamientos y procedimientos terapéuticos realizados</td></tr></table>
        <table><tr><td class="txt">{{ $r->contra_tratamientos_realizados ?: 'NA' }}</td></tr></table>

        <table class="bloque"><tr><td class="t">F. Diagnóstico</td></tr></table>
        <table>
            <tr><th style="width: 3%;"></th><th>Diagnóstico</th><th style="width: 9%;">CIE</th><th style="width: 5%;">PRE</th><th style="width: 5%;">DEF</th></tr>
            @foreach ($cdxs as $i => $d)
                <tr>
                    <td class="v">{{ $i + 1 }}.</td>
                    <td style="font-size: 8px;">{{ $d['descripcion'] ?? '' }}</td>
                    <td class="v">{{ $d['cie10'] ?? '' }}</td>
                    <td class="v">{{ $d ? $x(($d['tipo'] ?? '') === 'pre') : '' }}</td>
                    <td class="v">{{ $d ? $x(($d['tipo'] ?? '') === 'def') : '' }}</td>
                </tr>
            @endforeach
        </table>

        <table class="bloque"><tr><td class="t">G. Tratamiento recomendado a seguir en el establecimiento de salud al que se contrarrefiere</td></tr></table>
        <table><tr><td class="txt">{{ $r->contra_tratamiento_recomendado }}</td></tr></table>

        <table class="bloque"><tr><td class="t">H. Datos del profesional responsable</td></tr></table>
        <table>
            <tr><th>Fecha (aaaa-mm-dd)</th><th>Hora (hh:mm)</th><th>Nombres y apellidos</th><th>N.º documento de identificación</th><th style="width: 30%;">Firma y sello</th></tr>
            <tr>
                <td class="v">{{ $r->contra_fecha?->format('Y-m-d') }}</td>
                <td class="v">{{ $r->contra_fecha?->format('H:i') }}</td>
                <td class="v">{{ $mc ? trim($mc->nombres . ' ' . $mc->apellidos) : $r->contra_profesional }}</td>
                <td class="v">{{ $mc?->user?->cedula ?: $r->contra_profesional_cedula }}</td>
                <td class="firma">
                    @if ($mc?->firmaRuta())<img src="{{ $mc->firmaRuta() }}" alt="" style="max-height: 28px; max-width: 140px;">@endif
                </td>
            </tr>
        </table>
    @endif
</body>
</html>
