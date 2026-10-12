<div class="space-y-4" wire:poll.60s>
    @if ($hoy || $sri)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
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

    @if ($embarazos && ($embarazos['activos'] || $embarazos['atencion']->isNotEmpty()))
        <div class="bg-white border rounded-xl p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-800">Embarazos que requieren atención</h3>
                <span class="text-xs text-gray-500">{{ $embarazos['activos'] }} embarazos activos</span>
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
