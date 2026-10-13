<?php

namespace App\Livewire;

use App\Livewire\Traits\WithCie10Autocomplete;
use App\Models\Cie10Code;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\RecetaItem;
use App\Models\Turno;
use App\Services\AuditoriaService;
use App\Services\MarcaService;
use App\Services\RecetaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Receta médica con los datos mínimos de la Resolución ACESS-2023-0030, Art. 5.
 */
class CrearReceta extends Component
{
    use WithCie10Autocomplete;

    public const FORMAS = ['Tableta', 'Tableta recubierta', 'Cápsula', 'Comprimido', 'Jarabe', 'Suspensión oral', 'Solución oral', 'Gotas orales',
        'Solución inyectable', 'Polvo para suspensión', 'Crema', 'Ungüento', 'Gel', 'Óvulo', 'Supositorio', 'Solución oftálmica', 'Inhalador', 'Parche'];
    public const VIAS = ['Oral', 'Sublingual', 'Intramuscular', 'Intravenosa', 'Subcutánea', 'Tópica', 'Vaginal', 'Rectal', 'Oftálmica', 'Ótica', 'Nasal', 'Inhalatoria'];

    public Turno $turno;
    #[Locked]
    public $paciente_id;
    #[Locked]
    public $consulta_id;

    public array $items = [];
    public $ciudad;
    public $diagnostico;
    public $codigo_cie10;
    public $alergias;
    public $signos_alarma;
    public $recomendaciones;

    protected function rules(): array
    {
        return [
            'items' => 'required|array|min:1|max:15',
            'items.*.dci' => 'required|string|max:200',
            'items.*.forma_farmaceutica' => 'required|string|max:80',
            'items.*.concentracion' => 'required|string|max:80',
            'items.*.via' => 'required|string|max:60',
            'items.*.cantidad' => 'required|integer|min:1|max:9999',
            'items.*.dosis' => 'required|string|max:150',
            'items.*.frecuencia' => 'required|string|max:150',
            'items.*.duracion' => 'required|string|max:100',
            'items.*.indicacion' => 'nullable|string|max:500',
            'items.*.antimicrobiano' => 'boolean',
            'ciudad' => 'required|string|max:80',
            'diagnostico' => 'required|string|max:1000',
            'codigo_cie10' => ['required', 'string', 'max:10', 'regex:/^[A-Za-z]\d{2}(\.?[0-9A-Za-z]{1,2})?$/'],
            'alergias' => 'required|string|max:1000',
            'signos_alarma' => 'nullable|string|max:2000',
            'recomendaciones' => 'nullable|string|max:2000',
        ];
    }

    protected function messages(): array
    {
        return [
            'items.required' => 'Agregue al menos un medicamento.',
            'items.*.dci.required' => 'Escriba el nombre genérico (DCI).',
            'items.*.forma_farmaceutica.required' => 'Indique la forma farmacéutica.',
            'items.*.concentracion.required' => 'Indique la concentración.',
            'items.*.via.required' => 'Indique la vía.',
            'items.*.cantidad.required' => 'Indique la cantidad.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
            'items.*.dosis.required' => 'Indique la dosis.',
            'items.*.frecuencia.required' => 'Indique la frecuencia.',
            'items.*.duracion.required' => 'Indique la duración.',
            'codigo_cie10.required' => 'La receta debe llevar el diagnóstico con código CIE-10.',
            'codigo_cie10.regex' => 'El código CIE-10 no es válido (ejemplo: J02.9).',
            'alergias.required' => 'Registre las alergias o escriba "No refiere".',
        ];
    }

    public function mount(Turno $turno): void
    {
        $this->turno = $turno->loadMissing('paciente');
        $this->paciente_id = $this->turno->paciente_id;

        $consulta = ExpedienteConsulta::where('turno_id', $this->turno->id)->latest('fecha')->first();
        $this->consulta_id = $consulta?->id;
        $this->diagnostico = $consulta?->diagnostico;
        if ($consulta?->codigo_cie10) {
            $this->selectCie10($consulta->codigo_cie10);
            if (!$this->codigo_cie10) {
                // Código de la consulta que no está en el catálogo local: se respeta tal cual
                $this->codigo_cie10 = $consulta->codigo_cie10;
                $this->cie10Search = $consulta->codigo_cie10;
            }
        }

        $this->alergias = trim((string) $this->turno->paciente?->alergias) ?: 'No refiere';
        $establecimiento = app(MarcaService::class)->establecimiento();
        $this->ciudad = $establecimiento['canton'] ?: ($this->turno->paciente?->ciudad ?: '');

        $this->agregarMedicamento();
    }

    public function agregarMedicamento(): void
    {
        $this->items[] = [
            'dci' => '', 'forma_farmaceutica' => '', 'concentracion' => '', 'via' => 'Oral', 'cantidad' => '',
            'dosis' => '', 'frecuencia' => '', 'duracion' => '', 'indicacion' => '', 'antimicrobiano' => false,
        ];
    }

