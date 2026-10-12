<?php

namespace App\Livewire;

use App\Models\CertificadoMedico;
use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class CrearCertificado extends Component
{
    public Paciente $paciente;
    #[Url(as: 'consulta')]
    #[Locked]
    public ?int $consultaId = null;

    public string $tipo = 'atencion';
    public $fecha_atencion;
    public $hora_atencion;
    public $dias_reposo = 1;
    public $reposo_desde;
    public bool $mostrar_diagnostico = true;
    public $diagnostico;
    public $codigo_cie10;
    public $observaciones;

    public ?int $emitidoId = null;

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
        $this->fecha_atencion = today()->format('Y-m-d');
        $this->hora_atencion = now()->format('H:i');
        $this->reposo_desde = today()->format('Y-m-d');

        $consulta = $this->consultaId
            ? ExpedienteConsulta::where('paciente_id', $paciente->id)->find($this->consultaId)
            : null;
        $this->consultaId = $consulta?->id;
        if ($consulta) {
            $this->fecha_atencion = $consulta->fecha->format('Y-m-d');
            $this->hora_atencion = $consulta->fecha->format('H:i');
            $this->diagnostico = $consulta->diagnostico;
            $this->codigo_cie10 = $consulta->codigo_cie10;
        }
    }

    public function getReposoHastaProperty(): ?string
    {
        if ($this->tipo !== 'reposo' || !$this->reposo_desde || (int) $this->dias_reposo < 1) {
            return null;
        }
        return Carbon::parse($this->reposo_desde)->addDays((int) $this->dias_reposo - 1)->format('Y-m-d');
    }

    public function emitir(): void
    {
        $this->authorize('expediente.crear');
        $this->validate([
            'tipo' => 'required|in:atencion,reposo',
            'fecha_atencion' => 'required|date|before_or_equal:today',
            'hora_atencion' => ['nullable', 'date_format:H:i'],
            'dias_reposo' => 'required_if:tipo,reposo|nullable|integer|min:1|max:90',
            'reposo_desde' => 'required_if:tipo,reposo|nullable|date|after_or_equal:fecha_atencion',
            'diagnostico' => 'required_if:tipo,reposo|nullable|string|max:500',
            'codigo_cie10' => 'nullable|string|max:10',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'diagnostico.required_if' => 'El certificado de reposo requiere el diagnóstico.',
            'reposo_desde.after_or_equal' => 'El reposo no puede empezar antes de la atención.',
            'dias_reposo.max' => 'Máximo 90 días por certificado.',
        ]);

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            $this->addError('tipo', 'Necesita un perfil de médico para emitir certificados.');
            return;
        }

        $reposo = $this->tipo === 'reposo';
        $cert = CertificadoMedico::create([
            'paciente_id' => $this->paciente->id,
            'medico_id' => $medico->id,
            'consulta_id' => $this->consultaId,
            'user_id' => Auth::id(),
            'tipo' => $this->tipo,
            'fecha_emision' => today(),
            'fecha_atencion' => $this->fecha_atencion,
            'hora_atencion' => $this->hora_atencion ?: null,
            'dias_reposo' => $reposo ? (int) $this->dias_reposo : null,
            'reposo_desde' => $reposo ? $this->reposo_desde : null,
            'reposo_hasta' => $reposo ? $this->reposoHasta : null,
            'mostrar_diagnostico' => $this->mostrar_diagnostico,
            'diagnostico' => $this->diagnostico ?: null,
            'codigo_cie10' => $this->codigo_cie10 ?: null,
            'observaciones' => $this->observaciones ?: null,
        ]);

        app(AuditoriaService::class)->registrar('crear', 'certificado', $cert->id, $this->paciente->id,
            $reposo ? "Reposo {$cert->dias_reposo} día(s)" : 'Certificado de atención');
        $this->emitidoId = $cert->id;
    }

    public function nuevo(): void
    {
        $this->emitidoId = null;
    }

    public function render()
    {
        return view('livewire.crear-certificado', [
            'anteriores' => CertificadoMedico::where('paciente_id', $this->paciente->id)->with('medico')->latest('id')->take(10)->get(),
        ])->layout('layouts.app');
    }
}
