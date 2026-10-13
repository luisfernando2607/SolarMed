{{-- Firma y sello del médico. Uso: @include('pdf.partials.firma', ['medico' => $medico]) --}}
<div style="margin-top: 35px; text-align: center;">
    @if ($medico?->firmaRuta())
        <img src="{{ $medico->firmaRuta() }}" alt="Firma" style="max-height: 70px; max-width: 220px; margin-bottom: -6px;">
    @else
        <div style="height: 45px;"></div>
    @endif
    <div style="border-top: 1px solid #333; width: 60%; margin: 0 auto 4px;"></div>
    <div style="font-size: 11px; font-weight: bold;">{{ $medico?->nombre_completo }}</div>
    @if ($medico?->especialidad)
        <div style="font-size: 9px; color: #555;">{{ $medico->especialidad->nombre }}</div>
    @endif
    @if ($medico?->colegiatura)
        <div style="font-size: 9px; color: #555;">Registro ACESS: {{ $medico->colegiatura }}</div>
    @endif
</div>
