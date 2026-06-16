<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Historial Clínico - {{ $paciente->nombre_completo }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #2563eb; padding-bottom: 12px; }
        .header h1 { font-size: 18px; color: #2563eb; margin: 0 0 4px; }
        .header p { font-size: 10px; color: #666; margin: 2px 0; }
        .section { margin-bottom: 18px; }
        .section h2 { font-size: 13px; color: #2563eb; border-bottom: 1px solid #ddd; padding-bottom: 4px; margin: 0 0 8px; }
        .section h3 { font-size: 11px; color: #444; margin: 6px 0 4px; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        td, th { padding: 3px 5px; text-align: left; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 35%; }
        .value { }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-m { background: #dbeafe; color: #1d4ed8; }
        .badge-f { background: #fce7f3; color: #be185d; }
        .consulta { border-left: 3px solid #2563eb; padding-left: 8px; margin-bottom: 8px; }
        .consulta .fecha { font-size: 9px; color: #999; }
        .consulta .medico { font-size: 9px; color: #666; }
        .consulta .dx { font-weight: bold; color: #2563eb; font-size: 10px; }
        .footer { text-align: center; font-size: 9px; color: #999; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 8px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/SolarMed.jpg') }}" alt="SolarMed" style="height: 50px; margin-bottom: 6px;">
        <h1>HISTORIAL CLÍNICO</h1>
        <p>SolarMed Software — Sistema Médico</p>
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="section">
        <h2>Datos del paciente</h2>
        <table>
            <tr><td class="label">Nombre:</td><td class="value">{{ $paciente->nombre_completo }}</td></tr>
            <tr><td class="label">Cédula:</td><td class="value">{{ $paciente->cedula }}</td></tr>
            <tr><td class="label">Edad:</td><td class="value">{{ $paciente->edad ?? '--' }} años</td></tr>
            <tr><td class="label">Sexo:</td><td class="value">{{ ucfirst($paciente->sexo) }}</td></tr>
            <tr><td class="label">Fecha de nacimiento:</td><td class="value">{{ $paciente->fecha_nacimiento?->format('d/m/Y') ?? '--' }}</td></tr>
            <tr><td class="label">Ocupación:</td><td class="value">{{ $paciente->ocupacion ?? '--' }}</td></tr>
            <tr><td class="label">Teléfono:</td><td class="value">{{ $paciente->telefono ?? '--' }}</td></tr>
            <tr><td class="label">Email:</td><td class="value">{{ $paciente->email ?? '--' }}</td></tr>
            <tr><td class="label">Dirección:</td><td class="value">{{ $paciente->direccion ?? '--' }}</td></tr>
            <tr><td class="label">Ciudad:</td><td class="value">{{ $paciente->ciudad ?? '--' }}</td></tr>
            <tr><td class="label">Referido por:</td><td class="value">{{ $paciente->referido_por ?? '--' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Datos clínicos</h2>
        <table>
            <tr><td class="label">Grupo sanguíneo:</td><td class="value">{{ $paciente->grupo_sanguineo ?? '--' }}</td></tr>
            <tr><td class="label">Peso:</td><td class="value">{{ $paciente->peso ? $paciente->peso . ' kg' : '--' }}</td></tr>
            <tr><td class="label">Altura:</td><td class="value">{{ $paciente->altura ? $paciente->altura . ' m' : '--' }}</td></tr>
            <tr><td class="label">Medicamentos:</td><td class="value">{{ $paciente->medicamentos ?? 'Ninguno' }}</td></tr>
            <tr><td class="label">Cirugías previas:</td><td class="value">{{ $paciente->cirugias ?? 'Ninguna' }}</td></tr>
            <tr><td class="label">Alergias:</td><td class="value">{{ $paciente->alergias ?? 'Ninguna' }}</td></tr>
            <tr><td class="label">Antecedentes personales:</td><td class="value">{{ $paciente->antecedentes ?? 'Ninguno' }}</td></tr>
            <tr><td class="label">Antecedentes familiares:</td><td class="value">{{ $paciente->enfermedades_familiares ?? 'Ninguno' }}</td></tr>
        </table>
    </div>

    @if ($paciente->sexo === 'femenino')
    <div class="section">
        <h2>Gineco-obstétrico</h2>
        <table>
            <tr><td class="label">FUM:</td><td class="value">{{ $paciente->fum?->format('d/m/Y') ?? '--' }}</td></tr>
            <tr><td class="label">Método anticonceptivo:</td><td class="value">{{ $paciente->metodo_anticonceptivo ?? '--' }}</td></tr>
            <tr><td class="label">Gestas:</td><td class="value">{{ $paciente->gestas ?? '--' }}</td></tr>
            <tr><td class="label">Partos:</td><td class="value">{{ $paciente->partos ?? '--' }}</td></tr>
            <tr><td class="label">Cesáreas:</td><td class="value">{{ $paciente->cesareas ?? '--' }}</td></tr>
            <tr><td class="label">Abortos:</td><td class="value">{{ $paciente->abortos ?? '--' }}</td></tr>
        </table>
    </div>
    @endif

    <div class="section">
        <h2>Fichas médicas ({{ $paciente->consultas->count() }})</h2>
        @forelse ($paciente->consultas as $consulta)
            <div class="consulta">
                <div class="fecha">{{ $consulta->fecha->format('d/m/Y H:i') }}</div>
                <div class="medico">{{ $consulta->medico?->nombre_completo ?? '--' }} @if ($consulta->especialidad)&middot; {{ $consulta->especialidad->nombre }}@endif</div>
                <div class="dx">@if ($consulta->codigo_cie10)[{{ $consulta->codigo_cie10 }}] @endif{{ $consulta->diagnostico }}</div>
                <div><strong>Motivo:</strong> {{ $consulta->motivo_consulta ?? '--' }}</div>
                @if ($consulta->tratamiento)<div><strong>Tratamiento:</strong> {{ $consulta->tratamiento }}</div>@endif
                @if ($consulta->indicaciones)<div><strong>Indicaciones:</strong> {{ $consulta->indicaciones }}</div>@endif
            </div>
        @empty
            <p style="color: #999;">Sin fichas médicas registradas.</p>
        @endforelse
    </div>

    <div class="footer">
        <p>Documento generado electrónicamente el {{ now()->format('d/m/Y H:i') }}</p>
        <p>SolarMed Software &mdash; {{ config('app.url') }}</p>
    </div>
</body>
</html>
