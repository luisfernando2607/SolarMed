<?php

namespace App\Livewire;

use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PacienteArchivo;
use App\Services\ArchivoService;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ArchivosPaciente extends Component
{
    use WithFileUploads;

    public Paciente $paciente;
    public $archivo;
    public $categoria = 'laboratorio';
    public $descripcion;
    public $filtro = '';

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
        app(AuditoriaService::class)->registrar('ver', 'archivos', null, $paciente->id, 'Lista de archivos del paciente');
    }

    public function subir(): void
    {
        $this->authorize('expediente.crear');
        $this->validate([
            'archivo' => 'required|file|max:' . ArchivoService::MAX_KB . '|mimetypes:' . implode(',', array_keys(ArchivoService::MIME_PERMITIDOS)),
            'categoria' => ['required', Rule::in(array_keys(ArchivoService::CATEGORIAS))],
            'descripcion' => 'nullable|string|max:255',
        ], [
            'archivo.required' => 'Seleccione un archivo.',
            'archivo.max' => 'El archivo supera el límite de 5 MB.',
            'archivo.mimetypes' => 'Formato no permitido. Solo PDF, JPG y PNG.',
        ]);

        $guardado = app(ArchivoService::class)->subir(
            $this->archivo,
            $this->paciente->id,
            Medico::where('user_id', Auth::id())->value('id'),
            Auth::id(),
            $this->categoria,
            descripcion: $this->descripcion ?: null,
        );

        app(AuditoriaService::class)->registrar('crear', 'archivo', $guardado->id, $this->paciente->id, $guardado->nombre_original);
        $this->reset(['archivo', 'descripcion']);
        $this->dispatch('notify', message: 'Archivo subido.', type: 'success');
    }

    public function eliminar(int $id): void
    {
        $archivo = PacienteArchivo::where('paciente_id', $this->paciente->id)->findOrFail($id);

        // Solo quien lo subió (para corregir un error) o un administrador
        abort_unless($archivo->user_id === Auth::id() || Auth::user()->hasRole('admin'), 403);

        app(AuditoriaService::class)->registrar('eliminar', 'archivo', $archivo->id, $this->paciente->id, $archivo->nombre_original);
        app(ArchivoService::class)->eliminar($archivo);
        $this->dispatch('notify', message: 'Archivo eliminado.', type: 'success');
    }

    public function render()
    {
        $archivos = PacienteArchivo::where('paciente_id', $this->paciente->id)
            ->when(array_key_exists($this->filtro, ArchivoService::CATEGORIAS), fn ($q) => $q->where('categoria', $this->filtro))
            ->with('user')
            ->latest()
            ->get();

        return view('livewire.archivos-paciente', [
            'archivos' => $archivos,
            'categorias' => ArchivoService::CATEGORIAS,
        ])->layout('layouts.app');
    }
}
