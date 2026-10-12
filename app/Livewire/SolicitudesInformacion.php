<?php

namespace App\Livewire;

use App\Models\Paciente;
use App\Models\SolicitudInformacion;
use App\Services\ArchivoService;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Registro de solicitudes sobre la historia clínica: copias certificadas (48 h), entregas para
 * investigación (Art. 12) y derechos del titular de los datos (LOPDP, 15 días).
 */
class SolicitudesInformacion extends Component
{
    use WithFileUploads, WithPagination;

    public string $filtroEstado = 'pendiente';
    public string $busqueda = '';

    // Registro
    public bool $mostrarForm = false;
    public string $pacienteBusqueda = '';
    #[Locked]
    public ?int $paciente_id = null;
    public string $tipo = 'copia_hc';
    public string $solicitante_nombres = '';
    public string $solicitante_cedula = '';
    public string $relacion = 'titular';
    public string $entidad = '';
    public string $motivo = '';
    public string $fecha_solicitud = '';
    public $documento;

    // Respuesta
    #[Locked]
    public ?int $atendiendoId = null;
    public string $resultado = 'entregada';
    public string $respuesta = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->canAny(['auditoria.ver', 'pacientes.editar']), 403);
    }

    private function autorizarGestion(): void
    {
        abort_unless(Auth::user()->canAny(['auditoria.gestionar', 'pacientes.editar']), 403);
    }

    public function updated($propiedad): void
    {
        if (in_array($propiedad, ['filtroEstado', 'busqueda'], true)) {
            $this->resetPage();
        }
    }

    public function nueva(): void
    {
        $this->autorizarGestion();
        $this->reset(['pacienteBusqueda', 'paciente_id', 'tipo', 'solicitante_nombres', 'solicitante_cedula', 'relacion', 'entidad', 'motivo', 'documento']);
        $this->fecha_solicitud = now()->format('Y-m-d\TH:i');
        $this->resetErrorBag();
        $this->mostrarForm = true;
    }

    public function elegirPaciente(int $id): void
    {
        $paciente = Paciente::findOrFail($id);
        $this->paciente_id = $paciente->id;
        $this->pacienteBusqueda = '';
        // Por defecto el solicitante es el propio paciente
        if ($this->relacion === 'titular' && $this->solicitante_cedula === '') {
            $this->solicitante_nombres = $paciente->nombre_completo;
            $this->solicitante_cedula = (string) $paciente->cedula;
        }
    }

    public function quitarPaciente(): void
    {
        $this->paciente_id = null;
    }

    public function guardar(): void
    {
        $this->autorizarGestion();

        $investigacion = $this->tipo === 'uso_investigacion';
        $this->validate([
            'paciente_id' => 'required|exists:pacientes,id',
            'tipo' => ['required', Rule::in(array_keys(SolicitudInformacion::TIPOS))],
            'solicitante_nombres' => 'required|string|max:150',
            'solicitante_cedula' => 'required|string|max:20',
            'relacion' => ['required', Rule::in(array_keys(SolicitudInformacion::RELACIONES))],
            // Art. 12: registro de receptor, entidad en la que trabaja y razón del uso
            'entidad' => [$investigacion || $this->relacion === 'autoridad' ? 'required' : 'nullable', 'string', 'max:150'],
            'motivo' => [$investigacion ? 'required' : 'nullable', 'string', 'max:2000'],
            'fecha_solicitud' => 'required|date|before_or_equal:now',
            'documento' => 'nullable|file|max:' . ArchivoService::maxKb(),
        ], [
            'paciente_id.required' => 'Seleccione el paciente.',
            'entidad.required' => 'Indique la entidad del solicitante.',
            'motivo.required' => 'Indique la razón del uso de la información.',
            'fecha_solicitud.before_or_equal' => 'La fecha de solicitud no puede ser futura.',
        ]);

        $fecha = \Carbon\Carbon::parse($this->fecha_solicitud);
        $datos = [
            'paciente_id' => $this->paciente_id,
            'tipo' => $this->tipo,
            'solicitante_nombres' => $this->solicitante_nombres,
            'solicitante_cedula' => $this->solicitante_cedula,
            'relacion' => $this->relacion,
            'entidad' => $this->entidad ?: null,
            'motivo' => $this->motivo ?: null,
            'fecha_solicitud' => $fecha,
            'fecha_limite' => SolicitudInformacion::calcularFechaLimite($this->tipo, $fecha),
            'estado' => 'pendiente',
            'registrado_por' => Auth::id(),
        ];

        if ($this->documento) {
            $mime = $this->documento->getMimeType();
            if (!isset(ArchivoService::MIME_PERMITIDOS[$mime])) {
                $this->addError('documento', 'Formato no permitido. Solo PDF, JPG y PNG.');
                return;
            }
            $nombre = Str::uuid() . '.' . ArchivoService::MIME_PERMITIDOS[$mime];
            Storage::disk('private')->putFileAs('solicitudes', $this->documento, $nombre);
            $datos += [
                'documento_ruta' => "solicitudes/{$nombre}",
                'documento_nombre' => mb_substr($this->documento->getClientOriginalName(), 0, 255),
                'documento_mime' => $mime,
            ];
        }

        $solicitud = SolicitudInformacion::create($datos);
        app(AuditoriaService::class)->registrar('crear', 'solicitud', $solicitud->id, $solicitud->paciente_id,
            "{$solicitud->tipoTexto()} — {$solicitud->solicitante_nombres} ({$solicitud->solicitante_cedula})");

        $this->mostrarForm = false;
        $this->filtroEstado = 'pendiente';
        $this->dispatch('notify', message: 'Solicitud registrada. Plazo de respuesta: ' . $solicitud->fecha_limite->format('d/m/Y H:i') . '.', type: 'success');
    }

    public function atender(int $id): void
    {
        $this->autorizarGestion();
        $solicitud = SolicitudInformacion::findOrFail($id);
        if ($solicitud->estado !== 'pendiente') {
            return;
        }
        $this->atendiendoId = $solicitud->id;
        $this->resultado = 'entregada';
        $this->respuesta = '';
        $this->resetErrorBag();
    }

    public function cancelarAtencion(): void
    {
        $this->atendiendoId = null;
    }

    public function responder(): void
    {
        $this->autorizarGestion();
        $this->validate([
            'resultado' => 'required|in:entregada,rechazada',
            'respuesta' => [$this->resultado === 'rechazada' ? 'required' : 'nullable', 'string', 'max:2000'],
        ], ['respuesta.required' => 'Indique el motivo del rechazo.']);

        $solicitud = SolicitudInformacion::where('estado', 'pendiente')->findOrFail($this->atendiendoId);
        $solicitud->update([
            'estado' => $this->resultado,
            'respuesta' => $this->respuesta ?: null,
            'fecha_respuesta' => now(),
            'atendido_por' => Auth::id(),
        ]);

        $fuera = $solicitud->fecha_respuesta->gt($solicitud->fecha_limite) ? ' (fuera de plazo)' : '';
        app(AuditoriaService::class)->registrar('editar', 'solicitud', $solicitud->id, $solicitud->paciente_id,
            ucfirst($this->resultado) . $fuera . ": {$solicitud->tipoTexto()}");

        $this->atendiendoId = null;
        $this->dispatch('notify', message: $this->resultado === 'entregada' ? 'Solicitud marcada como entregada.' : 'Solicitud rechazada.', type: 'success');
    }

    public function render()
    {
        $limitePasivo = now()->subYears(Paciente::ANIOS_ARCHIVO_ACTIVO);

        $solicitudes = SolicitudInformacion::with('paciente', 'registrador', 'atendedor')
            ->when(array_key_exists($this->filtroEstado, SolicitudInformacion::ESTADOS), fn ($q) => $q->where('estado', $this->filtroEstado))
            ->when(trim($this->busqueda) !== '', function ($q) {
                $b = trim($this->busqueda);
                $q->where(fn ($w) => $w->where('solicitante_nombres', 'like', "%{$b}%")
                    ->orWhere('solicitante_cedula', 'like', "{$b}%")
                    ->orWhereHas('paciente', fn ($p) => $p->where('cedula', 'like', "{$b}%")
                        ->orWhere('nombres', 'like', "%{$b}%")->orWhere('apellidos', 'like', "%{$b}%")));
            })
            ->orderByRaw("case when estado = 'pendiente' then 0 else 1 end")
            ->orderBy('fecha_limite')
            ->paginate(20);

        $pendientes = SolicitudInformacion::pendientes();

        return view('livewire.solicitudes-informacion', [
            'solicitudes' => $solicitudes,
            'resumen' => [
                'pendientes' => (clone $pendientes)->count(),
                'vencidas' => (clone $pendientes)->where('fecha_limite', '<', now())->count(),
                'por_vencer' => (clone $pendientes)->whereBetween('fecha_limite', [now(), now()->addHours(SolicitudInformacion::HORAS_AVISO)])->count(),
                // AM 5216-A Art. 17: historias sin atención por más de 5 años pasan al archivo pasivo
                'hc_pasivas' => Paciente::where('created_at', '<', $limitePasivo)
                    ->whereDoesntHave('consultas', fn ($q) => $q->where('fecha', '>=', $limitePasivo))
                    ->whereDoesntHave('ecografias', fn ($q) => $q->where('fecha', '>=', $limitePasivo))
                    ->count(),
            ],
            'coincidencias' => !$this->paciente_id && mb_strlen(trim($this->pacienteBusqueda)) >= 2
                ? Paciente::where(fn ($q) => $q->where('cedula', 'like', trim($this->pacienteBusqueda) . '%')
                    ->orWhere('nombres', 'like', '%' . trim($this->pacienteBusqueda) . '%')
                    ->orWhere('apellidos', 'like', '%' . trim($this->pacienteBusqueda) . '%'))
                    ->orderBy('apellidos')->take(6)->get()
                : collect(),
            'pacienteElegido' => $this->paciente_id ? Paciente::find($this->paciente_id) : null,
            'atendiendo' => $this->atendiendoId ? SolicitudInformacion::with('paciente')->find($this->atendiendoId) : null,
            'puedeGestionar' => Auth::user()->canAny(['auditoria.gestionar', 'pacientes.editar']),
        ])->layout('layouts.app');
    }
}
