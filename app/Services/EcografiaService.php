<?php
namespace App\Services;

use App\Models\Ecografia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class EcografiaService {

    /**
     * Genera el PDF del informe de ecografía y guarda la ruta en la BD.
     */
    public function generarPdf(Ecografia $eco): string {
        $eco->load('paciente', 'medico.especialidad');

        $pdf = Pdf::loadView('pdf.ecografia', ['eco' => $eco])
                  ->setPaper('a4', 'portrait');

        $nombre = "eco_{$eco->paciente_id}_{$eco->id}_{$eco->fecha}.pdf";
        $ruta   = "pacientes/{$eco->paciente_id}/ecografias/{$nombre}";

        Storage::disk('private')->put($ruta, $pdf->output());

        $eco->update(['pdf_path' => $ruta]);

        return $ruta;
    }
}
