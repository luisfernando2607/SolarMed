<?php

namespace App\Livewire;

use App\Services\AuditoriaService;
use App\Services\EstadoSistemaService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

/** Panel del administrador: ¿el servidor está haciendo su trabajo? */
class EstadoSistema extends Component
{
    public function respaldarAhora(): void
    {
        $this->authorize('configuracion.editar');
        $ok = Artisan::call('respaldo:crear') === 0;
        app(AuditoriaService::class)->registrar('crear', 'respaldo', null, null, 'Respaldo manual desde Estado del sistema');
        $this->dispatch('notify', message: $ok ? 'Respaldo creado.' : 'No se pudo crear el respaldo: ' . trim(Artisan::output()), type: $ok ? 'success' : 'error');
    }

    public function verificarAuditoria(): void
    {
        $this->authorize('configuracion.editar');
        $r = app(AuditoriaService::class)->verificarIntegridad();
        $this->dispatch('notify', message: $r['integro'] ? 'Registro de auditoría íntegro.' : 'Se detectó una alteración en el registro de auditoría.', type: $r['integro'] ? 'success' : 'error');
    }

    /** Vuelve a leer los certificados .p12 (por ejemplo, después de cargar uno nuevo) */
    public function releerCertificados(): void
    {
        $this->authorize('configuracion.editar');
        foreach (\App\Models\Medico::whereNotNull('p12_path')->get() as $m) {
            Cache::forget("sistema.p12.{$m->id}." . md5($m->p12_path));
        }
    }

    public function render()
    {
        return view('livewire.estado-sistema', [
            'chequeos' => app(EstadoSistemaService::class)->chequeos(),
            'version' => [
                'PHP' => PHP_VERSION,
                'Laravel' => app()->version(),
                'Zona horaria' => config('app.timezone'),
                'Base de datos' => config('database.default'),
            ],
        ])->layout('layouts.app');
    }
}
