<?php

namespace App\Livewire;

use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Referencia;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Sección "Referencia / Derivación" del formulario SNS-MSP/HCU-form.053/2021 (bloques A–F).
 * Saliente: esta clínica envía al paciente. Entrante: se registra la referencia recibida de otro
 * establecimiento para luego emitir la contrarreferencia.
 */
class FormularioReferencia extends Component
{
    #[Locked]
    public Paciente $paciente;
    #[Locked]
    public ?int $referenciaId = null;
    #[Url(as: 'consulta')]
    #[Locked]
    public ?int $consultaId = null;

    public string $direccion = 'saliente';
    public $establecimiento_origen;
    public $institucion_origen;
    public $profesional_origen;
    public string $tipo = 'referencia';
    public $motivo;
    public $institucion_destino = 'Ministerio de Salud Pública (MSP)';
    public $establecimiento_destino;
    public $servicio = 'Consulta externa';
    public $especialidad;
    public $resumen_cuadro_clinico;
    public $hallazgos;
    public array $diagnosticos = [];

    public function mount(?Paciente $paciente = null, ?Referencia $referencia = null): void
    {
        if ($referencia?->exists) {
            abort_unless($referencia->editable(), 403, 'La referencia ya fue contrarreferida y no se puede editar.');
            $this->paciente = $referencia->paciente;
            $this->referenciaId = $referencia->id;
            $this->consultaId = $referencia->consulta_id;
            foreach (['direccion', 'establecimiento_origen', 'institucion_origen', 'profesional_origen', 'tipo', 'motivo', 'institucion_destino',
                'establecimiento_destino', 'servicio', 'especialidad', 'resumen_cuadro_clinico', 'hallazgos'] as $campo) {
                $this->{$campo} = $referencia->{$campo};
            }
            $this->diagnosticos = $referencia->diagnosticos ?: [];
        } else {
            $this->paciente = $paciente;
            $consulta = $this->consultaId
                ? ExpedienteConsulta::where('paciente_id', $paciente->id)->find($this->consultaId)
                : null;
            $this->consultaId = $consulta?->id;
            if ($consulta) {
                $this->prellenarDesdeConsulta($consulta);
            }
        }

        if (!$this->diagnosticos) {
            $this->agregarDiagnostico();
        }
    }

    /** Toma de la consulta la especialidad de derivación, el diagnóstico y el cuadro clínico */
    private function prellenarDesdeConsulta(ExpedienteConsulta $c): void
    {
        $this->especialidad = $c->derivacion_especialidad ?: null;
        $this->resumen_cuadro_clinico = trim(implode("\n", array_filter([
            $c->motivo_consulta ? "Motivo de consulta: {$c->motivo_consulta}" : null,
            $c->anamnesis ? "Enfermedad actual: {$c->anamnesis}" : null,
            $c->tratamiento ? "Tratamiento instaurado: {$c->tratamiento}" : null,
        ]))) ?: null;
        $this->hallazgos = ($c->examen_fisico['notas'] ?? null) ?: null;
        if ($c->diagnostico) {
            $this->diagnosticos = [[
                'descripcion' => $c->diagnostico,
                'cie10' => (string) $c->codigo_cie10,
                'tipo' => $c->tipo_diagnostico === 'definitivo' ? 'def' : 'pre',
            ]];
        }
    }

    public function agregarDiagnostico(): void
    {
        if (count($this->diagnosticos) < Referencia::MAX_DIAGNOSTICOS) {
            $this->diagnosticos[] = ['descripcion' => '', 'cie10' => '', 'tipo' => 'pre'];
        }
    }

    public function quitarDiagnostico(int $i): void
    {
        unset($this->diagnosticos[$i]);
        $this->diagnosticos = array_values($this->diagnosticos);
        if (!$this->diagnosticos) {
            $this->agregarDiagnostico();
        }
    }

