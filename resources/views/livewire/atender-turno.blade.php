<div>
    <div class="max-w-7xl mx-auto py-8 px-4">
        @php
            $paciente = $turno->paciente;
            $nombre = $paciente?->nombre_completo ?? $turno->nombre_temporal;
            $partes = preg_split('/\s+/', trim((string) $nombre)) ?: [];
            $ini = mb_strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1)) ?: '?';
            $campo = 'w-full rounded-xl border-gray-200 shadow-xs focus:border-blue-500 focus:ring-blue-500 text-sm';
            $etiqueta = 'block text-xs font-medium text-gray-600 mb-1';
            $chip = 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium transition';
        @endphp

        {{-- Encabezado --}}
        <a href="{{ route('sala-espera') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Sala de espera
        </a>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 min-w-0">
                <span class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-700 font-semibold shrink-0">{{ $ini }}</span>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-gray-900 truncate">{{ $nombre }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 tabular-nums mr-1">Turno {{ $turno->codigo }}</span>
                        {{ $turno->especialidad->nombre }} · {{ $turno->fecha->format('d/m/Y') }}
                    </p>
                </div>
            </div>
            @can('turnos.gestionar')
                <button wire:click="marcarCompletado" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 shadow-xs transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    Finalizar atención
                </button>
            @endcan
        </div>

        @if (session('error'))
            <div class="rounded-2xl border border-red-200 bg-red-50 text-red-700 text-sm px-5 py-4 mb-6">{{ session('error') }}</div>
        @endif

        @if ($paciente?->alergias)
            <div class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-5 py-3.5 mb-6">
                <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <div class="text-sm"><span class="font-semibold text-red-800">Alergias:</span> <span class="text-red-700">{{ $paciente->alergias }}</span></div>
            </div>
        @endif

        @include('livewire.partials.resumen-preparacion', ['preparacion' => $turno->preparacion])

        @if (session('receta_creada'))
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 px-5 py-4 mb-6">
                <span class="text-sm font-medium text-emerald-800">Receta guardada.</span>
                <a href="{{ route('recetas.pdf', session('receta_creada')) }}" target="_blank"
                   class="inline-flex justify-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl hover:bg-emerald-700 transition">Imprimir receta</a>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            {{-- Columna principal --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Nueva consulta --}}
                @if ($paciente)
                    <section class="bg-white rounded-2xl border border-gray-200/80">
                        <header class="flex items-center justify-between gap-3 px-6 py-4 {{ $showForm ? 'border-b border-gray-100' : '' }}">
                            <div>
                                <h2 class="font-semibold text-gray-900">Nueva consulta</h2>
                                @unless ($showForm)
                                    <p class="text-xs text-gray-500">Registre la atención de hoy en el expediente.</p>
                                @endunless
                            </div>
                            @if ($showForm)
                                <button wire:click="toggleForm" type="button" class="px-3 py-1.5 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition">Cancelar</button>
                            @else
                                <button wire:click="toggleForm" type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-xl hover:bg-blue-700 shadow-xs transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    Nueva consulta
                                </button>
                            @endif
                        </header>

                        @if ($showForm)
                            <form wire:submit="guardarConsulta" class="px-6 py-5 space-y-7">
                                {{-- Tipo de consulta --}}
                                <div>
                                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Tipo de consulta</h3>
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                        @foreach (['general' => 'Medicina general', 'ginecologica' => 'Ginecológica', 'control_prenatal' => 'Control prenatal'] as $valor => $texto)
                                            <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border cursor-pointer transition {{ $tipo_consulta === $valor ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500' : 'border-gray-200 hover:border-gray-300' }}">
                                                <input type="radio" wire:model.live="tipo_consulta" value="{{ $valor }}" class="text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm font-medium text-gray-800">{{ $texto }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('tipo_consulta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>

                                {{-- Motivo y anamnesis --}}
                                <div>
                                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Motivo y antecedentes</h3>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="{{ $etiqueta }}">Motivo de consulta *</label>
                                            <textarea wire:model="motivo_consulta" rows="2" class="{{ $campo }}"></textarea>
                                            @error('motivo_consulta') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                            @include('livewire.partials.consulta-tipo-atencion')
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">Enfermedad o problema actual</label>
                                            <textarea wire:model="anamnesis" rows="3" class="{{ $campo }}"></textarea>
                                            @include('livewire.partials.consulta-revision-sistemas')
                                        </div>
                                    </div>
                                </div>

                                {{-- Examen físico --}}
                                <div>
                                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Examen físico</h3>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div>
                                            <label class="{{ $etiqueta }}">PA (mmHg)</label>
                                            <input wire:model="examen_fisico_pa" type="text" class="{{ $campo }}">
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">FC (lpm)</label>
                                            <input wire:model="examen_fisico_fc" type="text" class="{{ $campo }}">
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">FR (rpm)</label>
                                            <input wire:model="examen_fisico_fr" type="text" class="{{ $campo }}">
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">Temp (°C)</label>
                                            <input wire:model="examen_fisico_temp" type="text" class="{{ $campo }}">
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">Peso (kg)</label>
                                            <input wire:model="examen_fisico_peso" type="number" step="0.1" wire:change="calcularImc" class="{{ $campo }}">
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">Talla (m)</label>
                                            <input wire:model="examen_fisico_talla" type="number" step="0.01" wire:change="calcularImc" class="{{ $campo }}">
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">IMC</label>
                                            <input wire:model="examen_fisico_imc" type="text" readonly class="w-full rounded-xl bg-gray-50 border-gray-200 text-sm text-gray-700">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="{{ $etiqueta }}">Notas del examen físico</label>
                                        <textarea wire:model="examen_fisico_notas" rows="2" class="{{ $campo }}"></textarea>
                                    </div>
                                </div>

                                {{-- Diagnóstico y tratamiento --}}
                                <div>
                                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Diagnóstico y tratamiento</h3>
                                    <div class="space-y-3">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div class="sm:col-span-2">
                                                <label class="{{ $etiqueta }}">Diagnóstico *</label>
                                                <textarea wire:model="diagnostico" rows="2" class="{{ $campo }}"></textarea>
                                                @error('diagnostico') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                @include('livewire.partials.consulta-tipo-diagnostico')
                                            </div>
                                            <div class="relative" x-data="{ open: false }">
                                                <label class="{{ $etiqueta }}">CIE-10</label>
                                                <input wire:model.live.debounce.200ms="cie10Search" type="text"
                                                    class="{{ $campo }}"
                                                    placeholder="Código o descripción…" autocomplete="off"
                                                    x-on:focus="open = true" x-on:click.away="open = false">
                                                @if ($cie10Results)
                                                    <div x-show="open"
                                                        class="absolute z-50 mt-1 w-full sm:w-80 right-0 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                                        @foreach ($cie10Results as $r)
                                                            <button type="button" wire:click="selectCie10({{ \Illuminate\Support\Js::from($r['codigo']) }})" x-on:click="open = false"
                                                                class="w-full text-left px-3 py-2 hover:bg-blue-50 text-sm border-b border-gray-100 last:border-b-0">
                                                                <span class="font-medium text-gray-900 font-mono">{{ $r['codigo'] }}</span>
                                                                <span class="text-gray-500 ml-2">{{ $r['descripcion'] }}</span>
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if ($cie10Selected)
                                                    <div class="mt-1.5 flex items-center gap-2">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-blue-50 text-blue-700 font-mono">{{ $cie10Selected['codigo'] }}</span>
                                                        <span class="text-xs text-gray-500 truncate">{{ $cie10Selected['descripcion'] }}</span>
                                                        <button type="button" wire:click="clearCie10" class="p-0.5 rounded-sm text-gray-400 hover:text-red-600" aria-label="Quitar código CIE-10">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                @endif
                                                <input type="hidden" wire:model="codigo_cie10">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">Tratamiento</label>
                                            <textarea wire:model="tratamiento" rows="3" class="{{ $campo }}"></textarea>
                                        </div>
                                        <div>
                                            <label class="{{ $etiqueta }}">Indicaciones</label>
                                            <textarea wire:model="indicaciones" rows="2" class="{{ $campo }}"></textarea>
                                        </div>
                                    </div>
                                </div>

                                {{-- Derivación --}}
                                <div>
                                    <h3 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Derivación</h3>
                                    <label class="inline-flex items-center gap-2 mb-3 cursor-pointer">
                                        <input type="checkbox" wire:model.live="requiere_derivacion" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Requiere derivación a otra especialidad</span>
                                    </label>
                                    @if ($requiere_derivacion)
                                        <div>
                                            <label class="{{ $etiqueta }}">Especialidad de derivación</label>
                                            <input wire:model="derivacion_especialidad" type="text" class="{{ $campo }} max-w-md">
                                            @error('derivacion_especialidad') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                        </div>
                                    @endif
                                </div>

                                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-5 border-t border-gray-100">
                                    <button type="button" wire:click="toggleForm"
                                        class="px-5 py-2.5 border border-gray-200 bg-white text-gray-700 font-medium rounded-xl hover:bg-gray-50 transition text-sm">
                                        Cancelar
                                    </button>
                                    <button type="submit" wire:loading.attr="disabled"
                                        class="px-5 py-2.5 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 transition shadow-xs text-sm disabled:opacity-60">
                                        Guardar consulta
                                    </button>
                                </div>
                            </form>
                        @endif
                    </section>
                @endif

                {{-- Consultas anteriores --}}
                @if ($consultasAnteriores->count() > 0)
                    <section class="bg-white rounded-2xl border border-gray-200/80">
                        <header class="px-6 py-4 border-b border-gray-100">
                            <h2 class="font-semibold text-gray-900">Consultas del expediente</h2>
                            <p class="text-xs text-gray-500">{{ $consultasAnteriores->count() }} {{ $consultasAnteriores->count() === 1 ? 'registro' : 'registros' }}</p>
                        </header>
                        <ol class="divide-y divide-gray-100">
                            @foreach ($consultasAnteriores as $consulta)
                                <li class="px-6 py-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm">
                                                <span class="font-medium text-gray-900">{{ $consulta->fecha->format('d/m/Y') }}</span>
                                                <span class="text-gray-400 tabular-nums">{{ $consulta->fecha->format('H:i') }}</span>
                                                <span class="text-gray-500">· {{ $consulta->medico?->nombre_completo ?? '—' }}</span>
                                            </p>
                                            <p class="text-sm text-gray-700 mt-1"><span class="text-gray-500">Motivo:</span> {{ $consulta->motivo_consulta }}</p>
                                            <p class="text-sm text-gray-700"><span class="text-gray-500">Diagnóstico:</span> {{ $consulta->diagnostico }}</p>
                                            @if ($consulta->codigo_cie10)
                                                <span class="inline-block mt-1.5 px-2 py-0.5 rounded-md bg-gray-100 text-xs text-gray-600 font-mono">CIE-10 {{ $consulta->codigo_cie10 }}</span>
                                            @endif
                                        </div>
                                        @can('update', $consulta)
                                            <a href="{{ route('consultas.editar', $consulta->id) }}" wire:navigate
                                               class="shrink-0 inline-flex p-2 rounded-lg text-gray-400 hover:text-blue-700 hover:bg-blue-50 transition" title="Editar consulta" aria-label="Editar consulta">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                            </a>
                                        @endcan
                                    </div>
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        @can('ecografias.crear')
                                            <a href="{{ route('ecografias.crear', $turno->id) }}" wire:navigate class="{{ $chip }} bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700">+ Ecografía</a>
                                        @endcan
                                        @can('expediente.crear')
                                            <a href="{{ route('pedidos.crear', ['paciente' => $turno->paciente_id, 'consulta' => $consulta->id]) }}" wire:navigate class="{{ $chip }} bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700">+ Exámenes</a>
                                            <a href="{{ route('certificados.crear', ['paciente' => $turno->paciente_id, 'consulta' => $consulta->id]) }}" wire:navigate class="{{ $chip }} bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700">+ Certificado</a>
                                            <a href="{{ route('consentimientos.crear', ['paciente' => $turno->paciente_id, 'consulta' => $consulta->id]) }}" wire:navigate class="{{ $chip }} bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700">+ Consentimiento</a>
                                            <a href="{{ route('referencias.crear', ['paciente' => $turno->paciente_id, 'consulta' => $consulta->id]) }}" wire:navigate
                                               class="{{ $chip }} {{ $consulta->requiere_derivacion ? 'bg-violet-100 text-violet-800 hover:bg-violet-200' : 'bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700' }}">{{ $consulta->requiere_derivacion ? 'Generar referencia (053)' : '+ Referencia' }}</a>
                                        @endcan
                                        <a href="{{ route('recetas.crear', $turno->id) }}" wire:navigate class="{{ $chip }} bg-gray-100 text-gray-700 hover:bg-blue-50 hover:text-blue-700">+ Receta</a>
                                    </div>
                                    @include('livewire.partials.encuesta-consulta', ['encuesta' => $consulta->encuesta])
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>

            {{-- Columna lateral: ficha del paciente --}}
            <aside class="space-y-6">
                <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
                    <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Paciente</h2>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Cédula</dt><dd class="text-gray-900 font-mono">{{ $turno->cedula }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Edad</dt><dd class="text-gray-900">{{ $paciente?->edad !== null ? $paciente->edad . ' años' : '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Sexo</dt><dd class="text-gray-900 capitalize">{{ $paciente?->sexo ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Teléfono</dt><dd class="text-gray-900">{{ $paciente?->telefono ?? $turno->telefono ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Email</dt><dd class="text-gray-900 truncate">{{ $paciente?->email ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Ciudad</dt><dd class="text-gray-900 text-right">{{ $paciente?->ciudad ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Dirección</dt><dd class="text-gray-900 text-right">{{ $paciente?->direccion ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-gray-500">Ocupación</dt><dd class="text-gray-900 text-right">{{ $paciente?->ocupacion ?: '—' }}</dd></div>
                        @if ($paciente?->referido_por)
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Referido por</dt><dd class="text-gray-900 text-right">{{ $paciente->referido_por }}</dd></div>
                        @endif
                    </dl>
                    @if ($paciente)
                        <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-gray-100">
                            <a href="{{ route('pacientes.archivos', $paciente->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 text-xs font-medium text-gray-700 hover:bg-gray-50 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/></svg>
                                Archivos
                            </a>
                            @if ($paciente->sexo === 'femenino')
                                <a href="{{ route('prenatal', $paciente->id) }}" wire:navigate class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-pink-200 text-xs font-medium text-pink-700 hover:bg-pink-50 transition">
                                    Control prenatal
                                </a>
                            @endif
                        </div>
                    @endif
                </section>

                @if ($paciente)
                    <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
                        <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Datos físicos</h2>
                        <dl class="grid grid-cols-2 gap-3 text-sm">
                            <div><dt class="text-xs text-gray-500">Peso</dt><dd class="font-medium text-gray-900">{{ $paciente->peso ? $paciente->peso . ' kg' : '—' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Altura</dt><dd class="font-medium text-gray-900">{{ $paciente->altura ? $paciente->altura . ' m' : '—' }}</dd></div>
                            @if ($paciente->peso && $paciente->altura)
                                @php $imc = round($paciente->peso / ($paciente->altura * $paciente->altura), 1); @endphp
                                <div><dt class="text-xs text-gray-500">IMC</dt><dd class="font-medium text-gray-900">{{ $imc }}</dd></div>
                            @endif
                            <div><dt class="text-xs text-gray-500">Grupo sanguíneo</dt><dd class="font-medium text-gray-900">{{ $paciente->grupo_sanguineo ?: '—' }}</dd></div>
                        </dl>
                    </section>

                    <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
                        <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Antecedentes</h2>
                        <dl class="space-y-3 text-sm">
                            <div><dt class="text-xs text-gray-500">Enfermedades personales</dt><dd class="text-gray-900">{{ $paciente->antecedentes ?: 'Ninguna' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Enfermedades familiares</dt><dd class="text-gray-900">{{ $paciente->enfermedades_familiares ?: 'Ninguna' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Medicamentos</dt><dd class="text-gray-900">{{ $paciente->medicamentos ?: 'Ninguno' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Cirugías</dt><dd class="text-gray-900">{{ $paciente->cirugias ?: 'Ninguna' }}</dd></div>
                            <div><dt class="text-xs text-gray-500">Alergias</dt><dd class="{{ $paciente->alergias ? 'text-red-700 font-medium' : 'text-gray-900' }}">{{ $paciente->alergias ?: 'Ninguna' }}</dd></div>
                        </dl>
                    </section>

                    @if ($paciente->sexo === 'femenino' && ($paciente->fum || $paciente->gestas))
                        <section class="bg-white rounded-2xl border border-pink-200 p-5">
                            <h2 class="text-[11px] font-semibold uppercase tracking-wide text-pink-600 mb-3">Gineco-obstétricos</h2>
                            <dl class="grid grid-cols-2 gap-3 text-sm">
                                <div><dt class="text-xs text-gray-500">FUM</dt><dd class="font-medium text-gray-900">{{ $paciente->fum?->format('d/m/Y') ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Gestas</dt><dd class="font-medium text-gray-900">{{ $paciente->gestas ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Partos</dt><dd class="font-medium text-gray-900">{{ $paciente->partos ?? '—' }}</dd></div>
                                <div><dt class="text-xs text-gray-500">Cesáreas</dt><dd class="font-medium text-gray-900">{{ $paciente->cesareas ?? '—' }}</dd></div>
                            </dl>
                        </section>
                    @endif
                @endif

                @if (count($recetasRecientes) > 0)
                    <section class="bg-white rounded-2xl border border-gray-200/80 p-5">
                        <h2 class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Recetas recientes</h2>
                        <ul class="divide-y divide-gray-100 -my-2">
                            @foreach ($recetasRecientes as $receta)
                                <li class="py-2.5 flex items-center justify-between gap-3 text-sm">
                                    <div class="min-w-0">
                                        <p class="text-gray-900"><span class="font-medium">{{ $receta->fecha->format('d/m/Y') }}</span> <span class="text-gray-500">· {{ $receta->medico?->nombre_completo }}</span></p>
                                        <p class="text-xs text-gray-500 truncate">{{ $receta->resumen() }}</p>
                                    </div>
                                    <a href="{{ route('recetas.pdf', $receta->id) }}" target="_blank"
                                       class="shrink-0 px-2.5 py-1 text-xs font-medium text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition">PDF</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </aside>
        </div>
    </div>
</div>
