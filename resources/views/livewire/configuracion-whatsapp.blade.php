@php
    $campo = 'w-full rounded-lg border-gray-300 shadow-xs text-sm';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
@endphp
<section id="whatsapp" class="bg-white rounded-xl shadow-xs border p-6 scroll-mt-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
        <h2 class="text-lg font-semibold text-gray-900">WhatsApp (API oficial de Meta)</h2>
        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $activo && $phone_number_id && $tieneToken ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
            {{ $activo && $phone_number_id && $tieneToken ? 'Activo' : 'Desactivado' }}
        </span>
    </div>
    <p class="text-sm text-gray-500 mb-5">Envía recordatorios, confirmaciones y encuestas desde el número de la clínica, sin que nadie tenga que pulsar "enviar". Mientras esté desactivado, los botones de WhatsApp abren el mensaje ya escrito como hasta ahora.</p>

    <form wire:submit="guardar" class="space-y-5">
        <label class="flex items-center gap-2 text-sm text-gray-800">
            <input type="checkbox" wire:model="activo" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            Enviar los mensajes automáticamente por la API de WhatsApp
        </label>
        @error('activo') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="{{ $etiqueta }}">Identificador del número (Phone number ID)</label>
                <input wire:model="phone_number_id" type="text" inputmode="numeric" class="{{ $campo }} font-mono">
                @error('phone_number_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $etiqueta }}">ID de la cuenta (WABA) <span class="font-normal text-gray-400">(opcional)</span></label>
                <input wire:model="waba_id" type="text" inputmode="numeric" class="{{ $campo }} font-mono">
                @error('waba_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $etiqueta }}">Versión de la API</label>
                <input wire:model="api_version" type="text" class="{{ $campo }} font-mono">
                @error('api_version') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="{{ $etiqueta }}">Idioma de las plantillas</label>
                <input wire:model="idioma" type="text" class="{{ $campo }} font-mono">
                @error('idioma') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $etiqueta }}">Token de acceso permanente
                    @if ($tieneToken)<span class="ml-1 text-emerald-700 font-normal">configurado ✓ · escriba uno nuevo solo para reemplazarlo</span>@endif
                </label>
                <input wire:model="token_nuevo" type="password" autocomplete="off" placeholder="{{ $tieneToken ? '••••••••••••' : 'Token de usuario del sistema de Meta' }}" class="{{ $campo }} font-mono">
                @error('token_nuevo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="{{ $etiqueta }}">App secret (para validar los avisos de Meta)
                    @if ($tieneSecreto)<span class="ml-1 text-emerald-700 font-normal">configurado ✓ · escriba uno nuevo solo para reemplazarlo</span>@endif
                </label>
                <input wire:model="app_secret_nuevo" type="password" autocomplete="off" placeholder="{{ $tieneSecreto ? '••••••••••••' : 'Clave secreta de la aplicación de Meta' }}" class="{{ $campo }} font-mono">
                @error('app_secret_nuevo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <p class="text-sm font-semibold text-gray-900 mb-2">Mensajes y plantillas</p>
            <p class="text-xs text-gray-500 mb-3">Cree estas plantillas en Meta Business Manager (WhatsApp → Plantillas de mensajes), categoría <strong>Utilidad</strong>, idioma español, con las variables en este orden. Escriba aquí el nombre exacto con que las creó.</p>
            <div class="space-y-3">
                @foreach (\App\Livewire\ConfiguracionWhatsapp::PLANTILLAS as $uso => $p)
                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-900">
                                <input type="checkbox" wire:model="usar.{{ $uso }}" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                {{ $p['titulo'] }}
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">Nombre en Meta</span>
                                <input wire:model="plantillas.{{ $uso }}" type="text" class="w-56 rounded-lg border-gray-300 shadow-xs text-sm font-mono">
                            </div>
                        </div>
                        @error("plantillas.{$uso}") <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="mt-3 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700 select-all">{{ $p['texto'] }}</p>
                        @php
                            $variables = collect($p['variables'])->map(fn ($v, $i) => str_repeat('{', 2) . ($i + 1) . str_repeat('}', 2) . ' ' . $v)->implode(' · ');
                        @endphp
                        <p class="mt-1.5 text-xs text-gray-500">
                            Variables: {{ $variables }}
                            @if ($p['botones']) · Botones de respuesta rápida, en este orden: <strong>{{ implode(' y ', $p['botones']) }}</strong>@endif
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-xs">Guardar WhatsApp</button>
        </div>
    </form>

    <div class="mt-6 grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-gray-200 p-4 text-sm">
            <p class="font-semibold text-gray-900 mb-2">Webhook (avisos de entrega y respuestas)</p>
            <p class="text-xs text-gray-500 mb-1">URL de devolución de llamada</p>
            <p class="font-mono text-xs bg-gray-50 rounded-lg px-3 py-2 break-all select-all">{{ $urlWebhook }}</p>
            <p class="text-xs text-gray-500 mt-3 mb-1">Token de verificación</p>
            <p class="font-mono text-xs bg-gray-50 rounded-lg px-3 py-2 break-all select-all">{{ $verifyToken }}</p>
            <p class="text-xs text-gray-500 mt-3">En Meta suscríbase al campo <strong>messages</strong>.</p>
            @unless ($esHttps)
                <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-900">Meta solo acepta webhooks con dirección pública HTTPS. Sin ella los mensajes igual se envían, pero no llegan los avisos de entrega, lectura ni las respuestas (Confirmar / Cancelar).</p>
            @endunless
        </div>

        <div class="rounded-xl border border-gray-200 p-4 text-sm">
            <p class="font-semibold text-gray-900 mb-2">Enviar prueba</p>
            <p class="text-xs text-gray-500 mb-3">Envía la plantilla <span class="font-mono">hello_world</span> que Meta crea en toda cuenta, para comprobar el número y el token. Guarde la configuración antes.</p>
            <form wire:submit="probar" class="flex gap-2">
                <input wire:model="telefono_prueba" type="tel" placeholder="0991234567" class="{{ $campo }}">
                <button type="submit" wire:loading.attr="disabled" class="shrink-0 px-4 py-2 rounded-lg border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <span wire:loading.remove wire:target="probar">Enviar prueba</span><span wire:loading wire:target="probar">Enviando…</span>
                </button>
            </form>
            @error('telefono_prueba') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            @if ($resultadoPrueba)
                <p class="mt-3 rounded-lg px-3 py-2 text-xs {{ $resultadoPrueba['ok'] ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-800' }}">{{ $resultadoPrueba['texto'] }}</p>
            @endif
        </div>
    </div>
</section>
