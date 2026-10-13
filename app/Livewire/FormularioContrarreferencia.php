<?php

namespace App\Livewire;

use App\Models\Medico;
use App\Models\Referencia;
use App\Services\AuditoriaService;
use App\Services\MarcaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Sección "Contrarreferencia / Referencia inversa" del formulario 053 (bloques A–H) y evaluación (bloque G).
 * Saliente: se registra la respuesta que envió el establecimiento receptor (con el documento escaneado).
 * Entrante: el médico de esta clínica emite la contrarreferencia para el establecimiento que refirió.
 */
class FormularioContrarreferencia extends Component
{
    use WithFileUploads;

    #[Locked]
    public Referencia $referencia;

    public string $contra_tipo = 'contrarreferencia';
    public $contra_establecimiento;
    public $contra_institucion;
    public $contra_distrito;
    public $contra_fecha;
    public $contra_resumen;
    public $contra_hallazgos;
    public $contra_tratamientos_realizados;
    public array $contra_diagnosticos = [];
    public $contra_tratamiento_recomendado;
    public $contra_profesional;
    public $contra_profesional_cedula;
    public $referencia_justificada;
    public $derivacion_justificada;
    public $documento;

    private const MIME_PERMITIDOS = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public function mount(Referencia $referencia): void
    {
        abort_if($referencia->tieneContrarreferencia(), 403, 'Esta referencia ya tiene contrarreferencia registrada.');
        $this->referencia = $referencia;
        $this->contra_fecha = today()->format('Y-m-d');

        if ($referencia->direccion === 'entrante') {
            // Esta clínica contrarrefiere: los datos del profesional salen del médico que firma
            $medico = Medico::with('user')->where('user_id', Auth::id())->first();
            $est = app(MarcaService::class)->establecimiento();
            $this->contra_establecimiento = $est['nombre'];
            $this->contra_institucion = $est['institucion'];
            $this->contra_distrito = $est['distrito'];
            $this->contra_profesional = $medico?->nombre_completo;
            $this->contra_profesional_cedula = $medico?->user?->cedula;
        } else {
            $this->contra_establecimiento = $referencia->establecimiento_destino;
            $this->contra_institucion = $referencia->institucion_destino;
        }
        $this->contra_diagnosticos = [['descripcion' => '', 'cie10' => '', 'tipo' => 'def']];
    }

    public function agregarDiagnostico(): void
    {
        if (count($this->contra_diagnosticos) < Referencia::MAX_DIAGNOSTICOS_CONTRA) {
            $this->contra_diagnosticos[] = ['descripcion' => '', 'cie10' => '', 'tipo' => 'def'];
        }
    }

    public function quitarDiagnostico(int $i): void
    {
        unset($this->contra_diagnosticos[$i]);
        $this->contra_diagnosticos = array_values($this->contra_diagnosticos) ?: [['descripcion' => '', 'cie10' => '', 'tipo' => 'def']];
    }

