@php
    $campo = 'w-full rounded-xl border-gray-200 bg-white shadow-xs text-sm focus:border-blue-500 focus:ring-blue-500';
    $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
    $paciente = $turno->paciente;
    $edad = \App\Models\Receta::edadTexto($paciente?->fecha_nacimiento);
    $avisos = $this->avisos;
    $sugeridos = $this->sugeridos;
    $hayAntimicrobiano = collect($items)->contains(fn ($i) => !empty($i['antimicrobiano']));
@endphp
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-5">

    <div>
        <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Volver a la atención
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">Nueva receta médica</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $paciente?->nombre_completo ?? $turno->nombre_temporal }}{{ $edad ? ' · ' . $edad : '' }} · Datos mínimos de la Resolución ACESS-2023-0030, Art. 5. Vigencia: {{ \App\Models\Receta::VIGENCIA_CONSULTA_EXTERNA }} días.</p>
    </div>

    @if (session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form wire:submit="guardar" class="space-y-5">

        {{-- Datos del paciente y diagnóstico --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Paciente y diagnóstico</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="{{ $etiqueta }}">Diagnóstico *</label>
                    <input wire:model="diagnostico" type="text" class="{{ $campo }}" placeholder="Diagnóstico del paciente">
                    @error('diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="relative" x-data="{ open: false }">
                    <label class="{{ $etiqueta }}">Código CIE-10 *</label>
                    <input wire:model.live.debounce.200ms="cie10Search" type="text" class="{{ $campo }}" placeholder="Código o descripción…" autocomplete="off"
                           x-on:focus="open = true" x-on:click.away="open = false">
                    @if ($cie10Results)
                        <div x-show="open" class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                            @foreach ($cie10Results as $r)
                                <button type="button" wire:click="selectCie10({{ \Illuminate\Support\Js::from($r['codigo']) }})" x-on:click="open = false"
                                        class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-b-0">
                                    <span class="font-medium text-gray-900 font-mono">{{ $r['codigo'] }}</span>
                                    <span class="text-gray-500 ml-2">{{ $r['descripcion'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if ($codigo_cie10)
                        <p class="mt-1.5 text-xs text-gray-500"><span class="px-1.5 py-0.5 rounded-sm bg-blue-50 text-blue-700 font-mono">{{ $codigo_cie10 }}</span> {{ $cie10Descripcion }}</p>
                    @endif
                    @error('codigo_cie10') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Ciudad *</label>
                    <input wire:model="ciudad" type="text" class="{{ $campo }}" placeholder="Ciudad de prescripción">
                    @error('ciudad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="{{ $etiqueta }}">Antecedentes de alergias *</label>
                    <input wire:model="alergias" type="text" class="{{ $campo }}" placeholder='Escriba "No refiere" si no tiene'>
                    @error('alergias') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Medicamentos --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3 mb-1">
                <h2 class="text-sm font-semibold text-gray-900">Medicamentos</h2>
                <button type="button" wire:click="agregarMedicamento" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition">+ Agregar</button>
            </div>
            <p class="text-xs text-gray-500 mb-4">Nombre genérico (DCI) completo, sin siglas ni abreviaturas. La cantidad se imprime en números y letras.</p>
            @error('items') <p class="text-red-500 text-xs mb-3">{{ $message }}</p> @enderror

            <datalist id="formas-farmaceuticas">@foreach (\App\Livewire\CrearReceta::FORMAS as $f)<option value="{{ $f }}">@endforeach</datalist>
            <datalist id="vias">@foreach (\App\Livewire\CrearReceta::VIAS as $v)<option value="{{ $v }}">@endforeach</datalist>

            <div class="space-y-4">
                @foreach ($items as $i => $item)
                    <div class="rounded-xl border border-gray-200 p-4" wire:key="item-{{ $i }}">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-semibold text-gray-500">Medicamento {{ $i + 1 }}</span>
                            @if (count($items) > 1)
                                <button type="button" wire:click="quitarMedicamento({{ $i }})" class="text-xs text-gray-400 hover:text-red-600">Quitar</button>
                            @endif
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div class="sm:col-span-2 relative"
                                 x-data="{ abierto: false, q: '', lista: @js($sugeridos), get filtrados() { const t = this.q.trim().toLowerCase(); return t.length < 2 ? [] : this.lista.filter(s => s.texto.toLowerCase().includes(t)).slice(0, 8); } }"
                                 x-on:click.outside="abierto = false">
                                <label class="{{ $etiqueta }}">Nombre genérico (DCI) *</label>
                                <input wire:model.blur="items.{{ $i }}.dci" type="text" class="{{ $campo }}" placeholder="Ej.: Amoxicilina" autocomplete="off"
                                       x-on:input="q = $event.target.value; abierto = true" x-on:focus="q = $event.target.value; abierto = true">
                                <div x-show="abierto && filtrados.length" x-cloak class="absolute z-30 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-56 overflow-y-auto">
                                    <p class="px-3 pt-2 pb-1 text-[11px] text-gray-400">Usados antes por usted</p>
                                    <template x-for="s in filtrados" :key="s.id">
                                        <button type="button" class="w-full text-left px-3 py-2 text-sm hover:bg-blue-50" x-text="s.texto"
                                                x-on:click="abierto = false; $wire.usarSugerido({{ $i }}, s.id)"></button>
                                    </template>
                                </div>
                                @error("items.{$i}.dci") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                @isset($avisos[$i]) <p class="text-amber-700 text-xs mt-1">⚠ {{ $avisos[$i] }}</p> @endisset
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Concentración *</label>
                                <input wire:model="items.{{ $i }}.concentracion" type="text" class="{{ $campo }}" placeholder="500 mg">
                                @error("items.{$i}.concentracion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Forma farmacéutica *</label>
                                <input wire:model="items.{{ $i }}.forma_farmaceutica" list="formas-farmaceuticas" type="text" class="{{ $campo }}" placeholder="Cápsula">
                                @error("items.{$i}.forma_farmaceutica") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Vía *</label>
                                <input wire:model="items.{{ $i }}.via" list="vias" type="text" class="{{ $campo }}" placeholder="Oral">
                                @error("items.{$i}.via") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Cantidad *</label>
                                <input wire:model="items.{{ $i }}.cantidad" type="number" min="1" max="9999" class="{{ $campo }}" placeholder="21">
                                @error("items.{$i}.cantidad") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Dosis *</label>
                                <input wire:model="items.{{ $i }}.dosis" type="text" class="{{ $campo }}" placeholder="1 cápsula">
                                @error("items.{$i}.dosis") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Frecuencia *</label>
                                <input wire:model="items.{{ $i }}.frecuencia" type="text" class="{{ $campo }}" placeholder="Cada 8 horas">
                                @error("items.{$i}.frecuencia") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="{{ $etiqueta }}">Duración *</label>
                                <input wire:model="items.{{ $i }}.duracion" type="text" class="{{ $campo }}" placeholder="7 días">
                                @error("items.{$i}.duracion") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2 lg:col-span-3">
                                <label class="{{ $etiqueta }}">Indicación adicional</label>
                                <input wire:model="items.{{ $i }}.indicacion" type="text" class="{{ $campo }}" placeholder="Después de las comidas">
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700 lg:pt-6">
                                <input wire:model.live="items.{{ $i }}.antimicrobiano" type="checkbox" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                                Antimicrobiano
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($hayAntimicrobiano)
                <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">Las recetas con antimicrobianos tienen una vigencia máxima de 3 días desde su prescripción (ACESS-2023-0030, Art. 19).</p>
            @endif
        </div>

        {{-- Indicaciones al paciente --}}
        <div class="bg-white rounded-2xl border border-gray-200/80 p-5 sm:p-6">
            <h2 class="text-sm font-semibold text-gray-900 mb-4">Indicaciones para el paciente</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="{{ $etiqueta }}">Signos de alarma</label>
                    <textarea wire:model="signos_alarma" rows="3" class="{{ $campo }}" placeholder="Ante qué síntomas debe llamar o acudir a emergencia"></textarea>
                </div>
                <div>
                    <label class="{{ $etiqueta }}">Recomendaciones no farmacológicas</label>
                    <textarea wire:model="recomendaciones" rows="3" class="{{ $campo }}" placeholder="Dieta, reposo, hidratación…"></textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('turno.atender', $turno->id) }}" wire:navigate class="px-5 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Cancelar</a>
            <button type="submit" wire:loading.attr="disabled" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-xs transition">Guardar receta</button>
        </div>
    </form>
</div>
