@php
    $tonos = [
        'eliminar' => 'bg-red-50 text-red-700', 'quitar_permiso' => 'bg-red-50 text-red-700',
        'editar' => 'bg-amber-50 text-amber-700', 'dar_permiso' => 'bg-amber-50 text-amber-700',
        'crear' => 'bg-emerald-50 text-emerald-700',
        'descargar' => 'bg-violet-50 text-violet-700', 'exportar' => 'bg-violet-50 text-violet-700',
        'enviar' => 'bg-teal-50 text-teal-700',
        'login_fallido' => 'bg-red-50 text-red-700', 'bloqueo' => 'bg-red-100 text-red-800',
        'login' => 'bg-gray-100 text-gray-600', 'logout' => 'bg-gray-100 text-gray-600',
        'cambiar_password' => 'bg-sky-50 text-sky-700', 'aceptar_confidencialidad' => 'bg-sky-50 text-sky-700',
    ];
    $campo = 'rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500';
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Registro de accesos</h1>
            <p class="text-sm text-gray-500 mt-1">Quién vio, descargó o modificó datos clínicos, y cada ingreso al sistema. Los registros no se pueden editar ni borrar (AM 5216-A, Art. 8).</p>
        </div>
        <a href="{{ $urlExportar }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-xs transition shrink-0">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Exportar CSV
        </a>
    </div>

    {{-- Integridad del registro --}}
    @php $ok = $integridad['integro'] ?? null; @endphp
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-2xl border px-5 py-4 {{ $ok === false ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-white' }}">
        <span class="flex items-center justify-center w-10 h-10 rounded-xl shrink-0 {{ $ok === false ? 'bg-red-100 text-red-700' : ($ok ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500') }}">
            @if ($ok === false)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            @endif
        </span>
        <div class="flex-1 text-sm">
            @if ($ok === null)
                <p class="font-medium text-gray-900">Integridad aún no verificada</p>
                <p class="text-gray-500">Cada registro se sella con SHA-256 encadenado al anterior. La verificación automática corre cada noche.</p>
            @elseif ($ok)
                <p class="font-medium text-gray-900">Registro íntegro · {{ number_format($integridad['total']) }} registros sellados</p>
                <p class="text-gray-500">Última verificación: {{ \Illuminate\Support\Carbon::parse($integridad['verificado_at'])->format('d/m/Y H:i') }}. Ningún registro fue modificado ni eliminado.</p>
            @else
                <p class="font-medium text-red-800">Se detectó una alteración a partir del registro #{{ $integridad['primer_error'] }}</p>
                <p class="text-red-700">Alguien modificó o borró registros directamente en la base de datos. Conserve los respaldos y notifique al responsable de seguridad.</p>
            @endif
        </div>
        <button wire:click="verificarIntegridad" wire:loading.attr="disabled" class="px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition shrink-0">
            <span wire:loading.remove wire:target="verificarIntegridad">Verificar ahora</span>
            <span wire:loading wire:target="verificarIntegridad">Verificando…</span>
        </button>
    </div>

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach ([
            ['Accesos a datos hoy', $kpis['accesos_hoy'], null],
            ['Pacientes consultados hoy', $kpis['pacientes_hoy'], null],
            ['Descargas y exportaciones', $kpis['descargas_7'], 'últimos 7 días'],
            ['Ingresos fallidos', $kpis['fallidos_7'], 'últimos 7 días'],
        ] as [$titulo, $valor, $nota])
            <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3.5">
                <p class="text-xs text-gray-500">{{ $titulo }}</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums {{ $titulo === 'Ingresos fallidos' && $valor > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ number_format($valor) }}</p>
                @if ($nota)<p class="text-[11px] text-gray-400">{{ $nota }}</p>@endif
            </div>
        @endforeach
    </div>

    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
        <input wire:model.live.debounce.300ms="paciente" type="search" class="lg:col-span-2 {{ $campo }}" placeholder="Paciente (nombre o cédula)">
        <select wire:model.live="usuario" class="{{ $campo }}">
            <option value="">Todos los usuarios</option>
            @foreach ($usuarios as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="accion" class="{{ $campo }}">
            <option value="">Todas las acciones</option>
            <option value="sesiones">Solo sesiones y cuentas</option>
            @foreach (\App\Models\RegistroAcceso::ACCIONES as $clave => $etiqueta)
                <option value="{{ $clave }}">{{ $etiqueta }}</option>
            @endforeach
        </select>
        <input wire:model.live="desde" type="date" title="Desde" class="{{ $campo }}">
        <div class="flex gap-2">
            <input wire:model.live="hasta" type="date" title="Hasta" class="flex-1 min-w-0 {{ $campo }}">
            <button wire:click="limpiar" class="px-3 text-sm text-gray-600 border border-gray-200 bg-white rounded-xl hover:bg-gray-50">Limpiar</button>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-xs text-gray-500 border-b border-gray-100">
                    <tr>
                        <th class="text-left font-medium px-5 py-3">Fecha</th>
                        <th class="text-left font-medium px-5 py-3">Usuario</th>
                        <th class="text-left font-medium px-5 py-3">Acción</th>
                        <th class="text-left font-medium px-5 py-3">Recurso</th>
                        <th class="text-left font-medium px-5 py-3">Paciente</th>
                        <th class="text-left font-medium px-5 py-3">Detalle</th>
                        <th class="text-left font-medium px-5 py-3 hidden lg:table-cell">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($registros as $r)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-600 tabular-nums">{{ $r->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-900">{{ $r->user?->name ?? '—' }}</td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $tonos[$r->accion] ?? 'bg-blue-50 text-blue-700' }}">{{ \App\Models\RegistroAcceso::ACCIONES[$r->accion] ?? $r->accion }}</span>
                            </td>
                            <td class="px-5 py-2.5 whitespace-nowrap text-gray-700">{{ str_replace('_', ' ', $r->recurso) }} @if ($r->recurso_id)<span class="text-gray-400">#{{ $r->recurso_id }}</span>@endif</td>
                            <td class="px-5 py-2.5 whitespace-nowrap">
                                @if ($r->paciente)
                                    <a href="{{ route('pacientes.ver', $r->paciente) }}" wire:navigate class="hover:text-blue-700">{{ $r->paciente->nombre_completo }}</a>
                                    <span class="text-gray-400 text-xs font-mono">{{ $r->paciente->cedula }}</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-2.5 text-gray-600 max-w-xs truncate" title="{{ $r->detalle }}">{{ $r->detalle }}</td>
                            <td class="px-5 py-2.5 text-gray-400 font-mono text-xs hidden lg:table-cell">{{ $r->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No hay registros con estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($registros->hasPages())
            <div class="px-5 py-3 border-t border-gray-100">{{ $registros->links() }}</div>
        @endif
    </div>
    <p class="text-xs text-gray-400">{{ number_format($registros->total()) }} registros con los filtros actuales. La exportación CSV incluye el sello SHA-256 de cada registro para que el auditor pueda comprobarlo.</p>
</div>
