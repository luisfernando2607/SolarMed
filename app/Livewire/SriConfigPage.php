<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\SriConfigService;

class SriConfigPage extends Component
{
    use WithFileUploads;

    public $sri_ruc;
    public $sri_razon_social;
    public $sri_nombre_comercial;
    public $sri_direccion;
    public $sri_telefono;
    public $sri_email;
    public $sri_contribuyente_especial;
    public $sri_obligado_contabilidad = 'NO';
    public $sri_establecimiento = '001';
    public $sri_pto_emi = '001';
    public $sri_ambiente = '1';

    protected function rules(): array
    {
        return [
            'sri_ruc' => 'required|string|max:13',
            'sri_razon_social' => 'required|string|max:300',
            'sri_nombre_comercial' => 'nullable|string|max:300',
            'sri_direccion' => 'required|string|max:300',
            'sri_telefono' => 'nullable|string|max:20',
            'sri_email' => 'nullable|email|max:255',
            'sri_contribuyente_especial' => 'nullable|string|max:50',
            'sri_obligado_contabilidad' => 'required|in:SI,NO',
            'sri_establecimiento' => 'required|string|max:3',
            'sri_pto_emi' => 'required|string|max:3',
            'sri_ambiente' => 'required|in:1,2',
        ];
    }

    public function mount(): void
    {
        $this->authorize('configuracion.editar');
        $config = app(SriConfigService::class);
        $vals = $config->getAll();

        foreach ($vals as $k => $v) {
            if (property_exists($this, $k)) {
                $this->$k = $v;
            }
        }
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');
        $this->validate();

        $svc = app(SriConfigService::class);
        foreach ($this->rules() as $clave => $_) {
            $svc->set($clave, $this->$clave);
        }

        $this->dispatch('notify', message: 'Configuración SRI guardada.', type: 'success');
    }

    public function render()
    {
        return view('livewire.sri-config-page')
            ->layout('layouts.app');
    }
}
