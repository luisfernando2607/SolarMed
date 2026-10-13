<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Consentimiento informado {{ $c->numero() }}</title>
    <style>
        @page { margin: 12mm 12mm 14mm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 8.5px; color: #111; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .bloque { border: 1px solid #555; margin-bottom: 7px; }
        .bloque > .titulo { background: #d9d9d9; font-weight: bold; font-size: 10px; padding: 2px 5px; border-bottom: 1px solid #555; }
        .celdas td { border: 1px solid #888; padding: 2px 4px; vertical-align: top; }
        .rotulo { font-size: 6.5px; text-transform: uppercase; color: #333; text-align: center; background: #f2f2f2; }
        .valor { font-size: 9px; text-align: center; height: 12px; }
        .fila td { border-bottom: 1px solid #aaa; padding: 3px 5px; vertical-align: top; }
        .fila .etq { width: 30%; font-size: 7px; text-transform: uppercase; color: #333; }
        .texto { padding: 4px 6px; text-align: justify; }
        .firmas td { padding: 22px 8px 2px; text-align: center; font-size: 7px; color: #333; vertical-align: bottom; }
        .linea { border-top: 1px solid #333; padding-top: 2px; }
        .dato { font-size: 8.5px; color: #111; margin-bottom: 2px; min-height: 10px; }
        .sub { font-weight: bold; font-size: 8px; padding: 4px 6px 0; }
        .pie { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7px; color: #444; }
        .marca { font-size: 8px; color: #555; }
        .x { display: inline-block; width: 9px; height: 9px; border: 1px solid #333; text-align: center; line-height: 9px; font-size: 8px; font-weight: bold; }
        .salto { page-break-before: always; }
    </style>
</head>
<body>
@php
    $p = $c->paciente;
    $m = $c->medico;
    $e = $marca->establecimiento();
    $partir = function (?string $texto): array {
        $partes = preg_split('/\s+/', trim((string) $texto)) ?: [];
        return [$partes[0] ?? '', implode(' ', array_slice($partes, 1))];
    };
    [$apellido1, $apellido2] = $partir($p->apellidos);
    [$nombre1, $nombre2] = $partir($p->nombres);

    // Condición de la edad del formulario: H horas, D días, M meses, A años
    $nac = $p->fecha_nacimiento;
    if (!$nac) { $edad = null; $condicion = null; }
    elseif ($nac->diffInYears(now()) >= 1) { $edad = (int) $nac->diffInYears(now()); $condicion = 'A'; }
    elseif ($nac->diffInMonths(now()) >= 1) { $edad = (int) $nac->diffInMonths(now()); $condicion = 'M'; }
    elseif ($nac->diffInDays(now()) >= 1) { $edad = (int) $nac->diffInDays(now()); $condicion = 'D'; }
    else { $edad = (int) $nac->diffInHours(now()); $condicion = 'H'; }

    $profesional = $m?->nombre_completo;
    $codigoProfesional = collect([$m?->user?->cedula ? 'C.I. ' . $m->user->cedula : null, $m?->colegiatura ? 'Reg. ' . $m->colegiatura : null])->filter()->implode(' · ');
    $na = fn ($v) => filled($v) ? $v : 'NA';
    $rep = fn (string $estado) => $c->representante_nombre && $c->estado === $estado;
@endphp

    @include('pdf.partials.confidencial')
    <table style="margin-bottom: 6px;">
        <tr>
            <td style="width: 60px;"><img src="{{ $marca->logoRuta() }}" alt="" style="max-height: 38px; max-width: 55px;"></td>
            <td>
                <div style="font-size: 11px; font-weight: bold;">{{ $marca->nombre() }}</div>
                <div class="marca">Consentimiento informado {{ $c->numero() }} · {{ $c->estadoTexto() }}</div>
            </td>
        </tr>
    </table>

    {{-- A. DATOS DEL ESTABLECIMIENTO Y USUARIO --}}
    <div class="bloque">
        <div class="titulo">A. DATOS DEL ESTABLECIMIENTO Y USUARIO</div>
        <table class="celdas">
            <tr>
                <td class="rotulo" colspan="2">Institución del sistema</td>
                <td class="rotulo" colspan="3">Establecimiento de salud</td>
                <td class="rotulo" colspan="2">Número de historia clínica única</td>
                <td class="rotulo" colspan="4">Número de archivo</td>
            </tr>
            <tr>
                <td class="valor" colspan="2">{{ $e['institucion'] }}</td>
                <td class="valor" colspan="3">{{ $e['nombre'] }}@if ($e['unicodigo']) · Unicódigo {{ $e['unicodigo'] }}@endif</td>
                <td class="valor" colspan="2">{{ $p->cedula }}</td>
                <td class="valor" colspan="4">{{ str_pad((string) $p->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td class="rotulo" style="width: 16%;">Primer apellido</td>
                <td class="rotulo" style="width: 16%;">Segundo apellido</td>
                <td class="rotulo" style="width: 15%;">Primer nombre</td>
                <td class="rotulo" style="width: 15%;" colspan="2">Segundo nombre</td>
                <td class="rotulo" style="width: 7%;">Sexo</td>
                <td class="rotulo" style="width: 7%;">Edad</td>
                <td class="rotulo">H</td><td class="rotulo">D</td><td class="rotulo">M</td><td class="rotulo">A</td>
            </tr>
            <tr>
                <td class="valor">{{ $apellido1 }}</td>
                <td class="valor">{{ $apellido2 }}</td>
                <td class="valor">{{ $nombre1 }}</td>
                <td class="valor" colspan="2">{{ $nombre2 }}</td>
                <td class="valor">{{ $p->sexo === 'femenino' ? 'M' : ($p->sexo === 'masculino' ? 'H' : '') }}</td>
                <td class="valor">{{ $edad ?? '' }}</td>
                @foreach (['H', 'D', 'M', 'A'] as $cond)
                    <td class="valor">{{ $condicion === $cond ? 'X' : '' }}</td>
                @endforeach
            </tr>
        </table>
    </div>

    {{-- B. CONSENTIMIENTO INFORMADO --}}
    <div class="bloque">
        <div class="titulo">B. CONSENTIMIENTO INFORMADO</div>
        <table>
            <tr class="fila"><td class="etq">Consentimiento informado para:</td><td colspan="3"><strong>{{ $c->procedimiento }}</strong></td></tr>
            <tr class="fila">
                <td class="etq">Servicio:</td><td>{{ $c->servicio }}</td>
                <td class="etq" style="width: 16%;">Tipo de atención:</td>
                <td style="width: 26%;"><span class="x">{{ $c->tipo_atencion === 'ambulatorio' ? 'X' : '' }}</span> Ambulatorio &nbsp; <span class="x">{{ $c->tipo_atencion === 'hospitalizacion' ? 'X' : '' }}</span> Hospitalización</td>
            </tr>
            <tr class="fila"><td class="etq">Diagnóstico:</td><td>{{ $c->diagnostico }}</td><td class="etq">CIE 10:</td><td>{{ $na($c->codigo_cie10) }}</td></tr>
            <tr class="fila"><td class="etq">Nombre del procedimiento recomendado:</td><td colspan="3">{{ $c->procedimiento }}</td></tr>
            <tr class="fila"><td class="etq">En qué consiste:</td><td colspan="3">{!! nl2br(e($c->en_que_consiste)) !!}</td></tr>
            <tr class="fila"><td class="etq">Cómo se realiza:</td><td colspan="3">{!! nl2br(e($na($c->como_se_realiza))) !!}</td></tr>
            <tr class="fila">
                <td class="etq">Gráfico de la intervención:</td>
                <td colspan="3" style="text-align: center;">
                    @if ($grafico)
                        <img src="{{ $grafico }}" alt="" style="max-height: 170px; max-width: 100%;">
                    @else
                        NA
                    @endif
                </td>
            </tr>
            <tr class="fila"><td class="etq">Duración estimada de la intervención:</td><td colspan="3">{{ $na($c->duracion_estimada) }}</td></tr>
            <tr class="fila"><td class="etq">Beneficios del procedimiento:</td><td colspan="3">{!! nl2br(e($na($c->beneficios))) !!}</td></tr>
            <tr class="fila"><td class="etq">Riesgos frecuentes (poco graves):</td><td colspan="3">{!! nl2br(e($na($c->riesgos_frecuentes))) !!}</td></tr>
            <tr class="fila"><td class="etq">Riesgos poco frecuentes (graves):</td><td colspan="3">{!! nl2br(e($na($c->riesgos_graves))) !!}</td></tr>
            <tr class="fila"><td class="etq">De existir, riesgos específicos relacionados con el paciente (edad, estado de salud, creencias, valores, etc.):</td><td colspan="3">{!! nl2br(e($na($c->riesgos_especificos))) !!}</td></tr>
            <tr class="fila"><td class="etq">Alternativas al procedimiento:</td><td colspan="3">{!! nl2br(e($na($c->alternativas))) !!}</td></tr>
            <tr class="fila"><td class="etq">Descripción del manejo posterior al procedimiento:</td><td colspan="3">{!! nl2br(e($na($c->manejo_posterior))) !!}</td></tr>
            <tr class="fila"><td class="etq">Consecuencias posibles si no se realiza el procedimiento:</td><td colspan="3">{!! nl2br(e($na($c->consecuencias_no_realizar))) !!}</td></tr>
        </table>
    </div>

    <div class="salto"></div>

    {{-- C. DECLARACIÓN DE CONSENTIMIENTO INFORMADO --}}
    <div class="bloque">
        <div class="titulo">C. DECLARACIÓN DE CONSENTIMIENTO INFORMADO</div>
        <table class="celdas">
            <tr>
                <td class="rotulo" style="width: 15%;">Fecha</td><td class="valor" style="width: 35%;">{{ ($c->fecha_aceptacion ?? $c->fecha_informacion)->format('d/m/Y') }}</td>
                <td class="rotulo" style="width: 15%;">Hora</td><td class="valor">{{ $c->fecha_aceptacion?->format('H:i') ?? $c->hora_informacion }}</td>
            </tr>
        </table>
        <div class="texto">{{ \App\Models\Consentimiento::TEXTO_DECLARACION }}</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $p->nombre_completo }}</div><div class="linea">Nombre completo del paciente</div></td>
                <td style="width: 24%;"><div class="dato">{{ $p->cedula }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del paciente o huella, según el caso</div></td>
            </tr>
            <tr>
                <td><div class="dato">{{ $profesional }}</div><div class="linea">Nombre del profesional que realiza el procedimiento</div></td>
                <td colspan="2"><div class="dato">{{ $codigoProfesional }}</div><div class="linea">Firma, sello y código del profesional de la salud que realizará el procedimiento</div></td>
            </tr>
        </table>
        <div class="sub">Si el paciente no está en capacidad para firmar el consentimiento informado:</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $rep('aceptado') ? $c->representante_nombre : '' }}</div><div class="linea">Nombre del representante legal</div></td>
                <td style="width: 24%;"><div class="dato">{{ $rep('aceptado') ? $c->representante_cedula : '' }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del representante legal</div></td>
            </tr>
            <tr>
                <td><div class="dato">{{ $rep('aceptado') ? $c->representante_parentesco : '' }}</div><div class="linea">Parentesco</div></td>
                <td colspan="2"></td>
            </tr>
        </table>
    </div>

    {{-- D. NEGATIVA DEL CONSENTIMIENTO INFORMADO --}}
    <div class="bloque">
        <div class="titulo">D. NEGATIVA DEL CONSENTIMIENTO INFORMADO</div>
        <table class="celdas">
            <tr><td class="rotulo" style="width: 15%;">Fecha</td><td class="valor">{{ $c->fecha_negativa?->format('d/m/Y H:i') }}</td></tr>
        </table>
        <div class="texto">{{ \App\Models\Consentimiento::TEXTO_NEGATIVA }}</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $c->estado === 'negado' ? $p->nombre_completo : '' }}</div><div class="linea">Nombre completo del paciente</div></td>
                <td style="width: 24%;"><div class="dato">{{ $c->estado === 'negado' ? $p->cedula : '' }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del paciente o huella, según el caso</div></td>
            </tr>
            <tr>
                <td><div class="dato">{{ $c->estado === 'negado' ? $profesional : '' }}</div><div class="linea">Nombre del profesional que realiza el procedimiento</div></td>
                <td colspan="2"><div class="dato">{{ $c->estado === 'negado' ? $codigoProfesional : '' }}</div><div class="linea">Firma, sello y código del profesional de la salud que realizará el procedimiento</div></td>
            </tr>
        </table>
        <div class="sub">Si el paciente no está en capacidad para firmar el consentimiento informado:</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $rep('negado') ? $c->representante_nombre : '' }}</div><div class="linea">Nombre del representante legal</div></td>
                <td style="width: 24%;"><div class="dato">{{ $rep('negado') ? $c->representante_cedula : '' }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del representante legal</div></td>
            </tr>
            <tr><td><div class="dato">{{ $rep('negado') ? $c->representante_parentesco : '' }}</div><div class="linea">Parentesco</div></td><td colspan="2"></td></tr>
        </table>
        <div class="sub">Si el paciente no acepta el procedimiento sugerido por el profesional y se niega a firmar este acápite:</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $c->negativa_sin_firma ? $c->testigo_nombre : '' }}</div><div class="linea">Nombre completo del testigo</div></td>
                <td style="width: 24%;"><div class="dato">{{ $c->negativa_sin_firma ? $c->testigo_cedula : '' }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del testigo</div></td>
            </tr>
        </table>
    </div>

    {{-- E. REVOCATORIA DEL CONSENTIMIENTO INFORMADO --}}
    <div class="bloque">
        <div class="titulo">E. REVOCATORIA DEL CONSENTIMIENTO INFORMADO</div>
        <div class="texto">{{ \App\Models\Consentimiento::TEXTO_REVOCATORIA }}@if ($c->fecha_revocatoria) <strong>Fecha: {{ $c->fecha_revocatoria->format('d/m/Y H:i') }}.</strong>@endif</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $c->estado === 'revocado' ? $p->nombre_completo : '' }}</div><div class="linea">Nombre completo del paciente</div></td>
                <td style="width: 24%;"><div class="dato">{{ $c->estado === 'revocado' ? $p->cedula : '' }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del paciente o huella, según el caso</div></td>
            </tr>
            <tr>
                <td><div class="dato">{{ $c->estado === 'revocado' ? $profesional : '' }}</div><div class="linea">Nombre del profesional que realiza el procedimiento</div></td>
                <td colspan="2"><div class="dato">{{ $c->estado === 'revocado' ? $codigoProfesional : '' }}</div><div class="linea">Firma, sello y código del profesional de la salud que realizará el procedimiento</div></td>
            </tr>
        </table>
        <div class="sub">Si el paciente no está en capacidad para firmar el consentimiento informado:</div>
        <table class="firmas">
            <tr>
                <td style="width: 38%;"><div class="dato">{{ $rep('revocado') ? $c->representante_nombre : '' }}</div><div class="linea">Nombre del representante legal</div></td>
                <td style="width: 24%;"><div class="dato">{{ $rep('revocado') ? $c->representante_cedula : '' }}</div><div class="linea">Cédula de ciudadanía</div></td>
                <td><div class="dato"></div><div class="linea">Firma del representante legal</div></td>
            </tr>
            <tr><td><div class="dato">{{ $rep('revocado') ? $c->representante_parentesco : '' }}</div><div class="linea">Parentesco</div></td><td colspan="2"></td></tr>
        </table>
    </div>

    <table class="pie">
        <tr>
            <td>SNS-MSP / HCU-form.024 · Todas las firmas con esferográfico azul</td>
            <td style="text-align: right; font-weight: bold;">CONSENTIMIENTO INFORMADO</td>
        </tr>
    </table>
</body>
</html>
