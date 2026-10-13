{{-- Preparación de enfermería del turno: botón para tomar los signos o distintivo si ya se tomaron --}}
@can('preparacion.registrar')
    @if ($turno->paciente_id)
        @if ($turno->preparacion)
            @php $alerta = (bool) $turno->preparacion->fueraDeRango(); @endphp
            <a href="{{ route('turnos.preparacion', $turno) }}" wire:navigate
               class="shrink-0 inline-flex items-center gap-1 px-2 py-1 rounded-lg text-[11px] font-medium {{ $alerta ? 'bg-amber-50 text-amber-800 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
               title="{{ $alerta ? 'Signos tomados: hay valores fuera del rango habitual' : 'Signos vitales tomados' }}">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $alerta ? 'M12 9v3.75m0 3.75h.008v.008H12v-.008z' : 'M4.5 12.75l6 6 9-13.5' }}"/></svg>
                Signos
            </a>
        @else
            <a href="{{ route('turnos.preparacion', $turno) }}" wire:navigate
               class="shrink-0 px-2.5 py-1.5 rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-700 hover:bg-gray-50 transition" title="Tomar signos vitales">
                Tomar signos
            </a>
        @endif
    @endif
@endcan
