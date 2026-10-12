<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\NotaCredito;
use Barryvdh\DomPDF\Facade\Pdf;

/** Representación impresa (RIDE) en formato ticket de facturas y notas de crédito. */
class RidePdfService
{
    public function factura(Factura $factura): \Barryvdh\DomPDF\PDF
    {
        $factura->loadMissing('paciente', 'medico', 'items');

        return Pdf::loadView('pdf.factura', [
            'factura' => $factura,
            'emisor' => app(SriConfigService::class)->emisor($factura->medico),
        ])->setPaper([0, 0, 226, $factura->clave_acceso ? 500 : 430], 'portrait');
    }

    public function notaCredito(NotaCredito $nota): \Barryvdh\DomPDF\PDF
    {
        $nota->loadMissing('factura.paciente', 'factura.items', 'medico');

        return Pdf::loadView('pdf.nota-credito', [
            'nota' => $nota,
            'emisor' => app(SriConfigService::class)->emisor($nota->medico),
        ])->setPaper([0, 0, 226, 500], 'portrait');
    }
}
