<?php

namespace App\Livewire;

use App\Models\Especialidad;
use App\Services\AuditoriaService;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GestionEspecialidades extends Component
{
    public bool $modal = false;
    #[Locked]
    public ?int $editId = null;

    public $nombre;
    public $codigo;
    public $prefijo_turno;
    public $color_agenda = '#3B82F6';
    public bool $activo = true;
    public $motivosTexto = '';

    public function crear(): void
    {
        $this->authorize('configuracion.editar');
        $this->resetFormulario();
        $this->motivosTexto = "Consulta\nControl\nOtro";
        $this->modal = true;
    }

    public function editar(int $id): void
    {
        $this->authorize('configuracion.editar');
        $esp = Especialidad::findOrFail($id);
        $this->resetFormulario();
        $this->editId = $esp->id;
        $this->nombre = $esp->nombre;
        $this->codigo = $esp->codigo;
        $this->prefijo_turno = $esp->prefijo_turno;
        $this->color_agenda = $esp->color_agenda ?: '#3B82F6';
        $this->activo = $esp->activo;
        $this->motivosTexto = implode("\n", $esp->motivos ?? []);
        $this->modal = true;
    }

    public function updatedNombre(): void
    {
        if (!$this->editId) {
            $this->codigo = Str::slug((string) $this->nombre, '_');
        }
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');
        $this->prefijo_turno = mb_strtoupper(trim((string) $this->prefijo_turno));

        $this->validate([
            'nombre' => 'required|string|max:100',
            'codigo' => ['required', 'alpha_dash', 'max:20', Rule::unique('especialidades', 'codigo')->ignore($this->editId)],
            'prefijo_turno' => ['required', 'regex:/^[A-Z]{1,3}$/', Rule::unique('especialidades', 'prefijo_turno')->ignore($this->editId)],
            'color_agenda' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'motivosTexto' => 'required|string|max:3000',
        ], [
            'prefijo_turno.regex' => 'Use de 1 a 3 letras, por ejemplo G u OB.',
            'prefijo_turno.unique' => 'Otra especialidad ya usa este prefijo.',
            'codigo.unique' => 'Ya existe una especialidad con este código.',
            'motivosTexto.required' => 'Agregue al menos un motivo de consulta.',
        ]);

        $motivos = collect(preg_split('/\R/', (string) $this->motivosTexto))
            ->map(fn ($m) => mb_substr(trim($m), 0, 100))
            ->filter()->unique()->values()->all();
        if (!$motivos) {
            $this->addError('motivosTexto', 'Agregue al menos un motivo de consulta.');
            return;
        }

        $datos = [
            'nombre' => $this->nombre,
            'prefijo_turno' => $this->prefijo_turno,
            'color_agenda' => $this->color_agenda,
            'activo' => $this->activo,
            'motivos' => $motivos,
        ];

        if ($this->editId) {
            $esp = Especialidad::findOrFail($this->editId);
            $esp->update($datos); // el código no cambia: lo usan seeders y reportes
        } else {
            $esp = Especialidad::create($datos + ['codigo' => $this->codigo]);
        }

        app(AuditoriaService::class)->registrar($this->editId ? 'editar' : 'crear', 'especialidad', $esp->id, null, $esp->nombre);
        $this->modal = false;
        $this->resetFormulario();
        $this->dispatch('notify', message: 'Especialidad guardada.', type: 'success');
    }

    public function cerrar(): void
    {
        $this->modal = false;
        $this->resetFormulario();
    }

    private function resetFormulario(): void
    {
        $this->reset(['editId', 'nombre', 'codigo', 'prefijo_turno', 'color_agenda', 'activo', 'motivosTexto']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.gestion-especialidades', [
            'especialidades' => Especialidad::withCount('medicos')->orderByDesc('activo')->orderBy('nombre')->get(),
        ])->layout('layouts.app');
    }
}