    protected function rules(): array
    {
        $entrante = $this->direccion === 'entrante';

        return [
            'direccion' => 'required|in:saliente,entrante',
            'establecimiento_origen' => [Rule::requiredIf($entrante), 'nullable', 'string', 'max:160'],
            'institucion_origen' => ['nullable', Rule::in(Referencia::INSTITUCIONES)],
            'profesional_origen' => 'nullable|string|max:160',
            'tipo' => ['required', Rule::in(array_keys(Referencia::TIPOS))],
            'motivo' => ['required', 'integer', Rule::in(array_keys(Referencia::MOTIVOS))],
            'institucion_destino' => ['required', Rule::in(Referencia::INSTITUCIONES)],
            'establecimiento_destino' => [Rule::requiredIf(!$entrante), 'nullable', 'string', 'max:160'],
            'servicio' => 'nullable|string|max:120',
            'especialidad' => 'required|string|max:120',
            'resumen_cuadro_clinico' => 'required|string|max:5000',
            'hallazgos' => 'nullable|string|max:5000',
            'diagnosticos' => 'required|array|min:1|max:' . Referencia::MAX_DIAGNOSTICOS,
            'diagnosticos.*.descripcion' => 'required|string|max:300',
            'diagnosticos.*.cie10' => ['nullable', 'regex:/^[A-Za-z]\d{2}(\.?[0-9A-Za-z]{1,2})?$/'],
            'diagnosticos.*.tipo' => 'required|in:pre,def',
        ];
    }

    protected function messages(): array
    {
        return [
            'motivo.required' => 'Marque el motivo de la referencia o derivación.',
            'establecimiento_origen.required' => 'Indique el establecimiento que refiere al paciente.',
            'establecimiento_destino.required' => 'Indique el establecimiento al que se refiere.',
            'especialidad.required' => 'Indique la especialidad a la que se refiere.',
            'resumen_cuadro_clinico.required' => 'Escriba el resumen del cuadro clínico.',
            'diagnosticos.max' => 'El formulario 053 admite máximo ' . Referencia::MAX_DIAGNOSTICOS . ' diagnósticos.',
            'diagnosticos.*.descripcion.required' => 'Escriba el diagnóstico.',
            'diagnosticos.*.cie10.regex' => 'Código CIE-10 no válido (ej.: N83.2).',
        ];
    }

    public function guardar(): void
    {
        $this->authorize('expediente.crear');
        $this->validate();

        $datos = [
            'direccion' => $this->direccion,
            'establecimiento_origen' => $this->direccion === 'entrante' ? trim((string) $this->establecimiento_origen) : null,
            'institucion_origen' => $this->direccion === 'entrante' ? ($this->institucion_origen ?: null) : null,
            'profesional_origen' => $this->direccion === 'entrante' ? (trim((string) $this->profesional_origen) ?: null) : null,
            'tipo' => $this->tipo,
            'motivo' => (int) $this->motivo,
            'institucion_destino' => $this->institucion_destino,
            'establecimiento_destino' => trim((string) $this->establecimiento_destino) ?: null,
            'servicio' => trim((string) $this->servicio) ?: null,
            'especialidad' => trim($this->especialidad),
            'resumen_cuadro_clinico' => trim($this->resumen_cuadro_clinico),
            'hallazgos' => trim((string) $this->hallazgos) ?: null,
            'diagnosticos' => array_map(fn ($d) => [
                'descripcion' => trim($d['descripcion']),
                'cie10' => strtoupper(trim((string) ($d['cie10'] ?? ''))),
                'tipo' => $d['tipo'],
            ], $this->diagnosticos),
        ];

        if ($this->referenciaId) {
            $referencia = Referencia::findOrFail($this->referenciaId);
            abort_unless($referencia->editable(), 403);
            $referencia->update($datos);
            app(AuditoriaService::class)->registrar('editar', 'referencia', $referencia->id, $referencia->paciente_id, $referencia->numero());
        } else {
            $medico = Medico::where('user_id', Auth::id())->first();
            if (!$medico) {
                $this->addError('motivo', 'Necesita un perfil de médico para emitir referencias.');
                return;
            }
            $referencia = Referencia::create($datos + [
                'paciente_id' => $this->paciente->id,
                'consulta_id' => $this->consultaId,
                'medico_id' => $medico->id,
                'user_id' => Auth::id(),
                'fecha' => now(),
                'estado' => 'enviada',
            ]);
            app(AuditoriaService::class)->registrar('crear', 'referencia', $referencia->id, $this->paciente->id,
                Referencia::TIPOS[$referencia->tipo] . ($referencia->direccion === 'entrante' ? ' recibida' : ' a ' . $referencia->especialidad));
        }

        session()->flash('notify', ['message' => 'Referencia guardada.', 'type' => 'success']);
        $this->redirect(route('referencias', ['paciente' => $this->paciente->id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.formulario-referencia')->layout('layouts.app');
    }
}
