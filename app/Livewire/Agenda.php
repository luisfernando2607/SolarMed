<?php

namespace App\Livewire;

use App\Exceptions\TurnoException;
use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\AjustesService;
use App\Services\TelemedicinaService;
use App\Services\TurnoService;
use App\Models\WhatsappMensaje;
use App\Services\WhatsappCloudService;
use App\Services\WhatsappService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Agenda extends Component
{
    public $filtroMedico = '';

    // Modal
    public ?string $modal = null; // 'crear' | 'ver'
    #[Locked]
    public ?int $citaId = null;

    // Formulario de cita
    public $fecha_hora;
    public $medico_id;
    public $tipo;
    public $duracion_min;
    public $notas;
    public string $modalidad = 'presencial';

    // Paciente
    public string $busquedaPaciente = '';
    #[Locked]
    public ?int $paciente_id = null;
    public bool $pacienteNuevo = false;
    public $nuevo_nombres;
    public $nuevo_apellidos;
    public $nuevo_cedula;
    public $nuevo_telefono;
    public $nuevo_sexo = 'femenino';

    public function mount(): void
    {
        // Un médico ve por defecto su propia agenda
        if (!Auth::user()->can('turnos.gestionar')) {
            $this->filtroMedico = (string) (Medico::where('user_id', Auth::id())->value('id') ?? '');
        }
    }

    /** Eventos para FullCalendar en el rango visible */
    public function eventos(string $inicio, string $fin): array
    {
        $inicio = Carbon::parse($inicio)->setTimezone(config('app.timezone'));
        $fin = Carbon::parse($fin)->setTimezone(config('app.timezone'));

        return Cita::with('paciente', 'medico', 'especialidad')
            ->whereBetween('fecha_hora', [$inicio, $fin])
            ->when($this->filtroMedico !== '', fn ($q) => $q->where('medico_id', (int) $this->filtroMedico))
            ->get()
            ->map(fn (Cita $c) => [
                'id' => $c->id,
                'title' => ($c->modalidad === 'telemedicina' ? 'Video · ' : '') . ($c->origen === 'web' && $c->estado === 'pendiente' ? 'En línea · ' : '') . ($c->paciente?->nombre_visible ?? 'Paciente') . ' · ' . $c->tipo,
                'start' => $c->fecha_hora->toIso8601String(),
                'end' => $c->fecha_hora->copy()->addMinutes($c->duracion_min)->toIso8601String(),
                'backgroundColor' => $c->estado === 'cancelada' ? '#9CA3AF' : ($c->color ?: $c->especialidad?->color_agenda ?: '#3B82F6'),
                'borderColor' => $c->estado === 'confirmada' ? '#065F46' : 'transparent',
                'classNames' => ['cita-' . $c->estado],
                'editable' => Auth::user()->can('turnos.gestionar') && in_array($c->estado, ['pendiente', 'confirmada'], true),
                'extendedProps' => ['estado' => $c->estado, 'medico' => $c->medico?->nombre_completo],
            ])
            ->all();
    }

    public function updatedFiltroMedico(): void
    {
        $this->dispatch('agenda-actualizada');
    }

    public function nuevaCita(string $fechaHora): void
    {
        $this->authorize('turnos.gestionar');
        $this->resetFormulario();
        $this->tipo = $this->tipos()[0] ?? null;
        $this->duracion_min = (int) app(AjustesService::class)->get('agenda.duracion_defecto');
        $this->fecha_hora = Carbon::parse($fechaHora)->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i');
        $this->medico_id = $this->filtroMedico !== '' ? (int) $this->filtroMedico : null;
        $this->modal = 'crear';
    }

    public function verCita(int $id): void
    {
        $this->resetFormulario();
        $this->citaId = Cita::findOrFail($id)->id;
        $this->modal = 'ver';
    }

    public function cerrarModal(): void
    {
        $this->modal = null;
        $this->resetFormulario();
    }

    public function seleccionarPaciente(int $id): void
    {
        $this->paciente_id = Paciente::findOrFail($id)->id;
        $this->busquedaPaciente = '';
        $this->pacienteNuevo = false;
    }

    public function quitarPaciente(): void
    {
        $this->paciente_id = null;
    }

    public function guardarCita(): void
    {
        $this->authorize('turnos.gestionar');

        $reglas = [
            'fecha_hora' => 'required|date',
            'medico_id' => 'required|integer|exists:medicos,id',
            'tipo' => ['required', Rule::in($this->tipos())],
            'duracion_min' => 'required|integer|min:10|max:240',
            'notas' => 'nullable|string|max:500',
            'modalidad' => ['required', Rule::in(app(TelemedicinaService::class)->activa() ? ['presencial', 'telemedicina'] : ['presencial'])],
        ];
        if ($this->pacienteNuevo) {
            $reglas += [
                'nuevo_nombres' => 'required|string|max:100',
                'nuevo_apellidos' => 'required|string|max:100',
                'nuevo_cedula' => 'required|string|max:13|unique:pacientes,cedula',
                'nuevo_telefono' => 'required|string|max:15',
                'nuevo_sexo' => 'required|in:masculino,femenino,otro',
            ];
        }
        $this->validate($reglas, [
            'medico_id.required' => 'Seleccione el médico.',
            'nuevo_cedula.unique' => 'Ya existe un paciente con esta cédula; búsquelo.',
        ]);

        if (!$this->pacienteNuevo && !$this->paciente_id) {
            throw ValidationException::withMessages(['busquedaPaciente' => 'Seleccione un paciente o registre uno nuevo.']);
        }

        $inicio = Carbon::parse($this->fecha_hora);
        $this->verificarDisponibilidad((int) $this->medico_id, $inicio, (int) $this->duracion_min);

        if ($this->pacienteNuevo) {
            $this->paciente_id = Paciente::create([
                'nombres' => $this->nuevo_nombres,
                'apellidos' => $this->nuevo_apellidos,
                'cedula' => $this->nuevo_cedula,
                'telefono' => $this->nuevo_telefono,
                'sexo' => $this->nuevo_sexo,
            ])->id;
        }

        $medico = Medico::findOrFail($this->medico_id);
        $cita = Cita::create([
            'paciente_id' => $this->paciente_id,
            'medico_id' => $medico->id,
            'especialidad_id' => $medico->especialidad_id,
            'tipo' => $this->tipo,
            'fecha_hora' => $inicio,
            'duracion_min' => (int) $this->duracion_min,
            'estado' => 'pendiente',
            'modalidad' => $this->modalidad,
            'notas' => $this->notas,
        ]);
        // Videoconsulta: se crea la sala y el enlace del paciente (Norma Técnica de Telesalud, num. 7.1.3)
        if ($this->modalidad === 'telemedicina') {
            app(TelemedicinaService::class)->crearParaCita($cita, Auth::id());
        }

        $this->cerrarModal();
        $this->dispatch('agenda-actualizada');
        $this->dispatch('notify', message: 'Cita agendada.', type: 'success');
    }

    /** Arrastrar y soltar o cambiar la duración en el calendario */
    public function mover(int $id, string $inicio, ?string $fin = null): bool
    {
        $this->authorize('turnos.gestionar');
        $cita = Cita::findOrFail($id);
        if (!in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            return false;
        }

        $nuevoInicio = Carbon::parse($inicio)->setTimezone(config('app.timezone'));
        $duracion = $fin ? max(10, (int) $nuevoInicio->diffInMinutes(Carbon::parse($fin))) : $cita->duracion_min;

        try {
            $this->verificarDisponibilidad($cita->medico_id, $nuevoInicio, $duracion, $cita->id);
        } catch (ValidationException $e) {
            $this->dispatch('notify', message: collect($e->errors())->flatten()->first(), type: 'error');
            return false;
        }

        $cita->update([
            'fecha_hora' => $nuevoInicio,
            'duracion_min' => $duracion,
            // Si cambió el día, el recordatorio debe enviarse de nuevo con la nueva fecha
            'recordatorio_enviado_at' => $cita->fecha_hora->isSameDay($nuevoInicio) ? $cita->recordatorio_enviado_at : null,
            'whatsapp_recordatorio_at' => $cita->fecha_hora->isSameDay($nuevoInicio) ? $cita->whatsapp_recordatorio_at : null,
        ]);
        $cita->teleconsulta()->where('estado', 'programada')->update(['fecha_hora' => $nuevoInicio, 'duracion_min' => $duracion]);
        $this->dispatch('notify', message: 'Cita reprogramada para el ' . $nuevoInicio->format('d/m/Y H:i') . '.', type: 'success');
        return true;
    }

    public function cambiarEstado(string $estado): void
    {
        $this->authorize('turnos.gestionar');
        abort_unless(in_array($estado, ['confirmada', 'cancelada', 'pendiente'], true), 422);

        $cita = Cita::findOrFail($this->citaId);
        $confirmaReservaWeb = $estado === 'confirmada' && $cita->estado === 'pendiente' && $cita->origen === 'web';
        $cita->update(['estado' => $estado]);
        // La teleconsulta sigue a la cita: cancelada si se cancela, programada de nuevo si se reactiva
        if ($estado === 'cancelada') {
            $cita->teleconsulta()->where('estado', 'programada')->update(['estado' => 'cancelada']);
        } elseif ($estado === 'pendiente') {
            $cita->teleconsulta()->where('estado', 'cancelada')->update(['estado' => 'programada']);
        }

        // La cita la reservó el paciente en línea: se le avisa por WhatsApp que quedó confirmada
        $aviso = '';
        $wa = app(WhatsappCloudService::class);
        if ($confirmaReservaWeb && $wa->usoActivo('confirmacion')) {
            [$mensaje, $motivo] = $wa->confirmacionReserva($cita);
            $aviso = $mensaje ? ' Se avisó al paciente por WhatsApp.' : " No se avisó por WhatsApp: {$motivo}";
        }

        $this->dispatch('agenda-actualizada');
        $this->dispatch('notify', message: 'Cita ' . ($estado === 'cancelada' ? 'cancelada' : 'actualizada') . '.' . $aviso, type: 'success');
    }

    /** Recordatorio por la API oficial de WhatsApp (cuando el módulo está activo) */
    public function enviarWhatsapp(): void
    {
        $this->authorize('turnos.gestionar');
        $cita = Cita::with('paciente', 'medico')->findOrFail($this->citaId);
        $wa = app(WhatsappCloudService::class);

        if (!$wa->usoActivo('recordatorio') || !in_array($cita->estado, ['pendiente', 'confirmada'], true) || !$cita->fecha_hora->isFuture()) {
            $this->dispatch('notify', message: 'No se puede enviar el recordatorio de esta cita.', type: 'error');
            return;
        }

        [$mensaje, $motivo] = $wa->recordatorioCita($cita);
        if (!$mensaje) {
            $this->dispatch('notify', message: $motivo, type: 'error');
            return;
        }
        $cita->update(['whatsapp_recordatorio_at' => now()]);
        $this->dispatch('notify', message: 'Recordatorio en cola para enviarse por WhatsApp.', type: 'success');
    }

    /** El paciente llegó: se crea su turno y pasa a la sala de espera */
    public function registrarLlegada(): void
    {
        $this->authorize('turnos.gestionar');
        $cita = Cita::with('paciente')->findOrFail($this->citaId);
        $p = $cita->paciente;

        try {
            $turno = app(TurnoService::class)->registrarDesdeQR([
                'nombres' => $p->nombres,
                'apellidos' => $p->apellidos,
                'cedula' => $p->cedula,
                'telefono' => $p->telefono ?? '',
                'sexo' => $p->sexo,
                'especialidad_id' => $cita->especialidad_id,
                'motivo' => mb_substr("Cita: {$cita->tipo}", 0, 100),
            ]);
        } catch (TurnoException $e) {
            $this->dispatch('notify', message: $e->getMessage(), type: 'error');
            return;
        }

        $cita->update(['estado' => 'completada']);
        $this->cerrarModal();
        $this->dispatch('agenda-actualizada');
        $this->dispatch('notify', message: "Llegada registrada. Turno {$turno->prefijo}{$turno->numero_turno} en sala de espera.", type: 'success');
    }

    /** @return string[] */
    public function tipos(): array
    {
        return app(AjustesService::class)->get('agenda.tipos');
    }

    private function verificarDisponibilidad(int $medicoId, Carbon $inicio, int $duracion, ?int $excluir = null): void
    {
        $fin = $inicio->copy()->addMinutes($duracion);

        $choque = Cita::where('medico_id', $medicoId)
            ->whereIn('estado', ['pendiente', 'confirmada'])
            ->when($excluir, fn ($q) => $q->where('id', '!=', $excluir))
            ->whereBetween('fecha_hora', [$inicio->copy()->subHours(4), $fin])
            ->get()
            ->first(fn (Cita $c) => $c->fecha_hora->lt($fin) && $c->fecha_hora->copy()->addMinutes($c->duracion_min)->gt($inicio));

        if ($choque) {
            throw ValidationException::withMessages([
                'fecha_hora' => "El médico ya tiene una cita de {$choque->fecha_hora->format('H:i')} a {$choque->fecha_hora->copy()->addMinutes($choque->duracion_min)->format('H:i')}.",
            ]);
        }
    }

    private function resetFormulario(): void
    {
        $this->reset([
            'citaId', 'fecha_hora', 'medico_id', 'tipo', 'duracion_min', 'notas', 'modalidad', 'busquedaPaciente', 'paciente_id',
            'pacienteNuevo', 'nuevo_nombres', 'nuevo_apellidos', 'nuevo_cedula', 'nuevo_telefono', 'nuevo_sexo',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $pacientes = collect();
        if ($this->modal === 'crear' && !$this->paciente_id && strlen($this->busquedaPaciente) >= 2) {
            $pacientes = Paciente::where(fn ($q) => $q
                ->where('cedula', 'like', "%{$this->busquedaPaciente}%")
                ->orWhere('nombres', 'like', "%{$this->busquedaPaciente}%")
                ->orWhere('apellidos', 'like', "%{$this->busquedaPaciente}%"))
                ->limit(8)->get();
        }

        $ajustes = app(AjustesService::class);

        return view('livewire.agenda', [
            'tipos' => $this->tipos(),
            'duraciones' => $ajustes->get('agenda.duraciones'),
            'calendario' => [
                'inicio' => $ajustes->get('agenda.hora_inicio') . ':00',
                'fin' => $ajustes->get('agenda.hora_fin') . ':00',
                'dias' => $ajustes->get('agenda.dias_laborables'),
            ],
            'medicos' => Medico::where('activo', true)->with('especialidad')->orderBy('apellidos')->get(),
            'pacientes' => $pacientes,
            'pacienteSeleccionado' => $this->paciente_id ? Paciente::find($this->paciente_id) : null,
            'cita' => $cita = $this->citaId ? Cita::with('paciente', 'medico.especialidad', 'teleconsulta')->find($this->citaId) : null,
            'telemedicinaActiva' => app(TelemedicinaService::class)->activa(),
            // Recordatorio solo para citas por venir
            'enlaceWhatsapp' => $cita && in_array($cita->estado, ['pendiente', 'confirmada'], true) && $cita->fecha_hora->isFuture()
                ? app(WhatsappService::class)->enlaceRecordatorio($cita) : null,
            // Con el módulo de WhatsApp activo, el recordatorio sale por la API y se muestra su último estado
            'whatsappApi' => app(WhatsappCloudService::class)->usoActivo('recordatorio'),
            'ultimoWhatsapp' => $cita ? WhatsappMensaje::where('cita_id', $cita->id)->latest('id')->first() : null,
        ])->layout('layouts.app');
    }
}
