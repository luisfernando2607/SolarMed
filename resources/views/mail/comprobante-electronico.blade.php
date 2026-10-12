<x-mail::message>
# {{ $tipo }} electrónica

Estimado(a) **{{ $paciente?->nombre_completo ?? 'cliente' }}**:

Adjuntamos su {{ mb_strtolower($tipo) }} electrónica **{{ $numero }}**@if ($facturaModificada), que anula la factura {{ $facturaModificada }}@endif, autorizada por el Servicio de Rentas Internas (SRI).

<x-mail::table>
| | |
|:--|--:|
| Número | {{ $numero }} |
| Valor | ${{ number_format($total, 2) }} |
@if ($fechaAutorizacion)
| Autorizada | {{ $fechaAutorizacion->format('d/m/Y H:i') }} |
@endif
</x-mail::table>

**Clave de acceso:**
{{ $claveAcceso }}

Puede verificar este comprobante en el portal del SRI (sri.gob.ec) con la clave de acceso.
Se adjunta la representación impresa (PDF) y el archivo XML autorizado.

Gracias por su confianza,<br>
{{ $marca->nombre() }}
</x-mail::message>
