<?php

namespace App\Livewire;

use App\Services\AjustesService;
use App\Services\AuditoriaService;
use App\Services\TelemedicinaService;
use Illuminate\Validation\Rule;
use Livewire\Component;

/** Ajustes de telemedicina (Norma Técnica de Telesalud, MSP 2025): componente aparte, con su propio guardado */
class ConfiguracionTelemedicina extends Component
{
    public bool $activa = false;
    public string $plataforma = 'jitsi';
    public string $jitsi_url = '';
    public $minutos_antes;
    public $minutos_despues;

    public function mount(): void
    {
        $a = app(AjustesService::class);
        $this->activa = (bool) $a->get('telemedicina.activa');
        $this->plataforma = (string) $a->get('telemedicina.plataforma');
        $this->jitsi_url = (string) $a->get('telemedicina.jitsi_url');
        $this->minutos_antes = $a->get('telemedicina.minutos_antes');
        $this->minutos_despues = $a->get('telemedicina.minutos_despues');
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');
        $this->validate([
            'plataforma' => ['required', Rule::in(array_keys(TelemedicinaService::PLATAFORMAS))],
            // La sala de video requiere HTTPS: el navegador no da cámara ni micrófono sin cifrado
            'jitsi_url' => ['required_if:plataforma,jitsi', 'nullable', 'url:https', 'max:200'],
            'minutos_antes' => 'required|integer|between:0,120',
            'minutos_despues' => 'required|integer|between:10,240',
        ], [
            'jitsi_url.url' => 'Escriba una dirección que empiece con https://',
            'jitsi_url.required_if' => 'Escriba la dirección del servidor de Jitsi.',
        ]);

        app(AjustesService::class)->guardar([
            'telemedicina.activa' => $this->activa,
            'telemedicina.plataforma' => $this->plataforma,
            'telemedicina.jitsi_url' => rtrim(trim($this->jitsi_url), '/'),
            'telemedicina.minutos_antes' => (int) $this->minutos_antes,
            'telemedicina.minutos_despues' => (int) $this->minutos_despues,
        ]);
        app(AuditoriaService::class)->registrar('editar', 'configuracion', null, null, 'Ajustes de telemedicina');
        $this->dispatch('notify', message: 'Ajustes de telemedicina guardados.', type: 'success');
    }

    public function render()
    {
        return view('livewire.configuracion-telemedicina');
    }
}
