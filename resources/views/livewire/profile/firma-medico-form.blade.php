<?php

use App\Models\Medico;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public $firma;

    private function medico(): ?Medico
    {
        return Medico::where('user_id', Auth::id())->first();
    }

    public function updatedFirma(): void
    {
        $this->validateOnly('firma', $this->reglas(), $this->mensajes());
    }

    private function reglas(): array
    {
        return ['firma' => 'required|image|mimes:png,jpg,jpeg|max:1024|dimensions:min_width=100,min_height=40,max_width=3000,max_height=3000'];
    }

    private function mensajes(): array
    {
        return [
            'firma.image' => 'La firma debe ser una imagen PNG o JPG.',
            'firma.mimes' => 'La firma debe ser una imagen PNG o JPG.',
            'firma.max' => 'La imagen no debe superar 1 MB.',
            'firma.dimensions' => 'La imagen debe medir entre 100×40 y 3000×3000 píxeles.',
        ];
    }

    public function guardar(): void
    {
        $medico = $this->medico();
        abort_unless($medico, 403);
        $this->validate($this->reglas(), $this->mensajes());

        if ($medico->firma_path) {
            Storage::disk('private')->delete($medico->firma_path);
        }
        $ext = $this->firma->getMimeType() === 'image/png' ? 'png' : 'jpg';
        $ruta = $this->firma->storeAs('firmas', "medico-{$medico->id}-" . now()->format('YmdHis') . ".{$ext}", 'private');
        $medico->update(['firma_path' => $ruta]);

        app(AuditoriaService::class)->registrar('editar', 'medico', $medico->id, null, 'Actualizó su firma y sello');
        $this->reset('firma');
        $this->dispatch('firma-guardada');
    }

    public function quitar(): void
    {
        $medico = $this->medico();
        abort_unless($medico, 403);
        if ($medico->firma_path) {
            Storage::disk('private')->delete($medico->firma_path);
            $medico->update(['firma_path' => null]);
            app(AuditoriaService::class)->registrar('editar', 'medico', $medico->id, null, 'Quitó su firma y sello');
        }
    }

    public function with(): array
    {
        return ['medico' => $this->medico()];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Firma y sello</h2>
        <p class="mt-1 text-sm text-gray-600">
            Se imprime en sus recetas, certificados, pedidos de exámenes e informes de ecografía.
            Use una imagen escaneada con fondo blanco o transparente (PNG recomendado).
        </p>
    </header>

    <div class="mt-4 flex flex-col sm:flex-row gap-6 items-start">
        <div class="w-64 h-28 border rounded-lg bg-white flex items-center justify-center overflow-hidden">
            @if ($firma && !$errors->has('firma') && $firma->isPreviewable())
                <img src="{{ $firma->temporaryUrl() }}" alt="Vista previa" class="max-h-full max-w-full object-contain">
            @elseif ($medico?->firmaRuta())
                <img src="{{ route('firma.ver') }}?v={{ md5($medico->firma_path) }}" alt="Firma actual" class="max-h-full max-w-full object-contain">
            @else
                <span class="text-xs text-gray-400">Sin firma cargada</span>
            @endif
        </div>
        <div class="space-y-2">
            <input wire:model="firma" type="file" accept="image/png,image/jpeg"
                class="text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700">
            <x-input-error :messages="$errors->get('firma')" />
            <div class="flex gap-3 items-center">
                <x-primary-button wire:click="guardar" :disabled="!$firma">Guardar firma</x-primary-button>
                @if ($medico?->firma_path)
                    <button type="button" wire:click="quitar" wire:confirm="¿Quitar su firma de los documentos?" class="text-sm text-red-600 hover:underline">Quitar</button>
                @endif
                <x-action-message on="firma-guardada">Guardada.</x-action-message>
            </div>
        </div>
    </div>
</section>