    public function guardar(): void
    {
        $this->authorize('expediente.crear');
        $entrante = $this->referencia->direccion === 'entrante';

        $this->validate([
            'contra_tipo' => ['required', Rule::in(array_keys(Referencia::TIPOS_CONTRA))],
            'contra_establecimiento' => 'required|string|max:160',
            'contra_institucion' => ['nullable', Rule::in(Referencia::INSTITUCIONES)],
            'contra_distrito' => 'nullable|string|max:30',
            'contra_fecha' => 'required|date|before_or_equal:today',
            'contra_resumen' => 'required|string|max:5000',
            'contra_hallazgos' => 'nullable|string|max:5000',
            'contra_tratamientos_realizados' => 'nullable|string|max:5000',
            'contra_diagnosticos' => 'required|array|min:1|max:' . Referencia::MAX_DIAGNOSTICOS_CONTRA,
            'contra_diagnosticos.*.descripcion' => 'required|string|max:300',
            'contra_diagnosticos.*.cie10' => ['nullable', 'regex:/^[A-Za-z]\d{2}(\.?[0-9A-Za-z]{1,2})?$/'],
            'contra_diagnosticos.*.tipo' => 'required|in:pre,def',
            'contra_tratamiento_recomendado' => 'required|string|max:5000',
            'contra_profesional' => 'required|string|max:160',
            'contra_profesional_cedula' => 'nullable|string|max:13',
            'referencia_justificada' => 'nullable|in:si,no',
            'derivacion_justificada' => 'nullable|in:si,no',
            'documento' => 'nullable|file|max:' . \App\Services\ArchivoService::maxKb(),
        ], [
            'contra_resumen.required' => 'Escriba el resumen del cuadro clínico.',
            'contra_tratamiento_recomendado.required' => 'Indique el tratamiento recomendado a seguir.',
            'contra_profesional.required' => 'Indique el profesional responsable.',
            'contra_diagnosticos.max' => 'La contrarreferencia admite máximo ' . Referencia::MAX_DIAGNOSTICOS_CONTRA . ' diagnósticos.',
            'contra_diagnosticos.*.descripcion.required' => 'Escriba el diagnóstico.',
            'contra_diagnosticos.*.cie10.regex' => 'Código CIE-10 no válido (ej.: N83.2).',
        ]);

        $documento = [];
        if ($this->documento) {
            $mime = $this->documento->getMimeType();
            if (!isset(self::MIME_PERMITIDOS[$mime])) {
                $this->addError('documento', 'Formato no permitido. Solo PDF, JPG y PNG.');
                return;
            }
            $nombre = Str::uuid() . '.' . self::MIME_PERMITIDOS[$mime];
            $carpeta = "pacientes/{$this->referencia->paciente_id}/referencias";
            Storage::disk('private')->putFileAs($carpeta, $this->documento, $nombre);
            $documento = [
                'documento_respuesta_ruta' => "{$carpeta}/{$nombre}",
                'documento_respuesta_nombre' => mb_substr($this->documento->getClientOriginalName(), 0, 255),
                'documento_respuesta_mime' => $mime,
            ];
        }

        $siNo = fn ($v) => $v === null || $v === '' ? null : $v === 'si';
        $this->referencia->update($documento + [
            'contra_tipo' => $this->contra_tipo,
            'contra_establecimiento' => trim($this->contra_establecimiento),
            'contra_institucion' => $this->contra_institucion ?: null,
            'contra_distrito' => trim((string) $this->contra_distrito) ?: null,
            'contra_fecha' => $this->contra_fecha . ' ' . now()->format('H:i:s'),
            'contra_resumen' => trim($this->contra_resumen),
            'contra_hallazgos' => trim((string) $this->contra_hallazgos) ?: null,
            'contra_tratamientos_realizados' => trim((string) $this->contra_tratamientos_realizados) ?: null,
            'contra_diagnosticos' => array_map(fn ($d) => [
                'descripcion' => trim($d['descripcion']),
                'cie10' => strtoupper(trim((string) ($d['cie10'] ?? ''))),
                'tipo' => $d['tipo'],
            ], $this->contra_diagnosticos),
            'contra_tratamiento_recomendado' => trim($this->contra_tratamiento_recomendado),
            'contra_profesional' => trim($this->contra_profesional),
            'contra_profesional_cedula' => trim((string) $this->contra_profesional_cedula) ?: null,
            'contra_medico_id' => $entrante ? Medico::where('user_id', Auth::id())->value('id') : null,
            'referencia_justificada' => $siNo($this->referencia_justificada),
            'derivacion_justificada' => $siNo($this->derivacion_justificada),
            'estado' => 'contrarreferida',
        ]);

        app(AuditoriaService::class)->registrar('editar', 'referencia', $this->referencia->id, $this->referencia->paciente_id,
            ($entrante ? 'Emitió ' : 'Registró ') . Referencia::TIPOS_CONTRA[$this->contra_tipo] . ' ' . $this->referencia->numero());

        session()->flash('notify', ['message' => Referencia::TIPOS_CONTRA[$this->contra_tipo] . ' registrada.', 'type' => 'success']);
        $this->redirect(route('referencias', ['paciente' => $this->referencia->paciente_id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.formulario-contrarreferencia')->layout('layouts.app');
    }
}
