<div x-data x-on:notify.window="Swal.fire({
    icon: $event.detail.type,
    title: $event.detail.type === 'success' ? 'Guardado' : 'Error',
    text: $event.detail.message,
    timer: 3000,
    showConfirmButton: false,
    toast: true,
    position: 'top-end'
})">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <a href="{{ route('facturas') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver a facturación</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            {{-- Header --}}
            <div class="p-6 border-b bg-gray-50">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $factura->numero_factura }}</h1>
                        <p class="text-sm text-gray-500">{{ $factura->fecha ? $factura->fecha->format('d/m/Y') : $factura->created_at->format('d/m/Y') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-gray-900">${{ number_format($factura->total, 2) }}</p>
                        @if ($factura->estado === 'pagada')
                            <span class="inline-flex mt-1 px-3 py-1 bg-green-100 text-green-700 text-sm font-medium rounded-full">Pagada</span>
                        @else
                            <span class="inline-flex mt-1 px-3 py-1 bg-red-100 text-red-700 text-sm font-medium rounded-full">Anulada</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Información --}}
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm border-b">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Paciente</h3>
                    <p class="text-gray-900">{{ $factura->paciente?->nombre_completo ?? '--' }}</p>
                    <p class="text-gray-500">C.I.: {{ $factura->paciente?->cedula ?? '--' }}</p>
                    <p class="text-gray-500">Tel: {{ $factura->paciente?->telefono ?? '--' }}</p>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Factura</h3>
                    <p class="text-gray-500">Forma de pago: <span class="capitalize font-medium text-gray-900">{{ $factura->forma_pago }}</span></p>
                    @if ($factura->referencia_pago)
                        <p class="text-gray-500">Referencia: <span class="font-medium text-gray-900">{{ $factura->referencia_pago }}</span></p>
                    @endif
                    <p class="text-gray-500">Emitida por: <span class="font-medium text-gray-900">{{ $factura->user?->name ?? '--' }}</span></p>
                </div>
            </div>

            {{-- Items --}}
            <div class="p-6 border-b">
                <h3 class="font-semibold text-gray-700 mb-3">Servicios facturados</h3>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-3 py-2">Descripción</th>
                            <th class="text-center px-3 py-2 w-20">Cant.</th>
                            <th class="text-right px-3 py-2 w-28">P. Unit.</th>
                            <th class="text-right px-3 py-2 w-28">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($factura->items as $item)
                            <tr>
                                <td class="px-3 py-2">{{ $item->descripcion }}</td>
                                <td class="px-3 py-2 text-center">{{ $item->cantidad }}</td>
                                <td class="px-3 py-2 text-right">${{ number_format($item->precio_unitario, 2) }}</td>
                                <td class="px-3 py-2 text-right font-medium">${{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totales --}}
            <div class="p-6 space-y-1 text-sm border-b">
                <div class="flex justify-between max-w-xs ml-auto">
                    <span class="text-gray-500">Subtotal:</span>
                    <span>${{ number_format($factura->subtotal, 2) }}</span>
                </div>
                @if ($factura->descuento > 0)
                    <div class="flex justify-between max-w-xs ml-auto">
                        <span class="text-gray-500">Descuento:</span>
                        <span class="text-red-600">-${{ number_format($factura->descuento, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between max-w-xs ml-auto text-base font-bold pt-1 border-t">
                    <span>TOTAL:</span>
                    <span>${{ number_format($factura->total, 2) }}</span>
                </div>
            </div>

            {{-- Observaciones --}}
            @if ($factura->observaciones)
                <div class="p-6 text-sm">
                    <h3 class="font-semibold text-gray-700 mb-1">Observaciones</h3>
                    <p class="text-gray-600">{{ $factura->observaciones }}</p>
                </div>
            @endif

            {{-- Estado SRI --}}
            @if ($factura->clave_acceso || $factura->estado_sri)
                <div class="p-6 border-b bg-blue-50">
                    <h3 class="font-semibold text-gray-700 mb-2">Facturación electrónica SRI</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                        @if ($factura->clave_acceso)
                            <div>
                                <span class="text-gray-500">Clave de acceso:</span>
                                <p class="font-mono text-xs text-gray-900 break-all mt-0.5">{{ $factura->clave_acceso }}</p>
                            </div>
                        @endif
                        @if ($factura->numero_autorizacion)
                            <div>
                                <span class="text-gray-500">Nro. autorización:</span>
                                <p class="font-mono text-xs text-gray-900 break-all mt-0.5">{{ $factura->numero_autorizacion }}</p>
                            </div>
                        @endif
                        @if ($factura->estado_sri)
                            <div>
                                <span class="text-gray-500">Estado SRI:</span>
                                <span class="ml-1 inline-flex px-2 py-0.5 text-xs font-medium rounded-full
                                    @switch($factura->estado_sri)
                                        @case('autorizado') bg-green-100 text-green-700 @break
                                        @case('recibido') bg-blue-100 text-blue-700 @break
                                        @case('firmado') bg-yellow-100 text-yellow-700 @break
                                        @case('xml_generado') bg-gray-100 text-gray-600 @break
                                        @case('rechazado') bg-red-100 text-red-700 @break
                                        @default bg-gray-100 text-gray-600
                                    @endswitch">{{ $factura->estado_sri }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Acciones --}}
                <div class="p-6 border-t bg-gray-50 flex flex-wrap gap-3 justify-end">
                    <a href="{{ route('facturas.pdf', $factura->id) }}" target="_blank"
                       class="px-5 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">
                        Descargar PDF
                    </a>
                    <a href="{{ route('facturas.pdf', $factura->id) }}" target="_blank"
                       onclick="window.open(this.href, '_blank', 'width=400,height=600'); return false;"
                       class="px-5 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                        Imprimir ticket
                    </a>
                    {{-- SRI actions --}}
                    @can('facturas.crear')
                        @if (!$factura->xml_enviado_path)
                            <button wire:click="generarXml"
                                class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                                Generar XML SRI
                            </button>
                        @elseif (!$factura->xml_autorizado_path)
                            <button wire:click="firmarXml"
                                class="px-5 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 transition">
                                Firmar XML
                            </button>
                        @elseif ($factura->estado_sri === 'firmado' || $factura->estado_sri === 'xml_generado')
                            <button wire:click="enviarSri" wire:loading.attr="disabled" wire:target="enviarSri"
                                class="px-5 py-2.5 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="enviarSri">Enviar al SRI</span>
                                <span wire:loading wire:target="enviarSri">Enviando...</span>
                            </button>
                        @elseif ($factura->estado_sri === 'enviado' || $factura->estado_sri === 'recibido')
                            <button wire:click="autorizarSri" wire:loading.attr="disabled" wire:target="autorizarSri"
                                class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                <span wire:loading.remove wire:target="autorizarSri">Autorizar SRI</span>
                                <span wire:loading wire:target="autorizarSri">Autorizando...</span>
                            </button>
                        @endif
                    @endcan
                    @if ($factura->estado === 'pagada')
                        @can('facturas.anular')
                            @php $sriBloqueado = $factura->estado_sri && $factura->estado_sri !== 'pendiente'; @endphp
                            <button x-data
                                x-on:click="{{ $sriBloqueado ? 'Swal.fire({icon: \'info\', title: \'Bloqueado por SRI\', text: \'Esta factura ya fue enviada al SRI y no puede ser anulada.\'})' : 'Swal.fire({icon: \'warning\', title: \'¿Anular factura?\', text: \'' . $factura->numero_factura . '. Esta acción no se puede deshacer.\', showCancelButton: true, confirmButtonColor: \'#dc2626\', cancelButtonColor: \'#6b7280\', confirmButtonText: \'Sí, anular\', cancelButtonText: \'Cancelar\'}).then((result) => { if (result.isConfirmed) $wire.anular() })' }}"
                                class="px-5 py-2.5 {{ $sriBloqueado ? 'bg-gray-400 cursor-not-allowed' : 'bg-red-600 hover:bg-red-700' }} text-white text-sm font-medium rounded-lg transition inline-flex items-center gap-1.5">
                                @if ($sriBloqueado)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                @endif
                                {{ $sriBloqueado ? 'Enviada al SRI' : 'Anular factura' }}
                            </button>
                        @endcan
                    @endif
                </div>
        </div>
    </div>
</div>
