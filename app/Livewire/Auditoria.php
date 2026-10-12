<?php

namespace App\Livewire;

use App\Models\RegistroAcceso;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Auditoria extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $paciente = '';
    #[Url(except: '')]
    public string $usuario = '';
    #[Url(except: '')]
    public string $accion = '';
    #[Url(except: '')]
    public string $desde = '';
    #[Url(except: '')]
    public string $hasta = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function limpiar(): void
    {
        $this->reset(['paciente', 'usuario', 'accion', 'desde', 'hasta']);
        $this->resetPage();
    }

    public function verificarIntegridad(): void
    {
        $this->authorize('auditoria.ver');
        $r = app(AuditoriaService::class)->verificarIntegridad();

        $this->dispatch('notify',
            message: $r['integro'] ? "Registro íntegro: {$r['total']} registros verificados." : "Se detectó una alteración a partir del registro #{$r['primer_error']}.",
            type: $r['integro'] ? 'success' : 'error');
    }

    private function filtros(): array
    {
        return $this->only(['paciente', 'usuario', 'accion', 'desde', 'hasta']);
    }

    public function render()
    {
        $hace7 = now()->subDays(7);

        return view('livewire.auditoria', [
            'registros' => RegistroAcceso::with('user', 'paciente')->filtrar($this->filtros())
                ->latest('created_at')->latest('id')->paginate(25),
            'usuarios' => User::orderBy('name')->get(['id', 'name']),
            'integridad' => Cache::get(AuditoriaService::CLAVE_INTEGRIDAD),
            'kpis' => [
                'accesos_hoy' => RegistroAcceso::whereDate('created_at', today())->whereNotIn('accion', RegistroAcceso::ACCIONES_SESION)->count(),
                'pacientes_hoy' => RegistroAcceso::whereDate('created_at', today())->whereNotNull('paciente_id')->distinct()->count('paciente_id'),
                'fallidos_7' => RegistroAcceso::where('created_at', '>=', $hace7)->whereIn('accion', ['login_fallido', 'bloqueo'])->count(),
                'descargas_7' => RegistroAcceso::where('created_at', '>=', $hace7)->whereIn('accion', ['descargar', 'exportar'])->count(),
            ],
            'urlExportar' => route('auditoria.exportar', array_filter($this->filtros(), fn ($v) => $v !== '')),
        ])->layout('layouts.app');
    }
}
