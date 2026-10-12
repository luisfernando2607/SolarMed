<?php

namespace App\Livewire;

use App\Models\CertificadoMedico;
use App\Models\Cita;
use App\Models\Ecografia;
use App\Models\ExpedienteConsulta;
use App\Models\Factura;
use App\Models\Paciente;
use App\Models\PedidoExamen;
use App\Models\Receta;
use App\Services\AuditoriaService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Ficha completa del paciente. Los datos clínicos (antecedentes, consultas, documentos)
 * solo se muestran con expediente.ver; recepción y enfermería ven datos de contacto y citas.
 */
class FichaPaciente extends Component
{
    #[Locked]
    public Paciente $paciente;

    public function mount(Paciente $paciente): void
    {
        $this->paciente = $paciente;
        app(AuditoriaService::class)->registrar('ver', 'paciente', $paciente->id, $paciente->id, 'Ficha del paciente');
    }

    /** Recetas, certificados, pedidos y ecografías en una sola línea de tiempo */
    private function documentos(): Collection
    {
        $id = $this->paciente->id;

        return collect()
            ->concat(Receta::with('medico')->where('paciente_id', $id)->get()->map(fn ($r) => [
                'tipo' => 'Receta', 'fecha' => $r->fecha, 'detalle' => str($r->medicamentos)->squish()->limit(90)->toString(),
                'medico' => $r->medico?->nombre_completo, 'pdf' => route('recetas.pdf', $r), 'tono' => 'bg-emerald-50 text-emerald-700',
            ]))
            ->concat(CertificadoMedico::with('medico')->where('paciente_id', $id)->get()->map(fn ($c) => [
                'tipo' => 'Certificado', 'fecha' => $c->fecha_emision, 'detalle' => $c->numero() . ($c->dias_reposo ? " · {$c->dias_reposo} días de reposo" : ''),
                'medico' => $c->medico?->nombre_completo, 'pdf' => route('certificados.pdf', $c), 'tono' => 'bg-sky-50 text-sky-700',
            ]))
            ->concat(PedidoExamen::with('medico')->where('paciente_id', $id)->get()->map(fn ($p) => [
                'tipo' => 'Pedido de exámenes', 'fecha' => $p->fecha, 'detalle' => $p->numero() . ($p->diagnostico ? ' · ' . str($p->diagnostico)->limit(60) : ''),
                'medico' => $p->medico?->nombre_completo, 'pdf' => route('pedidos.pdf', $p), 'tono' => 'bg-amber-50 text-amber-700',
            ]))
            ->concat(Ecografia::with('medico')->where('paciente_id', $id)->get()->map(fn ($e) => [
                'tipo' => 'Ecografía', 'fecha' => $e->fecha, 'detalle' => str($e->conclusion ?: $e->indicacion)->squish()->limit(90)->toString(),
                'medico' => $e->medico?->nombre_completo, 'pdf' => route('ecografias.pdf', $e), 'tono' => 'bg-pink-50 text-pink-700',
            ]))
            ->sortByDesc(fn ($d) => $d['fecha']?->getTimestamp() ?? 0)
            ->values();
    }

    public function render()
    {
        $p = $this->paciente;
        $user = auth()->user();
        $clinico = $user->can('expediente.ver');

        return view('livewire.ficha-paciente', [
            'clinico' => $clinico,
            'turnoActivo' => $p->turnos()->whereIn('estado', ['esperando', 'en_atencion'])->whereDate('fecha', today())->latest()->first(),
            'proximaCita' => Cita::with('medico')->where('paciente_id', $p->id)->where('fecha_hora', '>=', now())
                ->whereNotIn('estado', ['cancelada', 'completada'])->orderBy('fecha_hora')->first(),
            'citas' => $user->can('turnos.ver')
                ? Cita::with('medico')->where('paciente_id', $p->id)->latest('fecha_hora')->take(15)->get()
                : collect(),
            'consultas' => $clinico
                ? ExpedienteConsulta::with('medico', 'especialidad')->where('paciente_id', $p->id)->orderByDesc('fecha')->get()
                : collect(),
            'documentos' => $clinico ? $this->documentos() : collect(),
            'archivos' => $clinico ? $p->archivos()->count() : 0,
            'embarazo' => $clinico && $p->sexo === 'femenino' ? $p->embarazos()->where('estado', 'activo')->first() : null,
            'facturas' => $user->can('facturas.ver')
                ? Factura::where('paciente_id', $p->id)->latest('fecha')->latest('id')->take(20)->get()
                : collect(),
        ])->layout('layouts.app');
    }
}
