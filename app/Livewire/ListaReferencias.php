<?php

namespace App\Livewire;

use App\Models\Paciente;
use App\Models\Referencia;
use App\Services\AuditoriaService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ListaReferencias extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $estado = '';
    #[Url(except: '')]
    public string $direccion = '';
    #[Url(except: '')]
    public string $busqueda = '';
    #[Url(except: null)]
    public ?int $paciente = null;
    #[Url(except: false)]
    public bool $sinRespuesta = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function quitarPaciente(): void
    {
        $this->paciente = null;
        $this->resetPage();
    }

    /** Cierra el circuito cuando el paciente ya volvió con la contrarreferencia y se dio seguimiento */
    public function cerrar(int $id): void
    {
        $this->authorize('expediente.crear');
        $r = Referencia::findOrFail($id);
        if ($r->estado !== 'contrarreferida') {
            return;
        }
        $r->update(['estado' => 'cerrada']);
        app(AuditoriaService::class)->registrar('editar', 'referencia', $r->id, $r->paciente_id, "Cerró {$r->numero()}");
        $this->dispatch('notify', message: "{$r->numero()} cerrada.", type: 'success');
    }

    public function render()
    {
        $limite = now()->subDays(Referencia::DIAS_SIN_RESPUESTA);
        $q = Referencia::with('paciente', 'medico')->latest('fecha')->latest('id');

        if ($this->paciente) {
            $q->where('paciente_id', $this->paciente);
        }
        if (array_key_exists($this->estado, Referencia::ESTADOS)) {
            $q->where('estado', $this->estado);
        }
        if (in_array($this->direccion, ['saliente', 'entrante'], true)) {
            $q->where('direccion', $this->direccion);
        }
        if ($this->sinRespuesta) {
            $q->where('direccion', 'saliente')->where('estado', 'enviada')->where('fecha', '<', $limite);
        }
        if (trim($this->busqueda) !== '') {
            $t = trim($this->busqueda);
            $q->where(fn ($w) => $w->where('especialidad', 'like', "%{$t}%")
                ->orWhere('establecimiento_destino', 'like', "%{$t}%")
                ->orWhere('establecimiento_origen', 'like', "%{$t}%")
                ->orWhereHas('paciente', fn ($p) => $p->where('cedula', 'like', "{$t}%")
                    ->orWhere('nombres', 'like', "%{$t}%")->orWhere('apellidos', 'like', "%{$t}%")));
        }

        return view('livewire.lista-referencias', [
            'referencias' => $q->paginate(20),
            'pacienteFiltro' => $this->paciente ? Paciente::find($this->paciente) : null,
            'kpis' => [
                'enviadas' => Referencia::where('estado', 'enviada')->where('direccion', 'saliente')->count(),
                'sin_respuesta' => Referencia::where('estado', 'enviada')->where('direccion', 'saliente')->where('fecha', '<', $limite)->count(),
                'entrantes' => Referencia::where('estado', 'enviada')->where('direccion', 'entrante')->count(),
                'mes' => Referencia::where('fecha', '>=', now()->startOfMonth())->count(),
            ],
        ])->layout('layouts.app');
    }
}
