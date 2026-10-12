<x-mail::message>
# Recordatorio de cita

Estimado(a) **{{ $cita->paciente?->nombre_completo }}**:

Le recordamos su cita de **{{ mb_strtolower($cita->tipo) }}**:

<x-mail::table>
| | |
|:--|:--|
| Fecha | {{ $cita->fecha_hora->translatedFormat('l d \d\e F \d\e Y') }} |
| Hora | {{ $cita->fecha_hora->format('H:i') }} |
| Médico | {{ $cita->medico?->nombre_completo }}{{ $cita->medico?->especialidad ? ' — ' . $cita->medico->especialidad->nombre : '' }} |
@if ($direccion)
| Dirección | {{ $direccion }} |
@endif
</x-mail::table>

Por favor llegue 10 minutos antes con su cédula y, si los tiene, sus exámenes recientes.

@if ($telefono)
Si no puede asistir, comuníquese al **{{ $telefono }}** para reprogramar.
@else
Si no puede asistir, comuníquese con la consulta para reprogramar.
@endif

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
