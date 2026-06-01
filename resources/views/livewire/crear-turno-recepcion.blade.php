<div>
    <div class="max-w-5xl mx-auto py-8 px-4">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Registrar turno</h1>
                <p class="text-sm text-gray-500">Para pacientes que llegan sin turno previo</p>
            </div>
            @if ($turnoCreado)
                <div class="bg-green-50 border border-green-200 rounded-xl px-5 py-3 flex items-center gap-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-green-800">Turno creado</p>
                        <p class="text-lg font-bold text-green-700">{{ $turnoCreado }}</p>
                    </div>
                </div>
            @endif
        </div>

        <form wire:submit.prevent="registrar" class="space-y-6">

            {{-- Búsqueda de paciente --}}
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Buscar paciente por cédula o nombre
                </h2>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input wire:model.live.debounce.300ms="busqueda" type="text"
                               class="form-input pl-10 w-full"
                               placeholder="Escriba cédula o nombre...">
                    </div>
                    <button type="button" wire:click="nuevoPaciente"
                            class="px-4 py-2 border-2 border-gray-200 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-50 transition whitespace-nowrap">
                        + Nuevo
                    </button>
                </div>

                @if ($pacientes->isNotEmpty())
                    <div class="mt-2 divide-y divide-gray-100 border rounded-xl overflow-hidden">
                        @foreach ($pacientes as $p)
                            <button type="button" wire:click="seleccionarPaciente({{ $p->id }})"
                                    class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition flex items-center justify-between">
                                <span class="font-medium text-gray-700">{{ $p->nombre_completo }}</span>
                                <span class="text-gray-400 text-xs">{{ $p->cedula }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Datos del paciente --}}
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Datos del paciente
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Nombres <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <input wire:model="nombres" type="text" required class="form-input pl-10">
                        </div>
                        @error('nombres') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Apellidos <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A8 8 0 1118.88 6.196M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <input wire:model="apellidos" type="text" required class="form-input pl-10">
                        </div>
                        @error('apellidos') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Cédula <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                            <input wire:model="cedula" type="text" required inputmode="numeric" maxlength="10" class="form-input pl-10">
                        </div>
                        @error('cedula') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Celular <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <input wire:model="telefono" type="tel" required class="form-input pl-10">
                        </div>
                        @error('telefono') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Fecha de nacimiento</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <input wire:model="fecha_nacimiento" type="date" class="form-input pl-10">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Sexo</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <select wire:model="sexo" class="form-input pl-10">
                                <option value="">Seleccione...</option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Dirección</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <input wire:model="direccion" type="text" class="form-input pl-10">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Ciudad</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            <input wire:model="ciudad" type="text" class="form-input pl-10">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Ocupación</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input wire:model="ocupacion" type="text" class="form-input pl-10">
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Correo electrónico</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input wire:model="email" type="email" class="form-input pl-10">
                        </div>
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Referido por</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <input wire:model="referido_por" type="text" class="form-input pl-10">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="form-label">Peso (kg)</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <input wire:model="peso" type="number" step="0.1" class="form-input pl-9">
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Altura (m)</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                                <input wire:model="altura" type="number" step="0.01" class="form-input pl-9">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Información médica rápida --}}
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 016.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/>
                    </svg>
                    Antecedentes médicos (opcional)
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Enfermedades conocidas</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 016.364 6.364L12 20.364l-7.682-7.682a4.5 4.5 0 010-6.364z"/></svg>
                            <textarea wire:model="antecedentes" rows="2" class="form-input pl-10" placeholder="Diabetes, hipertensión..."></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Medicamentos actuales</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                            <textarea wire:model="medicamentos" rows="2" class="form-input pl-10" placeholder="Losartán 50mg..."></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Alergias</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            <textarea wire:model="alergias" rows="2" class="form-input pl-10" placeholder="Penicilina..."></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Cirugías anteriores</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.5 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <textarea wire:model="cirugias" rows="2" class="form-input pl-10" placeholder="Cesárea 2020..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <label class="form-label">Enfermedades familiares</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <textarea wire:model="enfermedades_familiares" rows="2" class="form-input pl-10" placeholder="Madre con diabetes..."></textarea>
                    </div>
                </div>
            </div>

            {{-- Turno --}}
            <div class="bg-white rounded-xl shadow-sm border p-5">
                <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                    Asignar turno
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Especialidad <span class="text-red-500">*</span></label>
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
                            <label class="form-label">Motivo de consulta <span class="text-red-500">*</span></label>
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
                </div>

                @if ($error)
                    <div class="mt-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm p-4">
                        {{ $error }}
                    </div>
                @endif
            </div>

            {{-- Botones --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('sala-espera') }}" wire:navigate
                   class="px-6 py-3 rounded-xl border-2 border-gray-200 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition">
                    Cancelar
                </a>
                <button type="submit" wire:loading.attr="disabled"
                    class="px-8 py-3 rounded-xl font-semibold text-white text-sm flex items-center gap-2 shadow-lg shadow-green-700/20 transition"
                    style="background: #1D9E75;">
                    <span wire:loading.remove>
                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Registrar turno
                    </span>
                    <span wire:loading>Registrando...</span>
                </button>
            </div>

        </form>
    </div>
</div>
