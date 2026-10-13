<?php

namespace App\Livewire;

use App\Models\Consentimiento;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PlantillaConsentimiento;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Consentimiento informado, bloque B del formulario SNS-MSP/HCU-form.024.
 * Se crea con el paciente presente; mientras no se firme se puede corregir.
 */
class CrearConsentimiento extends Component
{
    use WithFileUploads;

    #[Locked]
    public Paciente $paciente;
    #[Locked]
    public ?int $consentimientoId = null;
    #[Url(as: 'consulta')]
    #[Locked]
    public ?int $consultaId = null;

    public $plantillaId = '';
    public $servicio;
    public $tipo_atencion = 'ambulatorio';
    public $diagnostico;
    public $codigo_cie10;
    public $procedimiento;
    public $en_que_consiste;
    public $como_se_realiza;
    public $duracion_estimada;
    public $beneficios;
    public $riesgos_frecuentes;
    public $riesgos_graves;
    public $riesgos_especificos;
    public $alternativas;
    public $manejo_posterior;
    public $consecuencias_no_realizar;
    public $fecha_informacion;
    public $hora_informacion;
    public $grafico;
    public ?string $graficoActual = null;
    public bool $quitarGrafico = false;

    private const TEXTOS = [
        'servicio', 'tipo_atencion', 'diagnostico', 'codigo_cie10', 'procedimiento', 'en_que_consiste', 'como_se_realiza',
        'duracion_estimada', 'beneficios', 'riesgos_frecuentes', 'riesgos_graves', 'riesgos_especificos', 'alternativas',
        'manejo_posterior', 'consecuencias_no_realizar', 'fecha_informacion', 'hora_informacion',
    ];

    public function mount(?Paciente $paciente = null, ?Consentimiento $consentimiento = null): void
    {
        if ($consentimiento?->exists) {
            abort_unless($consentimiento->editable(), 403, 'El consentimiento ya fue firmado y no se puede modificar.');
            abort_unless($consentimiento->medico_id === $this->medicoActual()?->id, 403, 'Solo el profesional que lo registró puede corregirlo.');

            $this->paciente = $consentimiento->paciente;
            $this->consentimientoId = $consentimiento->id;
            $this->consultaId = $consentimiento->consulta_id;
            foreach (self::TEXTOS as $campo) {
                $this->{$campo} = $consentimiento->{$campo};
            }
            $this->fecha_informacion = $consentimiento->fecha_informacion?->format('Y-m-d');
            $this->graficoActual = $consentimiento->grafico_ruta;
            return;
        }

        $this->paciente = $paciente;
        $this->fecha_informacion = today()->format('Y-m-d');
        $this->hora_informacion = now()->format('H:i');
        $this->servicio = $this->medicoActual()?->especialidad?->nombre ?? 'Consulta externa';

        $consulta = $this->consultaId
            ? ExpedienteConsulta::where('paciente_id', $paciente->id)->find($this->consultaId)
            : null;
        $this->consultaId = $consulta?->id;
        if ($consulta) {
            $this->diagnostico = $consulta->diagnostico;
            $this->codigo_cie10 = $consulta->codigo_cie10;
        }

        // Punto de partida para los riesgos propios del paciente (instructivo, ítem 18): el profesional lo completa
        $this->riesgos_especificos = collect([
            filled($paciente->alergias) ? 'Alergias: ' . $paciente->alergias : null,
            filled($paciente->antecedentes) ? 'Antecedentes: ' . $paciente->antecedentes : null,
            filled($paciente->medicamentos) ? 'Medicación actual: ' . $paciente->medicamentos : null,
        ])->filter()->implode("\n") ?: null;
    }

    private function medicoActual(): ?Medico
    {
        return Medico::with('especialidad')->where('user_id', Auth::id())->first();
    }

    public function updatedPlantillaId($id): void
    {
        $plantilla = PlantillaConsentimiento::where('activo', true)->find($id);
        if (!$plantilla) {
            return;
        }
        foreach (PlantillaConsentimiento::CAMPOS as $campo) {
            $this->{$campo} = $plantilla->{$campo};
        }
        $this->dispatch('notify', message: "Se cargaron los textos de «{$plantilla->procedimiento}». Revíselos para este paciente.", type: 'info');
    }

