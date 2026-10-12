<?php

namespace App\Livewire;

use App\Models\ExpedienteConsulta;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PedidoExamen;
use App\Services\AjustesService;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

class CrearPedidoExamenes extends Component
{
    public Paciente $paciente;
    #[Url(as: 'consulta')]
    #[Locked]
    public ?int $consultaId = null;

    public array $laboratorio = [];
    public array $imagen = [];
    public $otros;
    public $diagnostico;
    public $codigo_cie10;
    public $indicaciones;

    public ?int $emitidoId = null;

    public const INDICACIONES_RAPIDAS = [
        'Acudir en ayunas de 8 a 12 horas.',
        'Recolectar la primera orina de la mañana.',
        'Acudir con vejiga llena.',
        'Traer resultados a la próxima consulta.',
    ];

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
        $consulta = $this->consultaId ? ExpedienteConsulta::where('paciente_id', $paciente->id)->find($this->consultaId) : null;
        $this->consultaId = $consulta?->id;
        $this->diagnostico = $consulta?->diagnostico;
        $this->codigo_cie10 = $consulta?->codigo_cie10;
    }

    public function agregarIndicacion(int $i): void
    {
        $texto = self::INDICACIONES_RAPIDAS[$i] ?? null;
        if ($texto && !str_contains((string) $this->indicaciones, $texto)) {
            $this->indicaciones = trim(trim((string) $this->indicaciones) . "\n" . $texto);
        }
    }

    public function emitir(): void
    {
        $this->authorize('expediente.crear');
        $catalogo = app(AjustesService::class);

        $this->validate([
            'laboratorio' => 'array',
            'laboratorio.*' => [Rule::in($catalogo->get('examenes.laboratorio'))],
            'imagen' => 'array',
            'imagen.*' => [Rule::in($catalogo->get('examenes.imagen'))],
            'otros' => 'nullable|string|max:1000',
            'diagnostico' => 'nullable|string|max:500',
            'codigo_cie10' => 'nullable|string|max:10',
            'indicaciones' => 'nullable|string|max:1000',
        ]);

        if (!$this->laboratorio && !$this->imagen && !trim((string) $this->otros)) {
            $this->addError('laboratorio', 'Seleccione al menos un examen o escriba otros exámenes.');
            return;
        }

        $medico = Medico::where('user_id', Auth::id())->first();
        if (!$medico) {
            $this->addError('laboratorio', 'Necesita un perfil de médico para emitir pedidos de exámenes.');
            return;
        }

        // Conserva el orden del catálogo
        $ordenar = fn (array $sel, array $cat) => array_values(array_intersect($cat, $sel));
        $pedido = PedidoExamen::create([
            'paciente_id' => $this->paciente->id,
            'medico_id' => $medico->id,
            'consulta_id' => $this->consultaId,
            'user_id' => Auth::id(),
            'fecha' => today(),
            'laboratorio' => $ordenar($this->laboratorio, $catalogo->get('examenes.laboratorio')),
            'imagen' => $ordenar($this->imagen, $catalogo->get('examenes.imagen')),
            'otros' => trim((string) $this->otros) ?: null,
            'diagnostico' => $this->diagnostico ?: null,
            'codigo_cie10' => $this->codigo_cie10 ?: null,
            'indicaciones' => trim((string) $this->indicaciones) ?: null,
        ]);

        app(AuditoriaService::class)->registrar('crear', 'pedido_examenes', $pedido->id, $this->paciente->id, $pedido->totalExamenes() . ' examen(es)');
        $this->emitidoId = $pedido->id;
    }

    public function nuevo(): void
    {
        $this->reset(['laboratorio', 'imagen', 'otros', 'indicaciones', 'emitidoId']);
    }

    public function render()
    {
        $catalogo = app(AjustesService::class);

        return view('livewire.crear-pedido-examenes', [
            'catalogoLaboratorio' => $catalogo->get('examenes.laboratorio'),
            'catalogoImagen' => $catalogo->get('examenes.imagen'),
            'anteriores' => PedidoExamen::where('paciente_id', $this->paciente->id)->with('medico')->latest('id')->take(10)->get(),
        ])->layout('layouts.app');
    }
}
