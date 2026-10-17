<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
    <div class="flex flex-wrap items-end justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Panel de auditoría</h1>
            <p class="text-sm text-gray-500 mt-0.5">Calidad de la historia clínica, planes de acción, incidentes, solicitudes y bitácora de accesos en un solo lugar.</p>
        </div>
        <div class="flex items-end gap-2">
            <div>
                <label class="form-label" for="desde">Desde</label>
                <input type="date" id="desde" wire:model.live="desde" class="form-input py-2">
            </div>
            <div>
                <label class="form-label" for="hasta">Hasta</label>
                <input type="date" id="hasta" wire:model.live="hasta" class="form-input py-2">
            </div>
        </div>
    </div>

    {{-- Calidad de la HC (form.002) --}}
    <section class="mb-6">
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400">Calidad de la historia clínica · form. 002</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('auditoria.informe-periodo', ['desde' => $desde, 'hasta' => $hasta]) }}" target="_blank" class="text-sm font-medium text-blue-600 hover:text-blue-700">Informe del período (PDF)</a>
                <a href="{{ route('auditoria.calidad') }}" wire:navigate class="text-sm font-medium text-blue-600 hover:text-blue-700">Auditar historias →</a>
            </div>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @php
                $pc = $calidad['promedio'];
                $tono = $pc === null ? 'text-gray-400' : ($pc >= 90 ? 'text-green-600' : ($pc >= 75 ? 'text-amber-600' : 'text-red-600'));
            @endphp
            <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-5">
                <p class="text-sm text-gray-500">Cumplimiento promedio</p>
                <p class="text-3xl font-bold {{ $tono }} mt-1">{{ $pc === null ? '—' : $pc.'%' }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $calidad['evaluadas'] }} consultas evaluadas</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-5">
                <p class="text-sm text-gray-500">Historias ≥ 90%</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $calidad['buenasPct'] }}%</p>
                <p class="text-xs text-gray-400 mt-1">{{ $calidad['buenas'] }} de {{ $calidad['evaluadas'] }}</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-5">
                <p class="text-sm text-gray-500">Auditorías manuales</p>
                <p class="text-3xl font-bold text-gray-900 mt-1">{{ $calidad['auditadas'] }}</p>
                <p class="text-xs text-gray-400 mt-1">cerradas en el período</p>
            </div>
            <a href="{{ route('auditoria.planes') }}" wire:navigate class="bg-white rounded-xl shadow-sm border p-5 transition hover:shadow-md {{ $planes['vencidos'] ? 'border-red-200' : 'border-gray-200/70' }}">
                <p class="text-sm text-gray-500">Planes de acción abiertos</p>
                <p class="text-3xl font-bold {{ $planes['vencidos'] ? 'text-red-600' : 'text-gray-900' }} mt-1">{{ $planes['abiertos'] }}</p>
                <p class="text-xs mt-1 {{ $planes['vencidos'] ? 'text-red-500 font-medium' : 'text-gray-400' }}">{{ $planes['vencidos'] ? $planes['vencidos'].' vencido(s)' : 'ninguno vencido' }}</p>
            </a>
        </div>
    </section>

    {{-- Seguridad y derechos del paciente --}}
    <section class="mb-6">
        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Seguridad del paciente y derechos de acceso</h2>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <a href="{{ route('auditoria.incidentes') }}" wire:navigate class="bg-white rounded-xl shadow-sm border p-5 transition hover:shadow-md {{ $incidentes['por_notificar'] ? 'border-red-200' : 'border-gray-200/70' }}">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-800">Incidentes de seguridad</p>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $incidentes['abiertos'] }} <span class="text-sm font-normal text-gray-400">abiertos</span></p>
                @if ($incidentes['por_notificar'])
                    <p class="text-xs text-red-600 font-medium mt-1">{{ $incidentes['por_notificar'] }} por notificar a la autoridad</p>
                @else
                    <p class="text-xs text-gray-400 mt-1">sin notificaciones pendientes</p>
                @endif
            </a>
            <a href="{{ route('auditoria.solicitudes') }}" wire:navigate class="bg-white rounded-xl shadow-sm border p-5 transition hover:shadow-md {{ $solicitudes['vencidas'] ? 'border-red-200' : 'border-gray-200/70' }}">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-800">Solicitudes de información</p>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $solicitudes['pendientes'] }} <span class="text-sm font-normal text-gray-400">pendientes</span></p>
                @if ($solicitudes['vencidas'])
                    <p class="text-xs text-red-600 font-medium mt-1">{{ $solicitudes['vencidas'] }} fuera de plazo legal</p>
                @else
                    <p class="text-xs text-gray-400 mt-1">dentro de plazo</p>
                @endif
            </a>
            <a href="{{ route('auditoria') }}" wire:navigate class="bg-white rounded-xl shadow-sm border p-5 transition hover:shadow-md border-gray-200/70">
                <div class="flex items-center justify-between">
                    <p class="font-semibold text-gray-800">Bitácora de accesos</p>
                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2 text-center">
                    <div><p class="text-xl font-bold text-gray-900">{{ $accesos['hoy'] }}</p><p class="text-[11px] text-gray-400 leading-tight">accesos hoy</p></div>
                    <div><p class="text-xl font-bold text-gray-900">{{ $accesos['descargas_7'] }}</p><p class="text-[11px] text-gray-400 leading-tight">descargas 7d</p></div>
                    <div><p class="text-xl font-bold {{ $accesos['fallidos_7'] ? 'text-amber-600' : 'text-gray-900' }}">{{ $accesos['fallidos_7'] }}</p><p class="text-[11px] text-gray-400 leading-tight">fallidos 7d</p></div>
                </div>
            </a>
        </div>
    </section>

    {{-- Integridad de la bitácora --}}
    <section class="bg-white rounded-xl shadow-sm border border-gray-200/70 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                @php $ok = $integridad['ok'] ?? null; @endphp
                <span class="flex-none w-10 h-10 rounded-xl flex items-center justify-center {{ $ok === null ? 'bg-gray-100 text-gray-400' : ($ok ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                </span>
                <div>
                    <p class="font-semibold text-gray-800">
                        @if ($ok === null) Integridad de la bitácora aún no verificada
                        @elseif ($ok) Bitácora íntegra
                        @else Se detectó una alteración en la bitácora @endif
                    </p>
                    <p class="text-sm text-gray-500">Cada registro se sella con SHA-256 encadenado al anterior (AM 5216-A, Art. 8). La verificación automática corre cada noche.</p>
                </div>
            </div>
            @can('auditoria.gestionar')
                <button wire:click="verificarIntegridad" class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-300 rounded-lg font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-xs hover:bg-gray-50 transition">Verificar ahora</button>
            @endcan
        </div>
    </section>
</div>
