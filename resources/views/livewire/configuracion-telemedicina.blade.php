<section class="bg-white rounded-xl shadow-xs border p-6">
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Telemedicina</h2>
    <p class="text-sm text-gray-500 mb-4">
        Videoconsultas agendadas desde la Agenda (modalidad "Telemedicina"). Antes de activarlas, la clínica debe contar con el permiso
        de ACESS para prestar servicios de telesalud (Norma Técnica de Telesalud, num. 7.1.6).
    </p>
    <form wire:submit="guardar" class="space-y-4">
        <label class="flex items-center gap-2 text-sm text-gray-800">
            <input type="checkbox" wire:model="activa" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            Ofrecer teleconsultas
        </label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Plataforma de video</label>
                <select wire:model.live="plataforma" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @foreach (\App\Services\TelemedicinaService::PLATAFORMAS as $clave => $texto)
                        <option value="{{ $clave }}">{{ $texto }}</option>
                    @endforeach
                </select>
                @error('plataforma') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            @if ($plataforma === 'jitsi')
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Servidor de Jitsi</label>
                    <input wire:model="jitsi_url" type="url" placeholder="https://meet.jit.si" class="w-full rounded-lg border-gray-300 shadow-xs text-sm">
                    @error('jitsi_url') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            @endif
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">El paciente puede entrar desde (minutos antes)</label>
                <input wire:model="minutos_antes" type="number" min="0" max="120" class="w-32 rounded-lg border-gray-300 shadow-xs text-sm">
                @error('minutos_antes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Hasta (minutos después de la hora)</label>
                <input wire:model="minutos_despues" type="number" min="10" max="240" class="w-32 rounded-lg border-gray-300 shadow-xs text-sm">
                @error('minutos_despues') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="rounded-lg bg-gray-50 px-4 py-3 text-xs text-gray-600 space-y-1">
            @if ($plataforma === 'jitsi')
                <p>Cada teleconsulta recibe una sala con un nombre aleatorio que no contiene datos del paciente. En el servidor público meet.jit.si, quien abre la sala primero debe iniciar sesión (Google, GitHub o Facebook): conviene que el médico entre antes que el paciente. Para no depender de un tercero, la clínica puede instalar su propio servidor Jitsi y escribir aquí su dirección.</p>
            @else
                <p>Con enlace propio, al abrir cada teleconsulta se pega el enlace de la reunión (Zoom, Google Meet, Teams…). Use reuniones con contraseña o sala de espera.</p>
            @endif
            <p>La clínica no graba las videoconsultas. La historia clínica se registra en SolarMed con la modalidad "Telemedicina".</p>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">Guardar telemedicina</button>
        </div>
    </form>
</section>
