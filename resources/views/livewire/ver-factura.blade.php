<div>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <div class="mb-6">
            <a href="{{ route('facturas') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">&larr; Volver a facturación</a>
        </div>

        <div class="bg-white rounded-xl shadow-xs border overflow-hidden">
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
            @php
                $enProcesoSri = in_array($factura->estado_sri, ['enviado', 'error_envio', 'error_autorizacion'], true)
                    || in_array($factura->notaCredito?->estado_sri, ['enviado', 'error_envio', 'error_autorizacion'], true);
            @endphp
            @if ($factura->clave_acceso || $factura->estado_sri)
                <div class="p-6 border-b bg-blue-50" @if ($enProcesoSri) wire:poll.15s="actualizarEstado" @endif>
                    @if ($enProcesoSri)
                        <p class="text-xs text-indigo-700 mb-2">El SRI está procesando el comprobante. Esta sección se actualiza sola.</p>
                    @endif
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
                                        @case('enviado') bg-blue-100 text-blue-700 @break
                                        @case('rechazado')
                                        @case('no_autorizado')
                                        @case('error_envio')
                                        @case('error_autorizacion') bg-red-100 text-red-700 @break
                                        @default bg-gray-100 text-gray-600
                                    @endswitch">{{ $factura->estado_sri }}</span>
                            </div>
                        @endif
                        @if ($factura->fecha_autorizacion)
                            <div>
                                <span class="text-gray-500">Fecha autorización:</span>
                                <p class="text-xs text-gray-900 mt-0.5">{{ $factura->fecha_autorizacion->format('d/m/Y H:i') }}</p>
                            </div>
                        @endif
                    </div>
                    @if (!empty($factura->mensajes_sri))
                        <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 space-y-1">
                            <p class="font-semibold">Mensajes del SRI</p>
                            @foreach ($factura->mensajes_sri as $m)
                                <p>
                                    <span class="font-mono">[{{ $m['identificador'] ?? '' }}]</span>
                                    {{ $m['mensaje'] ?? '' }}
                                    @if (!empty($m['informacionAdicional'])) — {{ $m['informacionAdicional'] }} @endif
                                </p>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif

            {{-- Envío por correo al paciente --}}
            @php $ncAutorizada = $factura->notaCredito?->estado_sri === 'autorizado'; @endphp
            @if ($factura->estado_sri === 'autorizado')
                <div class="p-6 border-b">
                    <h3 class="font-semibold text-gray-700 mb-2">Envío por correo</h3>
                    <div class="text-xs text-gray-500 space-y-0.5 mb-3">
                        <p>Factura:
                            @if ($factura->email_enviado_at)
                                enviada a <strong>{{ $factura->email_enviado_a }}</strong> el {{ $factura->email_enviado_at->format('d/m/Y H:i') }}
                            @else
                                aún no enviada
                            @endif
                        </p>
                        @if ($ncAutorizada)
                            <p>Nota de crédito:
                                @if ($factura->notaCredito->email_enviado_at)
                                    enviada a <strong>{{ $factura->notaCredito->email_enviado_a }}</strong> el {{ $factura->notaCredito->email_enviado_at->format('d/m/Y H:i') }}
                                @else
                                    aún no enviada
                                @endif
                            </p>
                        @endif
                    </div>
                    @can('facturas.crear')
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input wire:model="correo_envio" type="email" maxlength="150"
                                class="flex-1 rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm"
                                placeholder="correo@paciente.com">
                            <button wire:click="enviarCorreo('factura')"
                                class="px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition">
                                {{ $factura->email_enviado_at ? 'Reenviar factura' : 'Enviar factura' }}
                            </button>
                            @if ($ncAutorizada)
                                <button wire:click="enviarCorreo('nota_credito')"
                                    class="px-4 py-2 bg-teal-600 text-white text-sm font-medium rounded-lg hover:bg-teal-700 transition">
                                    {{ $factura->notaCredito->email_enviado_at ? 'Reenviar nota de crédito' : 'Enviar nota de crédito' }}
                                </button>
                            @endif
                        </div>
                        @error('correo_envio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    @endcan
                </div>
            @endif

            {{-- Nota de crédito (anulación de factura autorizada) --}}
            @if ($factura->estado_sri === 'autorizado')
                @php $nc = $factura->notaCredito; @endphp
                <div class="p-6 border-b {{ $nc ? 'bg-orange-50' : '' }}">
                    <h3 class="font-semibold text-gray-700 mb-2">Nota de crédito</h3>

                    @if (!$nc)
                        @if ($factura->estado === 'pagada')
                            @can('facturas.anular')
                                <p class="text-xs text-gray-500 mb-3">Una factura autorizada por el SRI solo se anula emitiendo una nota de crédito electrónica por el valor total.</p>
                                <div class="flex flex-col sm:flex-row gap-2">
                                    <input wire:model="motivo_nota_credito" type="text" maxlength="300"
                                        class="flex-1 rounded-lg border-gray-300 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm"
                                        placeholder="Motivo (ej.: error en el cobro, servicio no prestado)">
                                    <button wire:click="emitirNotaCredito" wire:loading.attr="disabled" wire:target="emitirNotaCredito"
                                        wire:confirm="Se emitirá una nota de crédito por ${{ number_format($factura->total, 2) }} y se enviará al SRI. ¿Continuar?"
                                        class="px-5 py-2.5 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition disabled:opacity-50">
                                        <span wire:loading.remove wire:target="emitirNotaCredito">Emitir nota de crédito</span>
                                        <span wire:loading wire:target="emitirNotaCredito">Emitiendo...</span>
                                    </button>
                                </div>
                                @error('motivo_nota_credito') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @endcan
                        @endif
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                            <div><span class="text-gray-500">Número:</span> <span class="font-mono">{{ $nc->numero }}</span></div>
                            <div><span class="text-gray-500">Estado SRI:</span> <span class="font-medium">{{ $nc->estado_sri }}</span></div>
                            <div class="sm:col-span-2"><span class="text-gray-500">Motivo:</span> {{ $nc->motivo }}</div>
                            @if ($nc->clave_acceso)
                                <div class="sm:col-span-2"><span class="text-gray-500">Clave de acceso:</span>
                                    <span class="font-mono text-xs break-all">{{ $nc->clave_acceso }}</span></div>
                            @endif
                            @if ($nc->fecha_autorizacion)
                                <div><span class="text-gray-500">Autorizada:</span> {{ $nc->fecha_autorizacion->format('d/m/Y H:i') }}</div>
                            @endif
                        </div>

                        @if (!empty($nc->mensajes_sri))
                            <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 space-y-1">
                                @foreach ($nc->mensajes_sri as $m)
                                    <p><span class="font-mono">[{{ $m['identificador'] ?? '' }}]</span> {{ $m['mensaje'] ?? '' }}
                                        @if (!empty($m['informacionAdicional'])) — {{ $m['informacionAdicional'] }} @endif</p>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('notas-credito.pdf', $nc->id) }}" target="_blank"
                               class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition">PDF nota de crédito</a>
                            @if ($nc->estado_sri === 'autorizado' && $nc->xml_autorizado_path)
                                <a href="{{ route('notas-credito.xml', $nc->id) }}"
                                   class="px-4 py-2 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800 transition">XML autorizado</a>
                            @endif
                            @can('facturas.anular')
                                @if (in_array($nc->estado_sri, [...\App\Services\ComprobanteSriService::REGENERABLES, 'firmado'], true))
                                    <button wire:click="procesarNotaCredito" wire:loading.attr="disabled" wire:target="procesarNotaCredito"
                                        class="px-4 py-2 bg-orange-600 text-white text-sm font-medium rounded-lg hover:bg-orange-700 transition disabled:opacity-50">
                                        <span wire:loading.remove wire:target="procesarNotaCredito">{{ $nc->estado_sri === 'pendiente' ? 'Enviar al SRI' : 'Reintentar envío al SRI' }}</span>
                                        <span wire:loading wire:target="procesarNotaCredito">Enviando...</span>
                                    </button>
                                @elseif (in_array($nc->estado_sri, ['enviado', 'error_envio', 'error_autorizacion'], true))
                                    <button wire:click="autorizarNotaCredito" wire:loading.attr="disabled" wire:target="autorizarNotaCredito"
                                        class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition disabled:opacity-50">
                                        <span wire:loading.remove wire:target="autorizarNotaCredito">Consultar autorización ahora</span>
                                        <span wire:loading wire:target="autorizarNotaCredito">Consultando...</span>
                                    </button>
                                @endif
                            @endcan
                        </div>
                    @endif
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
                    @if ($factura->estado_sri === 'autorizado' && $factura->xml_autorizado_path)
                        <a href="{{ route('facturas.xml', $factura->id) }}"
                           class="px-5 py-2.5 bg-green-700 text-white text-sm font-medium rounded-lg hover:bg-green-800 transition">
                            Descargar XML autorizado
                        </a>
                    @endif
                    @can('facturas.crear')
                        @if ($factura->estado !== 'anulada')
                            @php $estadoSri = $factura->estado_sri ?? 'pendiente'; @endphp
                            @if (in_array($estadoSri, ['pendiente', 'xml_generado', 'firmado', 'rechazado', 'no_autorizado'], true))
                                <button wire:click="emitirSri" wire:loading.attr="disabled" wire:target="emitirSri"
                                    class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="emitirSri">{{ in_array($estadoSri, ['rechazado', 'no_autorizado'], true) ? 'Corregir y reenviar al SRI' : 'Emitir al SRI' }}</span>
                                    <span wire:loading wire:target="emitirSri">Enviando al SRI...</span>
                                </button>
                            @elseif (in_array($estadoSri, ['enviado', 'recibido', 'error_envio', 'error_autorizacion'], true))
                                <button wire:click="autorizarSri" wire:loading.attr="disabled" wire:target="autorizarSri"
                                    class="px-5 py-2.5 border border-indigo-600 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-50 transition disabled:opacity-50">
                                    <span wire:loading.remove wire:target="autorizarSri">Consultar autorización ahora</span>
                                    <span wire:loading wire:target="autorizarSri">Consultando...</span>
                                </button>
                            @endif
                        @endif
                    @endcan
                    @if ($factura->estado === 'pagada')
                        @can('facturas.anular')
                            @php $sriBloqueado = !in_array($factura->estado_sri, \App\Livewire\VerFactura::ESTADOS_SIN_ENVIAR, true); @endphp
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
