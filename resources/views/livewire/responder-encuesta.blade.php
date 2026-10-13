<div>
    @if ($enviada)
        <div class="text-center py-6">
            <span class="mx-auto flex items-center justify-center w-14 h-14 rounded-full bg-emerald-50 text-emerald-600">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            </span>
            <h1 class="mt-4 text-lg font-semibold text-gray-900">¡Gracias por su opinión!</h1>
            <p class="mt-1 text-sm text-gray-500">Nos ayuda a mejorar la atención en {{ $marca->nombre() }}.</p>
            @if ($enDispositivo)
                <p class="mt-4 text-xs text-gray-400">Puede devolver este dispositivo al personal.</p>
            @endif
        </div>
    @elseif ($encuesta->respondida_at)
        <div class="text-center py-6">
            <h1 class="text-lg font-semibold text-gray-900">Esta encuesta ya fue respondida</h1>
            <p class="mt-1 text-sm text-gray-500">Gracias por ayudarnos a mejorar.</p>
        </div>
    @elseif (!$encuesta->disponible())
        <div class="text-center py-6">
            <h1 class="text-lg font-semibold text-gray-900">Este enlace ya expiró</h1>
            <p class="mt-1 text-sm text-gray-500">Si desea darnos su opinión, comuníquese con {{ $marca->nombre() }}{{ $marca->telefono() ? ' al ' . $marca->telefono() : '' }}.</p>
        </div>
    @else
        <h1 class="text-lg font-semibold text-gray-900">¿Cómo fue su atención?</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $encuesta->especialidad?->nombre ?? 'Consulta' }} con {{ $encuesta->medico?->nombre_completo }}. Es anónima para el médico y toma menos de un minuto.
        </p>

        <form wire:submit="enviar" class="mt-5 space-y-5">
            @foreach (\App\Models\Encuesta::PREGUNTAS as $campo => $pregunta)
                <fieldset>
                    <legend class="text-sm font-medium text-gray-800">{{ $pregunta }}</legend>
                    <div class="mt-2 flex gap-1.5" role="radiogroup">
                        @foreach ([1 => 'Muy malo', 2 => 'Malo', 3 => 'Regular', 4 => 'Bueno', 5 => 'Excelente'] as $valor => $texto)
                            <label class="flex-1 cursor-pointer" title="{{ $texto }}">
                                <input type="radio" wire:model.live="respuestas.{{ $campo }}" value="{{ $valor }}" class="peer sr-only">
                                <span class="flex flex-col items-center justify-center rounded-xl border border-gray-200 py-2 text-gray-300 transition
                                             peer-checked:border-amber-300 peer-checked:bg-amber-50 peer-checked:text-amber-500 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500
                                             {{ (int) ($respuestas[$campo] ?? 0) >= $valor ? 'text-amber-400' : '' }}">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M11.48 3.5a.56.56 0 011.04 0l2.13 5.11a.56.56 0 00.47.35l5.52.44c.5.04.7.66.32.99l-4.2 3.6a.56.56 0 00-.18.56l1.28 5.39a.56.56 0 01-.84.61l-4.72-2.88a.56.56 0 00-.59 0l-4.72 2.88a.56.56 0 01-.84-.61l1.28-5.39a.56.56 0 00-.18-.56l-4.2-3.6a.56.56 0 01.32-.99l5.52-.44a.56.56 0 00.47-.35L11.48 3.5z"/></svg>
                                    <span class="text-[10px] text-gray-500 mt-0.5">{{ $texto }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error("respuestas.{$campo}") <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </fieldset>
            @endforeach

            <fieldset>
                <legend class="text-sm font-medium text-gray-800">Del 0 al 10, ¿qué tan probable es que nos recomiende a un familiar o amigo?</legend>
                <div class="mt-2 grid grid-cols-11 gap-1">
                    @for ($n = 0; $n <= 10; $n++)
                        <label class="cursor-pointer">
                            <input type="radio" wire:model.live="recomendacion" value="{{ $n }}" class="peer sr-only">
                            <span class="flex items-center justify-center h-9 rounded-lg border border-gray-200 text-sm tabular-nums text-gray-600 transition
                                         peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500">{{ $n }}</span>
                        </label>
                    @endfor
                </div>
                <div class="mt-1 flex justify-between text-[10px] text-gray-400"><span>Nada probable</span><span>Muy probable</span></div>
                @error('recomendacion') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
            </fieldset>

            <div>
                <label for="comentario" class="text-sm font-medium text-gray-800">¿Algo que debamos mejorar? <span class="font-normal text-gray-400">(opcional)</span></label>
                <textarea id="comentario" wire:model="comentario" rows="3" maxlength="1000" class="mt-2 w-full rounded-xl border-gray-200 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                <p class="mt-1 text-[11px] text-gray-400">No escriba datos personales ni de salud.</p>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 shadow-sm">Enviar opinión</button>
        </form>
    @endif
</div>