    public function quitarMedicamento(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    /** Copia en la fila un medicamento que el médico ya prescribió antes */
    public function usarSugerido(int $index, int $itemId): void
    {
        $previo = RecetaItem::whereHas('receta', fn ($q) => $q->where('medico_id', $this->medicoActual()?->id))->find($itemId);
        if (!$previo || !isset($this->items[$index])) {
            return;
        }
        $this->items[$index] = array_merge($this->items[$index], $previo->only([
            'dci', 'forma_farmaceutica', 'concentracion', 'via', 'dosis', 'frecuencia', 'duracion', 'indicacion', 'antimicrobiano',
        ]));
    }

    private function medicoActual(): ?Medico
    {
        return Medico::where('user_id', Auth::id())->first();
    }

    /** Medicamentos que este médico ya usó (distintos por DCI, forma y concentración), para llenar más rápido */
    #[Computed]
    public function sugeridos(): array
    {
        $medico = $this->medicoActual();
        if (!$medico) {
            return [];
        }

        return RecetaItem::query()
            ->whereHas('receta', fn ($q) => $q->where('medico_id', $medico->id))
            ->latest('id')->limit(500)->get()
            ->unique(fn ($i) => mb_strtolower("{$i->dci}|{$i->forma_farmaceutica}|{$i->concentracion}"))
            ->take(150)
            ->map(fn ($i) => ['id' => $i->id, 'texto' => "{$i->dci} {$i->concentracion} · {$i->forma_farmaceutica}", 'dci' => $i->dci])
            ->values()->all();
    }

    /**
     * Avisos (no bloquean): el DCI debe ir sin siglas ni abreviaturas (Art. 5 c.i) y sin la concentración.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function avisos(): array
    {
        $avisos = [];
        foreach ($this->items as $i => $item) {
            $dci = trim((string) ($item['dci'] ?? ''));
            if ($dci === '') {
                continue;
            }
            if (preg_match('/\b[A-ZÁÉÍÓÚÑ]{2,}\b/u', $dci) && mb_strtoupper($dci) !== $dci) {
                $avisos[$i] = 'Parece tener siglas: escriba el nombre genérico completo.';
            } elseif (preg_match('/\b(mg|mcg|ml|g|ui|%)\b|\d/iu', $dci)) {
                $avisos[$i] = 'La concentración va en su propio campo; deje aquí solo el nombre genérico.';
            } elseif (preg_match('/\b\p{L}{1,4}\./u', $dci)) {
                $avisos[$i] = 'Evite abreviaturas (por ejemplo "Ác." o "Sol.").';
            } elseif (mb_strtoupper($dci) === $dci && mb_strlen($dci) <= 6) {
                $avisos[$i] = 'Parece una sigla: escriba el nombre genérico completo.';
            }
        }

        return $avisos;
    }

    public function guardar(RecetaService $recetas): void
    {
        $this->authorize('expediente.crear');
        $this->validate();

        $medico = $this->medicoActual();
        if (!$medico) {
            session()->flash('error', 'No tienes un perfil de médico asociado.');
            return;
        }

        $items = array_map(fn ($i) => [
            'dci' => trim($i['dci']), 'forma_farmaceutica' => trim($i['forma_farmaceutica']), 'concentracion' => trim($i['concentracion']),
            'via' => trim($i['via']), 'cantidad' => (int) $i['cantidad'], 'dosis' => trim($i['dosis']), 'frecuencia' => trim($i['frecuencia']),
            'duracion' => trim($i['duracion']), 'indicacion' => trim((string) ($i['indicacion'] ?? '')) ?: null,
            'antimicrobiano' => (bool) ($i['antimicrobiano'] ?? false),
        ], $this->items);

        $receta = $recetas->emitir([
            'consulta_id' => $this->consulta_id,
            'paciente_id' => $this->paciente_id,
            'medico_id' => $medico->id,
            'fecha' => today(),
            'ciudad' => trim($this->ciudad),
            'diagnostico' => trim($this->diagnostico),
            'codigo_cie10' => strtoupper(trim($this->codigo_cie10)),
            'alergias' => trim($this->alergias),
            'signos_alarma' => trim((string) $this->signos_alarma) ?: null,
            'recomendaciones' => trim((string) $this->recomendaciones) ?: null,
        ], $items);

        app(AuditoriaService::class)->registrar('crear', 'receta', $receta->id, $receta->paciente_id, "Receta N.º {$receta->numeroFormateado()}");
        session()->flash('success', 'Receta creada exitosamente.');
        session()->flash('receta_creada', $receta->id);
        $this->redirect(route('turno.atender', $this->turno->id), navigate: true);
    }

    public function render()
    {
        return view('livewire.crear-receta', [
            'cie10Descripcion' => $this->codigo_cie10 ? Cie10Code::where('codigo', $this->codigo_cie10)->value('descripcion') : null,
        ])->layout('layouts.app');
    }
}
