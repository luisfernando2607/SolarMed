<?php

namespace App\Livewire;

use App\Models\Teleconsulta;
use App\Services\AuditoriaService;
use App\Services\TelemedicinaService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Página pública (sin sesión) del paciente para su videoconsulta: acepta la autorización y el consentimiento
 * (Norma Técnica de Telesalud, num. 7.2.2) y, dentro del horario, entra a la sala. No muestra datos clínicos,
 * y el enlace de la sala no se entrega antes del consentimiento ni fuera del horario.
 */
class PacienteTeleconsulta extends Component
{
    #[Locked]
    public string $token;

    public bool $autoriza = false;
    public bool $consiente = false;
    public string $representante = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        abort_unless(Teleconsulta::where('token', $token)->exists(), 404);
    }

    private function teleconsulta(): Teleconsulta
    {
        return Teleconsulta::with('medico.especialidad')->where('token', $this->token)->firstOrFail();
    }

    public function aceptar(): void
    {
        $clave = 'teleconsulta-consentimiento:' . request()->ip();
        if (RateLimiter::tooManyAttempts($clave, 20)) {
            $this->addError('autoriza', 'Demasiados intentos. Intente más tarde.');
            return;
        }
        RateLimiter::hit($clave, 3600);

        $tc = $this->teleconsulta();
        if (!$tc->abierta() || $tc->tieneConsentimiento()) {
            return;
        }
        $this->validate([
            'autoriza' => 'accepted',
            'consiente' => 'accepted',
            'representante' => 'nullable|string|max:150',
        ], [
            'autoriza.accepted' => 'Para continuar debe autorizar la atención por videoconsulta.',
            'consiente.accepted' => 'Para continuar debe aceptar el tratamiento de sus datos.',
        ]);

        $tc->update([
            'autorizacion_telematica_at' => now(),
            'consentimiento_datos_at' => now(),
            'consentimiento_medio' => 'enlace',
            'consentimiento_ip' => request()->ip(),
            'consentimiento_representante' => trim($this->representante) ?: null,
        ]);
        app(AuditoriaService::class)->registrar('crear', 'teleconsulta', $tc->id, $tc->paciente_id, 'Consentimiento de telesalud aceptado por el paciente en su enlace');
    }

    public function render()
    {
        $tc = $this->teleconsulta();
        $servicio = app(TelemedicinaService::class);
        $enHorario = $servicio->dentroDeVentana($tc);
        // El enlace de la sala solo se entrega con consentimiento, en horario y con la teleconsulta abierta
        $puedeEntrar = $tc->abierta() && $tc->tieneConsentimiento() && $enHorario && $tc->sala_url;

        return view('livewire.paciente-teleconsulta', [
            'tc' => $tc,
            'ventana' => $servicio->ventana($tc),
            'enHorario' => $enHorario,
            'salaUrl' => $puedeEntrar ? $tc->sala_url : null,
        ])->layout('layouts.guest');
    }
}
