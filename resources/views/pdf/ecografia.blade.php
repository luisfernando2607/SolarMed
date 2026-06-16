<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Informe de Ecografía</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #7c3aed; padding-bottom: 15px; }
        .header h1 { font-size: 20px; color: #7c3aed; margin: 0; }
        .header p { font-size: 11px; color: #666; margin: 3px 0; }
        .section { margin-bottom: 20px; }
        .section h2 { font-size: 14px; color: #7c3aed; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        td { padding: 4px 6px; }
        .label { font-weight: bold; color: #555; width: 40%; }
        .value { }
        .conclusion { background: #f5f3ff; padding: 12px; border-radius: 6px; margin-top: 10px; }
        .footer { text-align: center; font-size: 10px; color: #999; margin-top: 40px; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/SolarMed.jpg') }}" alt="SolarMed" style="height: 50px; margin-bottom: 6px;">
        <h1>INFORME DE ECOGRAFÍA</h1>
        <p>SolarMed Software</p>
    </div>

    <div class="section">
        <h2>Datos del paciente</h2>
        <table>
            <tr><td class="label">Paciente:</td><td class="value">{{ $eco->paciente->nombre_completo }}</td></tr>
            <tr><td class="label">Cédula:</td><td class="value">{{ $eco->paciente->cedula }}</td></tr>
            <tr><td class="label">Edad:</td><td class="value">{{ $eco->paciente->edad }} años</td></tr>
            <tr><td class="label">Médico:</td><td class="value">{{ $eco->medico?->nombre_completo ?? '--' }}</td></tr>
            <tr><td class="label">Fecha:</td><td class="value">{{ $eco->fecha?->format('d/m/Y') ?? '--' }}</td></tr>
            <tr><td class="label">Indicación:</td><td class="value">{{ $eco->indicacion ?? '--' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Datos obstétricos</h2>
        <table>
            <tr><td class="label">Semanas de gestación:</td><td class="value">{{ $eco->semanas_gestacion ?? '--' }}</td></tr>
            <tr><td class="label">FUM:</td><td class="value">{{ $eco->fum?->format('d/m/Y') ?? '--' }}</td></tr>
            <tr><td class="label">FPP:</td><td class="value">{{ $eco->fpp?->format('d/m/Y') ?? '--' }}</td></tr>
            <tr><td class="label">Presentación:</td><td class="value">{{ $eco->presentacion ?? '--' }}</td></tr>
            <tr><td class="label">LCF:</td><td class="value">{{ $eco->lcf ? $eco->lcf . ' lpm' : '--' }}</td></tr>
            <tr><td class="label">Placenta:</td><td class="value">{{ $eco->placenta ?? '--' }}</td></tr>
            <tr><td class="label">Líquido amniótico:</td><td class="value">{{ $eco->liquido_amniotico ?? '--' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Biometría fetal</h2>
        <table>
            <tr><td class="label">DBP (Diámetro biparietal):</td><td class="value">{{ $eco->dbp ? $eco->dbp . ' mm' : '--' }}</td></tr>
            <tr><td class="label">CC (Circunferencia cefálica):</td><td class="value">{{ $eco->cc ? $eco->cc . ' mm' : '--' }}</td></tr>
            <tr><td class="label">CA (Circunferencia abdominal):</td><td class="value">{{ $eco->ca ? $eco->ca . ' mm' : '--' }}</td></tr>
            <tr><td class="label">LF (Longitud de fémur):</td><td class="value">{{ $eco->lf ? $eco->lf . ' mm' : '--' }}</td></tr>
            <tr><td class="label">Peso fetal estimado:</td><td class="value">{{ $eco->peso_fetal_estimado ? $eco->peso_fetal_estimado . ' g' : '--' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Conclusión</h2>
        <div class="conclusion">{{ $eco->conclusion ?? 'Sin hallazgos relevantes.' }}</div>
    </div>

    <div class="footer">
        <p>Documento generado electrónicamente el {{ now()->format('d/m/Y H:i') }}</p>
        <p>SolarMed Software &mdash; {{ config('app.url') }}</p>
    </div>
</body>
</html>
