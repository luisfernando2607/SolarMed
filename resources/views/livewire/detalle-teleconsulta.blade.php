@php
    $p = $tc->paciente;
    $boton = 'inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition';
    $medicoAsignado = \App\Models\Medico::where('user_id', auth()->id())->value('id') === $tc->medico_id;
@endphp
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">
    <a href="{{ route('telemedicina') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
        Telemedicina
    </a>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">Videoconsulta · {{ $tc->fecha_hora->translatedFormat('l d/m/Y') }} a las {{ $tc->fecha_hora->format('H:i') }} · {{ $tc->duracion_min }} min</p>
                <h1 class="text-2xl font-bold text-gray-900 mt-1">
                    @can('pacientes.ver')
                        <a href="{{ route('pacientes.ver', $p) }}" wire:navigate class="hover:text-blue-700">{{ $p->nombre_completo }}</a>
                    @else
                        {{ $p->nombre_visible }}
                    @endcan
                </h1>
                <p class="text-sm text-gray-500 mt-1"><span class="font-mono">{{ $p->cedula }}</span> · {{ $tc->medico?->nombre_completo }}{{ $tc->medico?->especialidad ? ' · ' . $tc->medico->especialidad->nombre : '' }}</p>
            </div>
            <span class="self-start px-2.5 py-1 rounded-full text-xs font-medium {{ $tc->estadoTono() }}">{{ $tc->estadoTexto() }}</span>
        </div>
    </div>

    {{-- Paso 1: enlace para el paciente --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-900">1. Enlace para el paciente</h2>
        <p class="text-sm text-gray-500 mt-1">El paciente abre este enlace, acepta el consentimiento y entra a la videoconsulta entre las {{ $ventana[0]->format('H:i') }} y las {{ $ventana[1]->format('H:i') }} del {{ $ventana[0]->format('d/m') }}.</p>
        <div class="mt-3 flex flex-col sm:flex-row gap-2" x-data="{ copiado: false }">
            <input type="text" readonly value="{{ $tc->enlacePaciente() }}" class="flex-1 min-w-0 rounded-xl border-gray-200 bg-gray-50 text-xs font-mono text-gray-600">
            <button type="button" class="{{ $boton }}" @click="navigator.clipboard.writeText(@js($tc->enlacePaciente())); copiado = true; setTimeout(() => copiado = false, 2000)">
                <span x-text="copiado ? 'Copiado ✓' : 'Copiar enlace'">Copiar enlace</span>
            </button>
            @if ($enlaceWhatsapp)
                <a href="{{ $enlaceWhatsapp }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700">Enviar por WhatsApp</a>
            @endif
        </div>
        @if (!str_starts_with((string) config('app.url'), 'https://'))
            <p class="mt-2 text-xs text-amber-700">Mientras la app use una dirección local, este enlace solo abre dentro de la red de la clínica. Desde fuera, registre el consentimiento verbal (paso 2) y envíe al paciente directamente el enlace de la sala.</p>
        @endif
    </section>

    {{-- Paso 2: autorización y consentimiento (num. 7.2.2) --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-900">2. Autorización y consentimiento <span class="font-normal text-gray-400">(Norma Técnica de Telesalud, num. 7.2.2)</span></h2>
        @if ($tc->tieneConsentimiento())
            <p class="mt-2 text-sm text-emerald-700">
                Aceptado el {{ $tc->consentimiento_datos_at->format('d/m/Y H:i') }}
                {{ $tc->consentimiento_medio === 'verbal' ? '· de palabra, registrado por el profesional' : '· por el paciente en su enlace' }}
                @if ($tc->consentimiento_representante) · por su representante legal: {{ $tc->consentimiento_representante }} @endif
            </p>
        @else
            <p class="mt-1 text-sm text-amber-700">El paciente aún no lo acepta. Sin él no se puede iniciar la videoconsulta.</p>
            <details class="mt-3">
                <summary class="cursor-pointer text-sm font-medium text-blue-700">Ver el texto que acepta el paciente</summary>
                <div class="mt-3 rounded-xl bg-gray-50 p-4">
                    @include('partials.consentimiento-telesalud', ['medico' => $tc->medico?->nombre_completo, 'clinica' => $marca->nombre()])
                </div>
            </details>
            @if ($puedeAtender && $tc->abierta())
                <form wire:submit="registrarConsentimientoVerbal" class="mt-4 space-y-3 rounded-xl border border-gray-200 p-4">
                    <p class="text-sm text-gray-700">Si el paciente no puede usar el enlace, léale el texto y registre aquí su aceptación de palabra:</p>
                    <label class="flex items-start gap-2 text-sm text-gray-800">
                        <input type="checkbox" wire:model="confirmaVerbal" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Leí el texto al paciente y aceptó la atención por video y el tratamiento de sus datos.
                    </label>
                    @error('confirmaVerbal') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
                    <input wire:model="representante" type="text" maxlength="150" placeholder="Si aceptó el representante legal, escriba su nombre (opcional)" class="w-full rounded-xl border-gray-200 text-sm">
                    <button type="submit" class="{{ $boton }}">Registrar consentimiento verbal</button>
                </form>
            @endif
        @endif
    </section>

    {{-- Paso 3: videoconsulta --}}
    <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-900">3. Videoconsulta</h2>

        @if ($tc->plataforma === 'manual' && $tc->abierta())
            @canany(['turnos.gestionar', 'expediente.crear'])
                <form wire:submit="guardarSala" class="mt-3 flex flex-col sm:flex-row gap-2">
                    <input wire:model="sala_url" type="url" placeholder="https://… enlace de Zoom, Meet o Teams" class="flex-1 min-w-0 rounded-xl border-gray-200 text-sm">
                    <button type="submit" class="{{ $boton }}">Guardar enlace</button>
                </form>
                @error('sala_url') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            @endcanany
        @endif

        @if ($tc->abierta())
            @if ($puedeAtender)
                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($tc->tieneConsentimiento() && $tc->sala_url)
                        <a href="{{ $tc->sala_url }}" target="_blank" rel="noopener" wire:click="iniciar"
                           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-xs">
                            {{ $tc->estado === 'en_curso' ? 'Volver a la sala de video' : 'Iniciar videoconsulta' }}
                        </a>
                    @else
                        <span class="inline-flex items-center px-4 py-2 rounded-xl bg-gray-100 text-sm text-gray-400" title="Falta el consentimiento o el enlace de la sala">Iniciar videoconsulta</span>
                    @endif
                    @if ($tc->estado === 'programada')
                        <button wire:click="noAsistio" wire:confirm="¿Marcar que el paciente no se conectó?" class="{{ $boton }}">No se conectó</button>
                    @endif
                </div>
                <p class="mt-2 text-xs text-gray-500">La sala se abre en otra pestaña. La clínica no graba la videoconsulta.</p>
            @else
                <p class="mt-2 text-sm text-gray-500">La inicia {{ $tc->medico?->nombre_completo }}.</p>
            @endif
        @endif

        @if ($tc->estado === 'en_curso' && $puedeAtender)
            <div class="mt-5 grid sm:grid-cols-2 gap-4 border-t border-gray-100 pt-5">
                <div>
                    <p class="text-sm font-medium text-gray-900">Historia clínica</p>
                    @if ($tc->consulta)
                        <p class="text-sm text-emerald-700 mt-1">Consulta registrada ✓</p>
                    @elseif ($medicoAsignado)
                        <a href="{{ route('expedientes.crear', ['teleconsulta' => $tc->id]) }}" wire:navigate class="mt-2 {{ $boton }}">Registrar la consulta</a>
                    @endif
                </div>
                <form wire:submit="finalizar" class="space-y-2">
                    <p class="text-sm font-medium text-gray-900">Finalizar</p>
                    <select wire:model="resultado" class="w-full rounded-xl border-gray-200 text-sm">
                        @foreach (\App\Models\Teleconsulta::RESULTADOS as $clave => $texto)
                            <option value="{{ $clave }}">{{ $texto }}</option>
                        @endforeach
                    </select>
                    <textarea wire:model="observaciones" rows="2" maxlength="1000" placeholder="Observaciones técnicas (calidad del audio o video, cortes…)" class="w-full rounded-xl border-gray-200 text-sm"></textarea>
                    <button type="submit" class="{{ $boton }}">Finalizar teleconsulta</button>
                </form>
            </div>
        @endif

        @if (in_array($tc->estado, ['finalizada', 'no_asistio', 'cancelada'], true))
            <dl class="mt-3 grid sm:grid-cols-2 gap-3 text-sm">
                @if ($tc->inicio_at)<div><dt class="text-xs text-gray-400">Inicio</dt><dd>{{ $tc->inicio_at->format('d/m/Y H:i') }}</dd></div>@endif
                @if ($tc->fin_at)<div><dt class="text-xs text-gray-400">Fin</dt><dd>{{ $tc->fin_at->format('d/m/Y H:i') }}</dd></div>@endif
                @if ($tc->resultado_conexion)<div><dt class="text-xs text-gray-400">Conexión</dt><dd>{{ \App\Models\Teleconsulta::RESULTADOS[$tc->resultado_conexion] ?? $tc->resultado_conexion }}</dd></div>@endif
                @if ($tc->observaciones_tecnicas)<div class="sm:col-span-2"><dt class="text-xs text-gray-400">Observaciones técnicas</dt><dd>{{ $tc->observaciones_tecnicas }}</dd></div>@endif
                <div><dt class="text-xs text-gray-400">Historia clínica</dt><dd>
                    @if ($tc->consulta)
                        Consulta registrada ✓
                    @elseif ($tc->estado === 'finalizada' && $medicoAsignado)
                        <a href="{{ route('expedientes.crear', ['teleconsulta' => $tc->id]) }}" wire:navigate class="text-blue-700 underline">Registrar la consulta</a>
                    @else
                        —
                    @endif
                </dd></div>
            </dl>
        @endif
    </section>
</div>
