<div>
<div class="max-w-5xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-bold text-gray-900">Configuración</h1>
    <p class="text-sm text-gray-500 mb-6">Datos de la clínica, agenda, alertas del control prenatal y horario de tareas automáticas. Los datos tributarios están en <a href="{{ route('sri.config') }}" wire:navigate class="text-blue-600 hover:underline">SRI</a> y en cada médico; las especialidades en <a href="{{ route('especialidades') }}" wire:navigate class="text-blue-600 hover:underline">Especialidades</a>.</p>

    <form wire:submit="guardar" class="space-y-6">
        <section class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Clínica</h2>
            <p class="text-xs text-gray-500 mb-4">Aparece en el inicio de sesión, el menú, el formulario de turnos para pacientes, los PDF y los correos.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex flex-col items-center gap-3">
                    <div class="w-36 h-36 rounded-xl border bg-gray-50 flex items-center justify-center overflow-hidden">
                        @if ($logo && !$errors->has('logo') && $logo->isPreviewable())
                            <img src="{{ $logo->temporaryUrl() }}" alt="Vista previa" class="max-w-full max-h-full object-contain">
                        @else
                            <img src="{{ $marca->logoUrl() }}" alt="Logo" class="max-w-full max-h-full object-contain">
                        @endif
                    </div>
                    <input wire:model="logo" type="file" accept="image/png,image/jpeg" class="w-full text-xs text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-50 file:text-blue-700">
                    <p class="text-xs text-gray-400 text-center">PNG o JPG, máx. 1 MB. Se genera una versión en blanco y negro para los tickets.</p>
                    @error('logo') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                    <button type="button" wire:click="quitarLogo" wire:confirm="¿Volver al logo original?" class="text-xs text-gray-500 hover:underline">Restaurar logo original</button>
                </div>
                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 content-start">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Nombre de la clínica *</label>
                    <input wire:model="marca_nombre" type="text"  class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('marca_nombre') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Subtítulo</label>
                    <input wire:model="marca_subtitulo" type="text" placeholder="Ej.: Ginecología y Medicina General" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('marca_subtitulo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
                    <input wire:model="marca_direccion" type="text"  class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('marca_direccion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
                    <input wire:model="marca_telefono" type="text"  class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('marca_telefono') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Correo</label>
                    <input wire:model="marca_email" type="email"  class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('marca_email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Color principal</label>
                        <div class="flex gap-2 items-center">
                            <input wire:model.live="marca_color" type="color" class="h-10 w-14 rounded-lg border-gray-300">
                            <span class="text-sm font-mono text-gray-600">{{ $marca_color }}</span>
                        </div>
                        @if ($this->contrasteColor !== null)
                            @if ($this->contrasteColor >= 4.5)
                                <p class="text-xs text-emerald-700 mt-1">✓ Buen contraste con texto blanco ({{ $this->contrasteColor }}:1)</p>
                            @else
                                <p class="text-xs text-amber-700 mt-1">⚠ Color claro: el texto blanco de los botones se leerá mal ({{ $this->contrasteColor }}:1, se recomienda 4.5:1). Elija un tono más oscuro.</p>
                            @endif
                        @endif
                        @error('marca_color') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Establecimiento de salud</h2>
            <p class="text-sm text-gray-500 mb-4">Datos del bloque A de los formularios de la Historia Clínica Única (consentimiento informado, referencia y contrarreferencia). El unicódigo lo asigna el MSP / ACESS al licenciar el establecimiento.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Institución del sistema</label>
                    <select wire:model="est_institucion" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @foreach (\App\Livewire\ConfiguracionClinica::INSTITUCIONES as $inst)
                            <option value="{{ $inst }}">{{ $inst }}</option>
                        @endforeach
                    </select>
                    @error('est_institucion') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Unicódigo</label>
                    <input wire:model="est_unicodigo" type="text" inputmode="numeric" class="w-full rounded-lg border-gray-300 shadow-xs text-sm font-mono">
                    @error('est_unicodigo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipología</label>
                    <input wire:model="est_tipologia" type="text" placeholder="Ej.: Consultorio de especialidad clínico-quirúrgica" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('est_tipologia') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                @foreach (['provincia' => 'Provincia', 'canton' => 'Cantón', 'parroquia' => 'Parroquia', 'distrito' => 'Distrito de salud'] as $campo => $etiqueta)
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $etiqueta }}</label>
                        <input wire:model="est_{{ $campo }}" type="text" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error("est_{$campo}") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Agenda</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:row-span-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tipos de cita <span class="font-normal text-gray-400">(uno por línea)</span></label>
                    <textarea wire:model="tiposTexto" rows="8" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                    @error('tiposTexto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Duraciones disponibles (minutos)</label>
                    <input wire:model="duracionesTexto" type="text" placeholder="15, 20, 30, 45, 60" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('duracionesTexto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Duración por defecto (minutos)</label>
                    <input wire:model="duracion_defecto" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    
                    @error('duracion_defecto') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Atención desde</label>
                        <input wire:model="hora_inicio" type="time" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('hora_inicio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
                        <input wire:model="hora_fin" type="time" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                        @error('hora_fin') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Días de atención</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach (\App\Livewire\ConfiguracionClinica::DIAS as $n => $dia)
                            <label class="flex items-center gap-1.5 text-sm text-gray-700">
                                <input type="checkbox" wire:model="dias" value="{{ $n }}" class="rounded-sm border-gray-300"> {{ $dia }}
                            </label>
                        @endforeach
                    </div>
                    @error('dias') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Mensaje del recordatorio por WhatsApp</label>
                    <textarea wire:model="plantillaWhatsapp" rows="3" maxlength="700" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Se reemplazan: {{ implode(' ', \App\Services\WhatsappService::PLACEHOLDERS) }}. El botón WhatsApp aparece en la cita de la agenda y en la agenda de hoy.</p>
                    @error('plantillaWhatsapp') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 rounded-lg border border-gray-200 p-4">
                    <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                        <input type="checkbox" wire:model="reserva_online" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                        Agenda en línea: los pacientes pueden reservar citas en <a href="{{ route('agendar') }}" target="_blank" class="text-blue-700 hover:underline">{{ route('agendar') }}</a>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Las reservas llegan como citas pendientes, marcadas "En línea", para que el personal las confirme.</p>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Días hacia adelante que se pueden reservar</label>
                            <input wire:model="reserva_dias" type="number" min="1" max="180" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            @error('reserva_dias') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Anticipación mínima (horas)</label>
                            <input wire:model="reserva_anticipacion_horas" type="number" min="0" max="168" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                            @error('reserva_anticipacion_horas') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Encuesta de satisfacción</h2>
            <p class="text-sm text-gray-500 mb-4">Cada consulta genera una encuesta corta para el paciente (Estadística → Satisfacción). Se responde en un dispositivo de la clínica o con el enlace por WhatsApp.</p>
            <div class="flex flex-wrap items-end gap-6">
                <label class="flex items-center gap-2 text-sm text-gray-800">
                    <input type="checkbox" wire:model="encuestaActiva" class="rounded-sm border-gray-300 text-blue-600 focus:ring-blue-500">
                    Generar encuesta en cada consulta
                </label>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">El enlace vence a los (días)</label>
                    <input wire:model="encuestaDias" type="number" min="1" max="60" class="w-28 rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('encuestaDias') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-1">Catálogo de exámenes</h2>
            <p class="text-xs text-gray-500 mb-4">Lista que aparece al emitir un pedido de exámenes (uno por línea). Los pedidos ya emitidos no cambian.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Laboratorio</label>
                    <textarea wire:model="examenesLaboratorio" rows="10" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                    @error('examenesLaboratorio') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Imagen y procedimientos</label>
                    <textarea wire:model="examenesImagen" rows="10" class="w-full rounded-lg border-gray-300 shadow-xs text-sm"></textarea>
                    @error('examenesImagen') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-xs border p-6">
            <div class="flex items-start justify-between gap-4 mb-1">
                <h2 class="text-lg font-semibold text-gray-900">Alertas del control prenatal</h2>
                <button type="button" wire:click="restaurarPrenatal" class="text-xs text-blue-600 hover:underline shrink-0">Restaurar valores sugeridos</button>
            </div>
            <p class="text-xs text-gray-500 mb-4">Son apoyo a la decisión clínica, no diagnósticos. Ajústelos según la guía que siga la consulta.</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hipertensión: sistólica ≥</label>
                    <input wire:model="pa_sistolica" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">mmHg</p>
                    @error('pa_sistolica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hipertensión: diastólica ≥</label>
                    <input wire:model="pa_diastolica" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">mmHg</p>
                    @error('pa_diastolica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Severa: sistólica ≥</label>
                    <input wire:model="pa_severa_sistolica" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">mmHg</p>
                    @error('pa_severa_sistolica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Severa: diastólica ≥</label>
                    <input wire:model="pa_severa_diastolica" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">mmHg</p>
                    @error('pa_severa_diastolica') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">FCF mínima</label>
                    <input wire:model="fcf_min" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">lpm</p>
                    @error('fcf_min') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">FCF máxima</label>
                    <input wire:model="fcf_max" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">lpm</p>
                    @error('fcf_max') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Tolerancia altura uterina</label>
                    <input wire:model="au_tolerancia_cm" type="number" step="0.5" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">± cm vs. semanas (20–36)</p>
                    @error('au_tolerancia_cm') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Alertar sin movimientos desde</label>
                    <input wire:model="movimientos_desde_semana" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">semana</p>
                    @error('movimientos_desde_semana') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Alertar sin control tras</label>
                    <input wire:model="dias_sin_control" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">días</p>
                    @error('dias_sin_control') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Avisar FPP con</label>
                    <input wire:model="aviso_fpp_dias" type="number" step="1" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">días de anticipación</p>
                    @error('aviso_fpp_dias') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <section class="bg-white rounded-xl shadow-xs border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tareas automáticas</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hora de envío de recordatorios de citas</label>
                    <input wire:model="hora_recordatorios" type="time" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Se envían a las pacientes con cita al día siguiente.</p>
                    @error('hora_recordatorios') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Hora del respaldo diario</label>
                    <input wire:model="hora_respaldo" type="time" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    <p class="text-xs text-gray-400 mt-0.5">Elija una hora sin atención (la base se copia completa).</p>
                    @error('hora_respaldo') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">Guardar configuración</button>
        </div>
    </form>

    {{-- WhatsApp por la API oficial: componente aparte, con su propio guardado --}}
    <div class="mt-6">
        <livewire:configuracion-whatsapp />
    </div>
</div>
</div>
