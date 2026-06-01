<div class="min-h-screen flex flex-col" style="background: #F1EFE8;">

    {{-- ===== PANTALLA DE ÉXITO ===== --}}
    @if ($turnoAsignado)
        <div class="flex-1 flex flex-col items-center justify-center px-6 text-center">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-5"
                 style="background: #E1F5EE;">
                <svg class="w-10 h-10" style="color: #1D9E75;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            <p class="text-xs font-semibold uppercase tracking-widest mb-1" style="color: #1D9E75;">
                ¡Turno asignado!
            </p>
            <p class="font-extrabold leading-none my-3" style="font-size: 80px; color: #0F6E56;">
                {{ $turnoAsignado }}
            </p>
            <p class="text-gray-600 text-sm">Preséntate en recepción y espera tu llamado.</p>
            <p class="text-xs text-gray-400 mt-1">Tiempo estimado de espera: ~10 min</p>

            <button wire:click="$set('turnoAsignado', null)"
                class="mt-10 w-full max-w-xs py-4 rounded-2xl font-semibold text-white text-base shadow-lg shadow-green-700/20"
                style="background: #1D9E75;">
                Sacar otro turno
            </button>
        </div>

    {{-- ===== FORMULARIO POR PASOS ===== --}}
    @else
        <div class="max-w-lg mx-auto w-full flex flex-col flex-1" x-data="turnoWizard()" x-init="init()">

            {{-- Cabecera --}}
            <div class="text-white text-center px-5 pt-8 pb-8 rounded-b-3xl shadow-lg shadow-green-800/10" style="background: linear-gradient(135deg, #0F6E56 0%, #1D9E75 100%);">
                <div class="w-12 h-12 mx-auto mb-3 bg-white/15 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <x-application-logo class="w-7 h-7 opacity-90" />
                </div>
                <h1 class="text-lg font-bold tracking-tight">Clínica Santa Martha</h1>
                <p class="text-xs mt-1 font-medium" style="opacity: 0.80;">Dr. Jorge Bury — Ginecología & Medicina General</p>
            </div>

            {{-- Barra de progreso --}}
            <div class="bg-white mx-4 -mt-5 rounded-2xl shadow-sm border border-gray-100 px-5 py-4 flex items-center gap-2">
                <template x-for="n in 3" :key="n">
                    <div class="flex items-center gap-2 flex-1 last:flex-none">
                        <button
                            type="button"
                            class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0 transition-all duration-300"
                            :class="paso >= n ? 'shadow-sm' : ''"
                            :style="paso > n  ? 'background:#9FE1CB; color:#085041;' :
                                    paso == n ? 'background:#1D9E75; color:white;' :
                                    'background:#F1EFE8; color:#9CA3AF;'"
                            @click="if(n < paso) irA(n)"
                            :aria-current="paso == n ? 'step' : undefined"
                        >
                            <template x-if="paso > n">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </template>
                            <template x-if="paso <= n">
                                <span x-text="n"></span>
                            </template>
                        </button>
                        <template x-if="n < 3">
                            <div class="h-0.5 flex-1 rounded-full transition-all duration-300"
                                 :style="paso > n ? 'background:#9FE1CB' : 'background:#E5E3D9'">
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <form wire:submit.prevent="registrar" class="flex-1 flex flex-col">

                {{-- ================================================ --}}
                {{-- PASO 1 — Datos personales + Contacto            --}}
                {{-- ================================================ --}}
                <div x-show="paso == 1" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="flex-1 overflow-y-auto">
                    <div class="flex items-center gap-3 px-5 pt-5 pb-1">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:#E1F5EE; color:#0F6E56;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Datos personales</p>
                            <p class="text-xs text-gray-400">Paso 1 de 3</p>
                        </div>
                    </div>

                    <div class="px-5 pb-3 space-y-3.5">

                        {{-- Datos personales --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Nombres <span class="text-green-600">*</span></label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <input wire:model="nombres" type="text" required autocomplete="given-name"
                                           placeholder="María Isabel"
                                           class="form-input pl-10">
                                </div>
                                @error('nombres') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Apellidos <span class="text-green-600">*</span></label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <input wire:model="apellidos" type="text" required autocomplete="family-name"
                                           placeholder="Torres Vega"
                                           class="form-input pl-10">
                                </div>
                                @error('apellidos') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Cédula <span class="text-green-600">*</span></label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                                    <input wire:model="cedula" type="text" required
                                           inputmode="numeric" maxlength="10"
                                           placeholder="10 dígitos"
                                           class="form-input pl-10">
                                </div>
                                @error('cedula') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Celular <span class="text-green-600">*</span></label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    <input wire:model="telefono" type="tel" required
                                           inputmode="tel" autocomplete="tel"
                                           placeholder="09XXXXXXXX"
                                           class="form-input pl-10">
                                </div>
                                @error('telefono') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Fecha de nacimiento</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <input wire:model="fecha_nacimiento" type="date"
                                           class="form-input pl-10">
                                </div>
                                @error('fecha_nacimiento') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Sexo <span class="text-green-600">*</span></label>
                                <div class="grid grid-cols-3 gap-2 mt-1">
                                    @foreach(['masculino' => 'M', 'femenino' => 'F', 'otro' => 'O'] as $val => $label)
                                        <label
                                            class="flex flex-col items-center justify-center py-2.5 rounded-xl border-2 text-sm font-medium cursor-pointer transition-all duration-150"
                                            :style="$wire.sexo === '{{ $val }}'
                                                ? 'border-color:#1D9E75; background:#E1F5EE; color:#085041;'
                                                : 'border-color:#E5E3D9; background:white; color:#6B7280;'">
                                            <input wire:model.live="sexo" type="radio" value="{{ $val }}" class="sr-only">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('sexo') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <hr class="border-gray-100 my-1">

                        {{-- Contacto y dirección --}}
                        <div>
                            <label class="form-label">Dirección domiciliaria</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <input wire:model="direccion" type="text"
                                       placeholder="Barrio, calle, número..."
                                       class="form-input pl-10">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Ciudad</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                    <input wire:model="ciudad" type="text"
                                           placeholder="Ej: Quito"
                                           class="form-input pl-10">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Ocupación</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    <input wire:model="ocupacion" type="text"
                                           placeholder="Ej: Docente"
                                           class="form-input pl-10">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Correo electrónico</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <input wire:model="email" type="email"
                                       inputmode="email" autocomplete="email"
                                       placeholder="correo@ejemplo.com"
                                       class="form-input pl-10">
                            </div>
                            @error('email') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">¿Quién lo recomendó?</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <input wire:model="referido_por" type="text"
                                       placeholder="Nombre de quien lo refirió"
                                       class="form-input pl-10">
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ================================================ --}}
                {{-- PASO 2 — Información médica                      --}}
                {{-- ================================================ --}}
                <div x-show="paso == 2" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="flex-1 overflow-y-auto">
                    <div class="flex items-center gap-3 px-5 pt-5 pb-1">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:#E1F5EE; color:#0F6E56;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 016.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Información médica</p>
                            <p class="text-xs text-gray-400">Paso 2 de 3 — Solo lo que recuerdes</p>
                        </div>
                    </div>

                    <div class="px-5 pb-3 space-y-3.5">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Peso (kg)</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <input wire:model="peso" type="number" step="0.1"
                                           inputmode="decimal"
                                           placeholder="68.5"
                                           class="form-input pl-10">
                                </div>
                                @error('peso') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Altura (m)</label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                    <input wire:model="altura" type="number" step="0.01"
                                           inputmode="decimal"
                                           placeholder="1.65"
                                           class="form-input pl-10">
                                </div>
                                @error('altura') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="form-label">¿Tiene enfermedades conocidas?</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 016.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/></svg>
                                <textarea wire:model="antecedentes" rows="3"
                                          placeholder="Ej: Diabetes tipo 2, Hipertensión..."
                                          class="form-input pl-10"></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">¿Toma medicamentos actualmente?</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                                <textarea wire:model="medicamentos" rows="3"
                                          placeholder="Ej: Losartán 50mg, Metformina 850mg..."
                                          class="form-input pl-10"></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Alergias conocidas</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <textarea wire:model="alergias" rows="3"
                                          placeholder="Ej: Penicilina, Aspirina, Polen..."
                                          class="form-input pl-10"></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Cirugías anteriores</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                                <textarea wire:model="cirugias" rows="3"
                                          placeholder="Ej: Cesárea 2020, Apendicectomía 2018..."
                                          class="form-input pl-10"></textarea>
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Enfermedades familiares</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <textarea wire:model="enfermedades_familiares" rows="3"
                                          placeholder="Ej: Madre con diabetes, Padre con hipertensión..."
                                          class="form-input pl-10"></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ================================================ --}}
                {{-- PASO 3 — Turno (especialidad + motivo)          --}}
                {{-- ================================================ --}}
                <div x-show="paso == 3" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-cloak class="flex-1">
                    <div class="flex items-center gap-3 px-5 pt-5 pb-1">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                             style="background:#E1F5EE; color:#0F6E56;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Tu turno</p>
                            <p class="text-xs text-gray-400">Paso 3 de 3</p>
                        </div>
                    </div>

                    <div class="px-5 pb-3 space-y-4">

                        <div>
                            <label class="form-label">Especialidad <span class="text-green-600">*</span></label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                <select wire:model.live="especialidad_id" required class="form-input pl-10">
                                    <option value="">Seleccione...</option>
                                    @foreach ($especialidades as $esp)
                                        <option value="{{ $esp['id'] }}">{{ $esp['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('especialidad_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        @if ($motivosDisponibles)
                            <div>
                                <label class="form-label">Motivo de consulta <span class="text-green-600">*</span></label>
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <select wire:model="motivo" required class="form-input pl-10">
                                        <option value="">Seleccione...</option>
                                        @foreach ($motivosDisponibles as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('motivo') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @if ($error)
                            <div class="rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm p-4">
                                {{ $error }}
                            </div>
                        @endif

                        <div class="rounded-xl p-3.5 text-xs text-gray-500 flex gap-2.5 items-start"
                             style="background: #F1EFE8;">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" style="color:#1D9E75;"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"/>
                            </svg>
                            <span>Al confirmar se te asignará un número de turno. Muéstraselo a la recepcionista al llegar.</span>
                        </div>

                    </div>
                </div>

                {{-- ===== Botones de navegación ===== --}}
                <div class="px-5 pb-6 pt-2 mt-auto flex gap-3">
                    <button
                        type="button"
                        x-show="paso > 1"
                        @click="paso--"
                        class="flex-1 py-4 rounded-2xl border-2 border-gray-200 bg-white text-gray-600 font-semibold text-base active:scale-[0.98] transition-transform">
                        ← Atrás
                    </button>

                    <button
                        type="button"
                        x-show="paso < 3"
                        @click="$wire.validarPaso(paso).then(ok => { if(ok) paso++ })"
                        class="py-4 rounded-2xl font-semibold text-white text-base shadow-lg shadow-green-700/20 active:scale-[0.98] transition-all"
                        :class="paso > 1 ? 'flex-[2]' : 'w-full'"
                        style="background: #1D9E75;">
                        Continuar →
                    </button>

                    <button
                        type="submit"
                        x-show="paso == 3"
                        wire:loading.attr="disabled"
                        class="flex-[2] py-4 rounded-2xl font-semibold text-white text-base flex items-center justify-center gap-2 shadow-lg shadow-green-700/20 active:scale-[0.98] transition-all"
                        style="background: #1D9E75;">
                        <span wire:loading.remove>
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            Obtener turno
                        </span>
                        <span wire:loading>
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                    </button>
                </div>

            </form>
        </div>

        <p class="text-center text-xs text-gray-400 pb-5">
            Clínica Santa Martha &mdash; Dr. Jorge Bury &mdash; Tel: 044619253
        </p>

    @endif
</div>
