<?php

namespace App\Livewire;

use App\Models\CertificadoMedico;
use App\Models\Consentimiento;
use App\Models\Cita;
use App\Models\Ecografia;
use App\Models\ExpedienteConsulta;
use App\Models\Factura;
use App\Models\Paciente;
use App\Models\PedidoExamen;
use App\Models\Preparacion;
use App\Models\Receta;
use App\Models\Referencia;
use App\Services\AuditoriaService;
use App\Services\EvolucionPacienteService;
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

    /** Recetas, certificados, pedidos, ecografías, consentimientos y referencias en una sola línea de tiempo */
    private function documentos(): Collection
    {
        $id = $this->paciente->id;

        return collect()
            ->concat(Receta::with('medico', 'items')->where('paciente_id', $id)->get()->map(fn ($r) => [
                'tipo' => 'Receta', 'fecha' => $r->fecha,
                'detalle' => ($r->estructurada() ? "N.º {$r->numeroFormateado()} · " : '') . str($r->resumen())->limit(90)->toString(),
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
            ->concat(Consentimiento::with('medico')->where('paciente_id', $id)->get()->map(fn ($c) => [
                'tipo' => 'Consentimiento informado', 'fecha' => $c->fecha_informacion,
                'detalle' => $c->procedimiento . ' · ' . $c->estadoTexto(),
                'medico' => $c->medico?->nombre_completo, 'pdf' => route('consentimientos.pdf', $c), 'tono' => $c->estadoTono(),
                'ver' => route('consentimientos.ver', $c),
            ]))
            ->concat(Referencia::with('medico')->where('paciente_id', $id)->get()->map(fn ($r) => [
                'tipo' => Referencia::TIPOS[$r->tipo] . ($r->direccion === 'entrante' ? ' recibida' : ''),
                'fecha' => $r->fecha,
                'detalle' => $r->numero() . ' · ' . $r->especialidad . ' · ' . Referencia::ESTADOS[$r->estado],
                'medico' => $r->medico?->nombre_completo, 'pdf' => route('referencias.pdf', $r), 'tono' => 'bg-violet-50 text-violet-700',
            ]))
            ->sortByDesc(fn ($d) => $d['fecha']?->getTimestamp() ?? 0)
            ->values();
    }

    /**
     * Gráficos de la pestaña Evolución: uno por medida, cada uno con un solo eje.
     * Franjas de referencia: las mismas de la preparación de enfermería; IMC según la OMS.
     */
    private function evolucion(): array
    {
        $servicio = app(EvolucionPacienteService::class);
        $m = $servicio->medidas($this->paciente);
        $r = Preparacion::RANGOS;
        $azul = '#2a78d6';
        $naranja = '#eb6834';
        $neutro = '#6b6a65';
        $serie = fn (string $nombre, array $puntos, string $color) => ['nombre' => $nombre, 'color' => $color, 'puntos' => $puntos];
        $rango = fn (string $c) => "{$r[$c][0]}–{$r[$c][1]}";

        $graficos = [
            ['id' => 'pa', 'titulo' => 'Presión arterial', 'unidad' => 'mmHg', 'eje' => 'fecha',
                'series' => [$serie('Sistólica', $m['sistolica'], $azul), $serie('Diastólica', $m['diastolica'], $naranja)],
                'bandas' => [[$r['presion_sistolica'][0], $r['presion_sistolica'][1], $azul], [$r['presion_diastolica'][0], $r['presion_diastolica'][1], $naranja]],
                'nota' => 'Franjas: rango habitual de la sistólica (' . $rango('presion_sistolica') . ') y de la diastólica (' . $rango('presion_diastolica') . ').'],
            ['id' => 'peso', 'titulo' => 'Peso', 'unidad' => 'kg', 'eje' => 'fecha', 'series' => [$serie('Peso', $m['peso'], $azul)], 'bandas' => []],
            ['id' => 'imc', 'titulo' => 'Índice de masa corporal', 'unidad' => 'kg/m²', 'eje' => 'fecha', 'series' => [$serie('IMC', $m['imc'], $azul)],
                'bandas' => [[18.5, 24.9, $neutro]], 'nota' => 'Franja: peso normal según la OMS (18,5–24,9).'],
            ['id' => 'glucosa', 'titulo' => 'Glucosa capilar', 'unidad' => 'mg/dl', 'eje' => 'fecha', 'series' => [$serie('Glucosa', $m['glucosa'], $azul)], 'bandas' => []],
            ['id' => 'saturacion', 'titulo' => 'Saturación de oxígeno', 'unidad' => '%', 'eje' => 'fecha', 'series' => [$serie('Saturación', $m['saturacion'], $azul)],
                'bandas' => [[$r['saturacion'][0], $r['saturacion'][1], $neutro]], 'nota' => 'Franja: rango habitual (' . $rango('saturacion') . ' %).'],
            ['id' => 'fc', 'titulo' => 'Frecuencia cardíaca', 'unidad' => 'lpm', 'eje' => 'fecha', 'series' => [$serie('Pulso', $m['fc'], $azul)],
                'bandas' => [[$r['frecuencia_cardiaca'][0], $r['frecuencia_cardiaca'][1], $neutro]], 'nota' => 'Franja: rango habitual (' . $rango('frecuencia_cardiaca') . ' lpm).'],
        ];

        if ($emb = $servicio->embarazo($this->paciente)) {
            $tol = $emb['tolerancia_au'];
            $graficos[] = ['id' => 'ganancia', 'titulo' => 'Ganancia de peso en el embarazo', 'unidad' => 'kg', 'eje' => 'semana',
                'series' => [$serie('Ganancia', $emb['ganancia'], $azul)], 'bandas' => [],
                'nota' => 'Diferencia con el peso del primer control prenatal.'];
            $graficos[] = ['id' => 'au', 'titulo' => 'Altura uterina', 'unidad' => 'cm', 'eje' => 'semana',
                'series' => [$serie('Altura uterina', $emb['altura_uterina'], $azul)], 'bandas' => [],
                'franjaDiagonal' => ['desde' => 20, 'hasta' => 36, 'tolerancia' => $tol],
                'nota' => "Franja: semanas de gestación ± {$tol} cm (semanas 20 a 36)."];
        }

        return $graficos;
    }

    public function render()
    {
        $p = $this->paciente;
        $user = auth()->user();
        $clinico = $user->can('expediente.ver');

        return view('livewire.ficha-paciente', [
            'clinico' => $clinico,
            'ultimaAtencion' => $p->ultimaAtencion(),
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
            'evolucion' => $clinico ? $this->evolucion() : [],
            'archivos' => $clinico ? $p->archivos()->count() : 0,
            'embarazo' => $clinico && $p->sexo === 'femenino' ? $p->embarazos()->where('estado', 'activo')->first() : null,
            'facturas' => $user->can('facturas.ver')
                ? Factura::where('paciente_id', $p->id)->latest('fecha')->latest('id')->take(20)->get()
                : collect(),
        ])->layout('layouts.app');
    }
}
