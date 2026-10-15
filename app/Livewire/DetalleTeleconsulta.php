<?php

namespace App\Livewire;

use App\Models\Medico;
use App\Models\Teleconsulta;
use App\Services\AuditoriaService;
use App\Services\TelemedicinaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Teleconsulta para el personal: enlace del paciente, autorización y consentimiento (num. 7.2.2),
 * inicio de la videoconsulta, registro en la historia clínica y cierre con la efectividad (num. 7.3.4).
 */
class DetalleTeleconsulta extends Component
{
    #[Locked]
    public int $teleconsultaId;

    public string $sala_url = '';
    public bool $confirmaVerbal = false;
    public string $representante = '';
    public string $resultado = 'efectiva';
    public string $observaciones = '';

    public function mount(Teleconsulta $teleconsulta): void
    {
        $this->teleconsultaId = $teleconsulta->id;
        $this->sala_url = (string) $teleconsulta->sala_url;
        app(AuditoriaService::class)->registrar('ver', 'teleconsulta', $teleconsulta->id, $teleconsulta->paciente_id);
    }

    private function teleconsulta(): Teleconsulta
    {
        return Teleconsulta::with('paciente', 'medico.especialidad', 'consulta')->findOrFail($this->teleconsultaId);
    }

    /** Solo el médico de la teleconsulta (o el administrador) la atiende */
    private function autorizarMedico(Teleconsulta $tc): void
    {
        $this->authorize('expediente.crear');
        $esSuMedico = Medico::where('user_id', Auth::id())->value('id') === $tc->medico_id;
        abort_unless($esSuMedico || Auth::user()->can('configuracion.editar'), 403);
    }

    /** Plataforma "enlace propio": el personal pega el enlace de la reunión */
    public function guardarSala(): void
    {
        abort_unless(Auth::user()->canAny(['turnos.gestionar', 'expediente.crear']), 403);
        $tc = $this->teleconsulta();
        abort_unless($tc->abierta(), 403);
        // La videoconsulta se transmite cifrada: solo enlaces https (num. 7.2)
        $this->validate(['sala_url' => ['required', 'url:https', 'max:500']], [
            'sala_url.url' => 'Pegue un enlace que empiece con https://',
            'sala_url.required' => 'Pegue el enlace de la reunión.',
        ]);
        $tc->update(['sala_url' => trim($this->sala_url)]);
        app(AuditoriaService::class)->registrar('editar', 'teleconsulta', $tc->id, $tc->paciente_id, 'Enlace de la sala de video');
        $this->dispatch('notify', message: 'Enlace de la reunión guardado.', type: 'success');
    }

    /**
     * Consentimiento dado de palabra al profesional (por ejemplo, al inicio de la llamada): queda documentado
     * con quién lo registró, como pide el num. 7.2.2 ("documentar evidencia").
     */
    public function registrarConsentimientoVerbal(): void
    {
        $tc = $this->teleconsulta();
        $this->autorizarMedico($tc);
        abort_unless($tc->abierta() && !$tc->tieneConsentimiento(), 403);
        $this->validate(['confirmaVerbal' => 'accepted', 'representante' => 'nullable|string|max:150'], [
            'confirmaVerbal.accepted' => 'Confirme que leyó el consentimiento al paciente y que lo aceptó.',
        ]);

        $tc->update([
            'autorizacion_telematica_at' => now(),
            'consentimiento_datos_at' => now(),
            'consentimiento_medio' => 'verbal',
            'consentimiento_ip' => request()->ip(),
            'consentimiento_registrado_por' => Auth::id(),
            'consentimiento_representante' => trim($this->representante) ?: null,
        ]);
        app(AuditoriaService::class)->registrar('crear', 'teleconsulta', $tc->id, $tc->paciente_id, 'Consentimiento de telesalud verbal registrado por el profesional');
        $this->reset('confirmaVerbal', 'representante');
        $this->dispatch('notify', message: 'Consentimiento registrado.', type: 'success');
    }

    public function iniciar(): void
    {
        $tc = $this->teleconsulta();
        $this->autorizarMedico($tc);
        abort_unless($tc->abierta() && $tc->tieneConsentimiento() && $tc->sala_url, 403);

        if ($tc->estado === 'programada') {
            $tc->update(['estado' => 'en_curso', 'inicio_at' => now()]);
            app(AuditoriaService::class)->registrar('editar', 'teleconsulta', $tc->id, $tc->paciente_id, 'Inicio de la videoconsulta');
        }
    }

    public function finalizar(): void
    {
        $tc = $this->teleconsulta();
        $this->autorizarMedico($tc);
        abort_unless($tc->estado === 'en_curso', 403);
        $this->validate([
            'resultado' => ['required', Rule::in(array_keys(Teleconsulta::RESULTADOS))],
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $tc->update([
            'estado' => 'finalizada',
            'fin_at' => now(),
            'resultado_conexion' => $this->resultado,
            'observaciones_tecnicas' => trim($this->observaciones) ?: null,
        ]);
        $tc->cita?->update(['estado' => 'completada']);
        app(AuditoriaService::class)->registrar('editar', 'teleconsulta', $tc->id, $tc->paciente_id, 'Videoconsulta finalizada: ' . $this->resultado);
        $this->dispatch('notify', message: 'Teleconsulta finalizada.', type: 'success');
    }

    public function noAsistio(): void
    {
        $tc = $this->teleconsulta();
        $this->autorizarMedico($tc);
        abort_unless($tc->abierta(), 403);
        $tc->update(['estado' => 'no_asistio', 'fin_at' => now()]);
        app(AuditoriaService::class)->registrar('editar', 'teleconsulta', $tc->id, $tc->paciente_id, 'El paciente no se conectó');
        $this->dispatch('notify', message: 'Marcada como "no se conectó".', type: 'success');
    }

    public function render()
    {
        $tc = $this->teleconsulta();
        $servicio = app(TelemedicinaService::class);
        $medicoId = Medico::where('user_id', Auth::id())->value('id');

        return view('livewire.detalle-teleconsulta', [
            'tc' => $tc,
            'puedeAtender' => Auth::user()->can('expediente.crear') && ($medicoId === $tc->medico_id || Auth::user()->can('configuracion.editar')),
            'enlaceWhatsapp' => $tc->abierta() ? $servicio->enlaceWhatsapp($tc) : null,
            'ventana' => $servicio->ventana($tc),
        ])->layout('layouts.app');
    }
}