    protected function rules(): array
    {
        return [
            'servicio' => 'required|string|max:120',
            'tipo_atencion' => 'required|in:ambulatorio,hospitalizacion',
            'diagnostico' => 'required|string|max:500',
            'codigo_cie10' => 'nullable|string|max:10',
            'procedimiento' => 'required|string|max:200',
            'en_que_consiste' => 'required|string|max:5000',
            'como_se_realiza' => 'required|string|max:5000',
            'duracion_estimada' => 'required|string|max:100',
            'beneficios' => 'required|string|max:5000',
            'riesgos_frecuentes' => 'required|string|max:5000',
            'riesgos_graves' => 'required|string|max:5000',
            'riesgos_especificos' => 'nullable|string|max:5000',
            'alternativas' => 'required|string|max:5000',
            'manejo_posterior' => 'required|string|max:5000',
            'consecuencias_no_realizar' => 'required|string|max:5000',
            'fecha_informacion' => 'required|date|before_or_equal:today',
            'hora_informacion' => ['nullable', 'date_format:H:i'],
            'grafico' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => 'Este campo es obligatorio (si no aplica, escriba «NA»).',
            'grafico.image' => 'El gráfico debe ser una imagen PNG o JPG.',
            'grafico.max' => 'El gráfico no debe superar 2 MB.',
        ];
    }

    public function guardarComoPlantilla(): void
    {
        $this->authorize('expediente.crear');
        $this->validate([
            'procedimiento' => 'required|string|max:200',
            'en_que_consiste' => 'required|string|max:5000',
        ], ['required' => 'Escriba al menos el procedimiento y en qué consiste para guardar la plantilla.']);

        $datos = collect(PlantillaConsentimiento::CAMPOS)->mapWithKeys(fn ($c) => [$c => $this->{$c} ?: null])->all();
        $plantilla = PlantillaConsentimiento::create($datos + ['activo' => true, 'created_by' => Auth::id()]);
        app(AuditoriaService::class)->registrar('crear', 'plantilla_consentimiento', $plantilla->id, null, $plantilla->procedimiento);

        $this->plantillaId = (string) $plantilla->id;
        $this->dispatch('notify', message: 'Plantilla guardada. Podrá usarla con otros pacientes.', type: 'success');
    }

    public function guardar(): void
    {
        $this->authorize('expediente.crear');
        $this->validate();

        $medico = $this->medicoActual();
        if (!$medico) {
            $this->addError('procedimiento', 'Necesita un perfil de médico para registrar consentimientos.');
            return;
        }

        $datos = collect(self::TEXTOS)->mapWithKeys(fn ($c) => [$c => filled($this->{$c}) ? $this->{$c} : null])->all();
        $datos['hora_informacion'] = $this->hora_informacion ?: null;

        if ($this->grafico) {
            $datos['grafico_ruta'] = $this->grafico->storeAs("pacientes/{$this->paciente->id}/consentimientos",
                Str::uuid() . '.' . $this->grafico->extension(), 'private');
        } elseif ($this->quitarGrafico) {
            $datos['grafico_ruta'] = null;
        }

        if ($this->consentimientoId) {
            $consentimiento = Consentimiento::findOrFail($this->consentimientoId);
            abort_unless($consentimiento->medico_id === $medico->id, 403);
            $anterior = $consentimiento->grafico_ruta;
            $consentimiento->update($datos);
            if ($anterior && $anterior !== $consentimiento->grafico_ruta) {
                Storage::disk('private')->delete($anterior);
            }
            app(AuditoriaService::class)->registrar('editar', 'consentimiento', $consentimiento->id, $this->paciente->id, $consentimiento->procedimiento);
        } else {
            $consentimiento = Consentimiento::create($datos + [
                'paciente_id' => $this->paciente->id,
                'medico_id' => $medico->id,
                'consulta_id' => $this->consultaId,
                'user_id' => Auth::id(),
                'estado' => 'pendiente_firma',
            ]);
            app(AuditoriaService::class)->registrar('crear', 'consentimiento', $consentimiento->id, $this->paciente->id, $consentimiento->procedimiento);
        }

        session()->flash('notify', ['message' => 'Consentimiento guardado. Imprímalo para la firma del paciente.', 'type' => 'success']);
        $this->redirect(route('consentimientos.ver', $consentimiento), navigate: true);
    }

    public function render()
    {
        return view('livewire.crear-consentimiento', [
            'plantillas' => PlantillaConsentimiento::where('activo', true)->orderBy('procedimiento')->get(['id', 'procedimiento']),
            'menorDeEdad' => $this->paciente->edad !== null && $this->paciente->edad < 18,
        ])->layout('layouts.app');
    }
}
