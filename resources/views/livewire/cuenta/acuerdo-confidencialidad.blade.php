<div>
    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400">Confidencial</p>
    <h1 class="mt-1 text-lg font-semibold text-gray-900">Acuerdo de confidencialidad</h1>
    <p class="mt-1 text-sm text-gray-500">{{ $marca->nombre() }} · {{ auth()->user()->name }}</p>

    <div class="mt-4 max-h-80 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 space-y-3 leading-relaxed">
        <p>Al usar este sistema me comprometo a:</p>
        <ol class="list-decimal pl-5 space-y-2">
            <li>Guardar reserva, de manera indefinida, sobre toda la información de los pacientes a la que tenga acceso, incluso después de terminar mi relación con el establecimiento (Reglamento de Información Confidencial en el Sistema Nacional de Salud, AM 5216-A, Arts. 5 y 9).</li>
            <li>Acceder a la historia clínica y a los datos de los pacientes solo cuando lo requiera mi trabajo y para los fines de su atención, auditoría de calidad, estadística, investigación o docencia autorizados (Arts. 7 y 10).</li>
            <li>No compartir mi usuario ni mi contraseña: el acceso es personal (Art. 15). Lo que se haga con mi cuenta queda registrado a mi nombre.</li>
            <li>No divulgar ni usar la información en beneficio propio o de terceros, ni comentar datos de pacientes en espacios públicos, llamadas o mensajes (Arts. 25 y 38).</li>
            <li>Entregar copias de la historia clínica únicamente a las personas autorizadas y por el procedimiento establecido (Arts. 28 a 32).</li>
            <li>Tratar los datos de salud como datos sensibles según la Ley Orgánica de Protección de Datos Personales y reportar de inmediato cualquier acceso indebido o pérdida de información.</li>
        </ol>
        <p>Conozco que el incumplimiento de estas obligaciones está sujeto a las sanciones previstas en la legislación vigente (Art. 37).</p>
    </div>

    <form wire:submit="aceptar" class="mt-4 space-y-4">
        <label class="flex items-start gap-3 text-sm text-gray-800">
            <input type="checkbox" wire:model="acepto" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
            <span>He leído y acepto el acuerdo de confidencialidad.</span>
        </label>
        <x-input-error :messages="$errors->get('acepto')" />
        <div class="flex items-center justify-between">
            <button type="button" wire:click="salir" class="text-sm text-gray-500 hover:text-gray-800">Cerrar sesión</button>
            <x-primary-button>Aceptar y continuar</x-primary-button>
        </div>
    </form>
</div>
