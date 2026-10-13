@php
    $estilos = [
        'ok' => ['bg-emerald-50 text-emerald-700', 'Bien', 'M4.5 12.75l6 6 9-13.5'],
        'aviso' => ['bg-amber-50 text-amber-700', 'Revisar', 'M12 9v3.75m0 3.75h.008v.008H12v-.008z'],
        'error' => ['bg-red-50 text-red-700', 'Atención', 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z'],
    ];
    $errores = collect($chequeos)->where('estado', 'error')->count();
    $avisos = collect($chequeos)->where('estado', 'aviso')->count();
    $boton = 'inline-flex items-center gap-2 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5" wire:poll.60s>

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Estado del sistema</h1>
            <p class="text-sm text-gray-500 mt-1">
                @if ($errores)
                    {{ $errores }} {{ $errores === 1 ? 'punto necesita' : 'puntos necesitan' }} atención{{ $avisos ? " y {$avisos} para revisar" : '' }}.
                @elseif ($avisos)
                    Funciona, con {{ $avisos }} {{ $avisos === 1 ? 'punto' : 'puntos' }} para revisar.
                @else
                    Todo funciona correctamente.
                @endif
                Se actualiza cada minuto.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button wire:click="respaldarAhora" wire:loading.attr="disabled" class="{{ $boton }}">
                <span wire:loading.remove wire:target="respaldarAhora">Respaldar ahora</span>
                <span wire:loading wire:target="respaldarAhora">Respaldando…</span>
            </button>
            <button wire:click="verificarAuditoria" wire:loading.attr="disabled" class="{{ $boton }}">Verificar auditoría</button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 divide-y divide-gray-100">
        @foreach ($chequeos as $clave => $c)
            @php [$tono, $etiqueta, $icono] = $estilos[$c['estado']]; @endphp
            <div class="flex items-start gap-4 px-5 py-4">
                <span class="flex items-center justify-center w-9 h-9 rounded-xl shrink-0 {{ $tono }}" title="{{ $etiqueta }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icono }}"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-sm font-semibold text-gray-900">{{ $c['titulo'] }}</p>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium {{ $tono }}">{{ $etiqueta }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-0.5">{{ $c['detalle'] }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @if ($clave === 'certificados')
                        <button wire:click="releerCertificados" class="text-xs font-medium text-gray-500 hover:text-gray-800" title="Volver a leer los certificados">Releer</button>
                    @endif
                    @isset($c['accion'])
                        <a href="{{ $c['accion'] }}" wire:navigate class="text-xs font-medium text-blue-700 hover:underline">Abrir</a>
                    @endisset
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-400">
        @foreach ($version as $k => $v)
            <span>{{ $k }}: <span class="text-gray-600">{{ $v }}</span></span>
        @endforeach
    </div>
</div>
