{{-- Encuesta de satisfacción de la consulta: responder en un dispositivo de la clínica o enviar por WhatsApp --}}
@if ($encuesta)
    <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
        <span class="text-gray-400">Encuesta:</span>
        @if ($encuesta->respondida_at)
            <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">Respondida ✓</span>
        @elseif ($encuesta->disponible())
            <a href="{{ $encuesta->url() }}?dispositivo=1" target="_blank" rel="noopener"
               class="px-2 py-0.5 rounded-md bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700" title="Abre la encuesta para que el paciente la responda en esta pantalla o en una tableta">Responder aquí</a>
            @if ($wa = app(\App\Services\EncuestaService::class)->enlaceWhatsapp($encuesta))
                <a href="{{ $wa }}" target="_blank" rel="noopener" class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                   title="{{ str_starts_with(config('app.url'), 'https://') ? 'Enviar el enlace por WhatsApp' : 'El enlace solo abrirá dentro de la red de la clínica hasta que la app tenga dirección pública' }}">WhatsApp</a>
            @endif
        @else
            <span class="text-gray-400">expirada</span>
        @endif
    </div>
@endif
