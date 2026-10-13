<div class="space-y-4" wire:poll.60s>
    @if ($enLinea)
        <a href="{{ route('agenda') }}" wire:navigate class="flex items-center gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 hover:bg-sky-100 transition">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
            <span class="flex-1">
                <span class="font-semibold">{{ $enLinea['total'] }} {{ $enLinea['total'] === 1 ? 'cita en línea' : 'citas en línea' }} por confirmar</span>
                <span class="text-sky-700"> · la próxima el {{ \Illuminate\Support\Carbon::parse($enLinea['proxima'])->format('d/m H:i') }}</span>
            </span>
            <span class="font-medium">Revisar →</span>
        </a>
    @endif
    @if ($hoy || $sri)
        <div class="grid grid-cols-2 {{ $hoy && $sri ? 'lg:grid-cols-4' : 'lg:grid-cols-2' }} gap-4">
            @if ($hoy)
                <a href="{{ route('agenda') }}" wire:navigate class="bg-white border rounded-xl p-4 hover:shadow-sm transition">
                    <p class="text-xs text-gray-500">Citas de hoy</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $hoy['citas'] }}</p>
                    <p class="text-xs {{ $hoy['sin_confirmar'] ? 'text-amber-700' : 'text-gray-400' }}">{{ $hoy['sin_confirmar'] }} sin confirmar</p>
                </a>
                <a href="{{ route('sala-espera') }}" wire:navigate class="bg-white border rounded-xl p-4 hover:shadow-sm transition">
                    <p class="text-xs text-gray-500">En sala de espera</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $hoy['esperando'] }}</p>
                    <p class="text-xs text-gray-400">{{ $hoy['en_atencion'] }} en atención</p>
                </a>
            @endif
            @if ($sri)
                <a href="{{ route('facturas') }}" wire:navigate class="bg-white border rounded-xl p-4 hover:shadow-sm transition">
                    <p class="text-xs text-gray-500">Facturas sin emitir al SRI</p>
                    <p class="text-2xl font-bold {{ $sri['sin_emitir'] ? 'text-amber-700' : 'text-gray-900' }}">{{ $sri['sin_emitir'] }}</p>
                    <p class="text-xs text-gray-400">{{ $sri['en_proceso'] }} en proceso de autorización</p>
                </a>
                <div class="bg-white border rounded-xl p-4 {{ $sri['con_problema']->isNotEmpty() ? 'border-red-300 bg-red-50' : '' }}">
                    <p class="text-xs text-gray-500">Devueltas / no autorizadas</p>
                    <p class="text-2xl font-bold {{ $sri['con_problema']->isNotEmpty() ? 'text-red-700' : 'text-gray-900' }}">{{ $sri['con_problema']->count() }}</p>
                    @foreach ($sri['con_problema'] as $f)
                        <a href="{{ route('facturas.ver', $f->id) }}" wire:navigate class="block text-xs text-red-700 hover:underline font-mono">{{ $f->numero_factura }}</a>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if ($inventario)
        <a href="{{ route('inventario') }}" wire:navigate
           class="bg-white border rounded-xl p-4 flex flex-wrap items-center gap-x-6 gap-y-2 hover:bg-gray-50 transition {{ $inventario['vencidos'] ? 'border-red-300' : '' }}">
            <h3 class="font-semibold text-gray-800 text-sm">Inventario</h3>
            @if ($inventario['stock_bajo'])
                <span class="text-sm text-amber-800">{{ $inventario['stock_bajo'] }} {{ $inventario['stock_bajo'] === 1 ? 'producto' : 'productos' }} con stock bajo</span>
            @endif
            @if ($inventario['por_vencer'])
                <span class="text-sm text-amber-800">{{ $inventario['por_vencer'] }} {{ $inventario['por_vencer'] === 1 ? 'lote por vencer' : 'lotes por vencer' }}</span>
            @endif
            @if ($inventario['vencidos'])
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ $inventario['vencidos'] }} {{ $inventario['vencidos'] === 1 ? 'lote vencido' : 'lotes vencidos' }} con existencias</span>
            @endif
        </a>
    @endif

    @if ($cumplimiento)
        <div class="bg-white border rounded-xl p-4 flex flex-wrap items-center gap-x-6 gap-y-2 {{ $cumplimiento['vencidas'] ? 'border-red-300' : '' }}">
            <h3 class="font-semibold text-gray-800 text-sm">Plazos legales</h3>
            @if ($cumplimiento['solicitudes'])
                <a href="{{ route('auditoria.solicitudes') }}" wire:navigate class="text-sm text-gray-700 hover:underline">
                    {{ $cumplimiento['solicitudes'] }} {{ $cumplimiento['solicitudes'] === 1 ? 'solicitud pendiente' : 'solicitudes pendientes' }}
                    @if ($cumplimiento['vencidas'])
                        <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">{{ $cumplimiento['vencidas'] }} fuera de plazo</span>
                    @endif
                </a>
            @endif
            @if ($cumplimiento['incidentes'])
                <a href="{{ route('auditoria.incidentes') }}" wire:navigate class="text-sm text-amber-800 hover:underline">
                    {{ $cumplimiento['incidentes'] }} {{ $cumplimiento['incidentes'] === 1 ? 'incidente por notificar' : 'incidentes por notificar' }} a la autoridad
                </a>
            @endif
        </div>
    @endif

    @if ($embarazos && ($embarazos['activos'] || $embarazos['atencion']->isNotEmpty()))
        <div class="bg-white border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">Embarazos que requieren atención</h3>
                <span class="text-xs text-gray-500">{{ $embarazos['activos'] }} {{ $embarazos['activos'] === 1 ? 'embarazo activo' : 'embarazos activos' }}</span>
            </div>
            @forelse ($embarazos['atencion'] as $item)
                <a href="{{ route('prenatal', $item['embarazo']->paciente_id) }}" wire:navigate
                   class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2 border-t first:border-t-0 hover:bg-gray-50 -mx-2 px-2 rounded">
                    <span class="font-medium text-gray-900">{{ $item['embarazo']->paciente?->nombre_completo }}</span>
                    <span class="text-xs text-pink-700">{{ $item['eg']['semanas'] }}+{{ $item['eg']['dias'] }} sem</span>
                    @foreach ($item['motivos'] as $m)
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $m['nivel'] === 'alta' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">{{ $m['texto'] }}</span>
                    @endforeach
                </a>
            @empty
                <p class="text-sm text-gray-400">Sin alertas: todos los embarazos activos están al día.</p>
            @endforelse
        </div>
    @endif

    @if ($facturacion && $facturacion->isNotEmpty())
        <div class="bg-white border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">Facturación de {{ now()->translatedFormat('F') }} por médico</h3>
                <a href="{{ route('reportes.ventas') }}" wire:navigate class="text-xs text-blue-600 hover:underline">Ver reporte</a>
            </div>
            @php $maximo = max(1, (float) $facturacion->max('monto')); @endphp
            <div class="space-y-2">
                @foreach ($facturacion as $fila)
                    <div class="grid grid-cols-12 items-center gap-3 text-sm">
                        <span class="col-span-4 truncate text-gray-700">{{ $fila->medico?->nombre_completo ?? '—' }}</span>
                        <div class="col-span-5 bg-gray-100 rounded h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded" style="width: {{ round((float) $fila->monto / $maximo * 100) }}%"></div>
                        </div>
                        <span class="col-span-3 text-right tabular-nums"><strong>${{ number_format($fila->monto, 2) }}</strong> <span class="text-xs text-gray-500">({{ $fila->cantidad }})</span></span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
