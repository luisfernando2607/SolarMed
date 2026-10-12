<?php

namespace App\Livewire;

use App\Models\Factura;
use App\Models\Medico;
use App\Models\NotaCredito;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

/** Resumen mensual de ventas por RUC emisor para la declaración del contador. */
class ReporteVentas extends Component
{
    public string $mes = '';
    public string $ruc = '';
    public bool $soloAutorizadas = false;

    public function mount(): void
    {
        $this->mes = now()->subMonthNoOverflow()->format('Y-m');
    }

    private function periodo(): array
    {
        $inicio = preg_match('/^\d{4}-\d{2}$/', $this->mes) ? Carbon::createFromFormat('Y-m-d', "{$this->mes}-01")->startOfDay() : now()->startOfMonth();
        return [$inicio, $inicio->copy()->endOfMonth()];
    }

    private function facturas(): Collection
    {
        [$inicio, $fin] = $this->periodo();

        return Factura::with('paciente')
            ->whereBetween('fecha', [$inicio, $fin])
            ->when($this->ruc !== '', fn ($q) => $q->where('ruc_emisor', $this->ruc))
            ->when($this->soloAutorizadas, fn ($q) => $q->where('estado_sri', 'autorizado'))
            ->orderBy('ruc_emisor')->orderBy('numero_factura')
            ->get();
    }

    private function notasCredito(): Collection
    {
        [$inicio, $fin] = $this->periodo();

        return NotaCredito::with('factura.paciente')
            ->whereBetween('fecha', [$inicio, $fin])
            ->when($this->ruc !== '', fn ($q) => $q->where('ruc_emisor', $this->ruc))
            ->when($this->soloAutorizadas, fn ($q) => $q->where('estado_sri', 'autorizado'))
            ->orderBy('ruc_emisor')->orderBy('numero')
            ->get();
    }

    private function resumen(Collection $facturas, Collection $notas): array
    {
        $vigentes = $facturas->where('estado', '!=', 'anulada');
        // Una factura anulada por nota de crédito sí se declaró: cuenta como venta y la NC la compensa
        $declaradas = $facturas->filter(fn ($f) => $f->estado !== 'anulada' || $f->estado_sri === 'autorizado');
        $totalFacturado = (float) $declaradas->sum('total');
        $totalNotas = (float) $notas->sum('valor_modificacion');

        return [
            'facturas' => $declaradas->count(),
            'anuladas_sin_sri' => $facturas->count() - $declaradas->count(),
            'subtotal' => (float) $declaradas->sum('subtotal'),
            'descuento' => (float) $declaradas->sum('descuento'),
            'facturado' => $totalFacturado,
            'notas' => $totalNotas,
            'neto' => $totalFacturado - $totalNotas,
            'por_forma_pago' => $vigentes->groupBy('forma_pago')->map(fn ($g) => (float) $g->sum('total'))->all(),
            'pendientes_sri' => $declaradas->where('estado_sri', '!=', 'autorizado')->count(),
        ];
    }

    public function exportar()
    {
        $this->authorize('reportes.ver');
        $facturas = $this->facturas();
        $notas = $this->notasCredito();
        $nombre = 'ventas_' . $this->mes . ($this->ruc ? "_{$this->ruc}" : '') . '.csv';

        app(AuditoriaService::class)->registrar('descargar', 'reporte_ventas', null, null, "Reporte de ventas {$this->mes}" . ($this->ruc ? " RUC {$this->ruc}" : ''));

        return response()->streamDownload(function () use ($facturas, $notas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel reconozca UTF-8
            $num = fn ($v) => number_format((float) $v, 2, ',', '');
            $fila = fn (array $c) => fputcsv($out, $c, ';');

            $fila(['Tipo', 'RUC emisor', 'Fecha', 'Número', 'Doc. modificado', 'Cliente', 'Identificación', 'Base 0%', 'Descuento', 'IVA', 'Total', 'Forma de pago', 'Estado', 'Estado SRI', 'Autorización']);
            foreach ($facturas as $f) {
                $fila(['Factura', $f->ruc_emisor, $f->fecha->format('d/m/Y'), $f->numero_factura, '', $f->paciente?->nombre_completo, $f->paciente?->cedula,
                    $num($f->subtotal - $f->descuento), $num($f->descuento), $num(0), $num($f->total), $f->forma_pago, $f->estado, $f->estado_sri, $f->numero_autorizacion]);
            }
            foreach ($notas as $n) {
                $fila(['Nota de crédito', $n->ruc_emisor, $n->fecha->format('d/m/Y'), $n->numero, $n->factura?->numero_factura, $n->factura?->paciente?->nombre_completo, $n->factura?->paciente?->cedula,
                    $num(-$n->valor_modificacion), $num(0), $num(0), $num(-$n->valor_modificacion), '', '', $n->estado_sri, $n->numero_autorizacion]);
            }
            fclose($out);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $facturas = $this->facturas();
        $notas = $this->notasCredito();

        $rucs = Medico::whereNotNull('ruc')->orderBy('apellidos')->get(['ruc', 'nombres', 'apellidos', 'razon_social'])
            ->mapWithKeys(fn ($m) => [$m->ruc => ($m->razon_social ?: "{$m->apellidos} {$m->nombres}")])
            ->union(Factura::whereNotNull('ruc_emisor')->distinct()->pluck('ruc_emisor')->mapWithKeys(fn ($r) => [$r => 'Clínica / otro'])->all())
            ->all();

        return view('livewire.reporte-ventas', [
            'facturas' => $facturas,
            'notas' => $notas,
            'resumen' => $this->resumen($facturas, $notas),
            'rucs' => $rucs,
        ])->layout('layouts.app');
    }
}
