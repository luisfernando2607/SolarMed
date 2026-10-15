<?php

namespace App\Livewire;

use App\Jobs\EnviarWhatsapp;
use App\Models\WhatsappMensaje;
use App\Services\WhatsappCloudService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Historial de mensajes de WhatsApp enviados por la API, con estados de entrega y respuestas de los pacientes */
class WhatsappMensajes extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $estado = '';
    #[Url(except: '')]
    public string $tipo = '';
    #[Url(except: '')]
    public string $fecha = '';
    #[Url(except: '')]
    public string $busqueda = '';
    #[Url(except: false)]
    public bool $soloRespuestas = false;

    public function updated(): void
    {
        $this->resetPage();
    }

    public function reintentar(int $id): void
    {
        $this->authorize('turnos.gestionar');
        $m = WhatsappMensaje::findOrFail($id);
        if ($m->estado !== 'fallido' || !app(WhatsappCloudService::class)->configurado()) {
            return;
        }
        if ($m->paciente?->whatsapp_baja) {
            $this->dispatch('notify', message: 'El paciente pidió no recibir mensajes por WhatsApp.', type: 'error');
            return;
        }
        $m->update(['estado' => 'pendiente', 'error_codigo' => null, 'error_mensaje' => null, 'wamid' => null]);
        EnviarWhatsapp::dispatch($m->id);
        $this->dispatch('notify', message: 'Mensaje en cola para reintentarse.', type: 'success');
    }

    public function marcarRevisada(int $id): void
    {
        $this->authorize('turnos.gestionar');
        WhatsappMensaje::whereKey($id)->whereNotNull('respondido_at')->update(['respuesta_revisada_at' => now()]);
    }

    public function render()
    {
        $query = WhatsappMensaje::with('paciente:id,nombres,apellidos,cedula,identidad_reservada', 'cita:id,fecha_hora,estado', 'user:id,name')
            ->when(array_key_exists($this->estado, WhatsappMensaje::ESTADOS), fn ($q) => $q->where('estado', $this->estado))
            ->when(array_key_exists($this->tipo, WhatsappMensaje::TIPOS), fn ($q) => $q->where('tipo', $this->tipo))
            ->when($this->fecha !== '' && strtotime($this->fecha), fn ($q) => $q->whereDate('created_at', $this->fecha))
            ->when($this->soloRespuestas, fn ($q) => $q->whereNotNull('respondido_at'))
            ->when(trim($this->busqueda) !== '', function ($q) {
                $t = trim($this->busqueda);
                $q->where(fn ($w) => $w->where('telefono', 'like', "%{$t}%")
                    ->orWhereHas('paciente', fn ($p) => $p->where('cedula', 'like', "%{$t}%")->orWhere('nombres', 'like', "%{$t}%")->orWhere('apellidos', 'like', "%{$t}%")));
            })
            ->orderByRaw('CASE WHEN respondido_at IS NOT NULL AND respuesta_revisada_at IS NULL THEN 0 ELSE 1 END')
            ->latest('id');

        $semana = WhatsappMensaje::where('created_at', '>=', now()->subDays(7))->where('tipo', '!=', 'prueba');
        $enviados7 = (clone $semana)->whereIn('estado', ['enviado', 'entregado', 'leido', 'fallido'])->count();
        $wa = app(WhatsappCloudService::class);

        return view('livewire.whatsapp-mensajes', [
            'mensajes' => $query->paginate(20),
            'kpis' => [
                'hoy' => WhatsappMensaje::whereDate('created_at', today())->where('tipo', '!=', 'prueba')->count(),
                'entregados' => $enviados7 ? (int) round((clone $semana)->whereIn('estado', ['entregado', 'leido'])->count() * 100 / $enviados7) : null,
                'leidos' => $enviados7 ? (int) round((clone $semana)->where('estado', 'leido')->count() * 100 / $enviados7) : null,
                'fallidos' => (clone $semana)->where('estado', 'fallido')->count(),
                'sin_revisar' => WhatsappMensaje::whereNotNull('respondido_at')->whereNull('respuesta_revisada_at')->count(),
            ],
            'activo' => $wa->activo() && $wa->configurado(),
        ])->layout('layouts.app');
    }
}
