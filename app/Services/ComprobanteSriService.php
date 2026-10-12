<?php
namespace App\Services;

use App\Jobs\AutorizarComprobanteSri;
use App\Jobs\EnviarComprobantePorCorreo;
use App\Models\Factura;
use App\Models\NotaCredito;
use Illuminate\Support\Facades\Storage;

/**
 * Ciclo de vida de un comprobante electrónico (factura o nota de crédito) frente al SRI:
 * generar XML → firmar → enviar (recepción) → autorizar. Cada paso retoma desde el estado guardado.
 */
class ComprobanteSriService {

    /** Estados en los que el SRI todavía no aceptó el comprobante, por lo que el XML se puede (re)generar */
    public const REGENERABLES = ['pendiente', 'xml_generado', 'rechazado', 'no_autorizado'];

    /** Estados que se resuelven solos consultando de nuevo al SRI */
    public const PENDIENTES_SRI = ['firmado', 'error_envio', 'enviado', 'error_autorizacion'];

    /**
     * Genera, firma y envía el comprobante. Si el SRI lo recibe, programa la consulta automática de autorización.
     *
     * @return array{estado: string, mensajes: array}
     */
    public function procesar(Factura|NotaCredito $comprobante): array
    {
        $estado = $comprobante->estado_sri ?: 'pendiente';

        if ($comprobante instanceof Factura && $comprobante->estado === 'anulada') {
            throw new \RuntimeException('La factura está anulada; no se puede emitir al SRI.');
        }

        if (in_array($estado, self::REGENERABLES, true)) {
            $this->generarXml($comprobante);
            $estado = 'xml_generado';
        }

        if ($estado === 'xml_generado') {
            $this->firmar($comprobante);
            $estado = 'firmado';
        }

        if (!in_array($estado, ['firmado', 'error_envio'], true)) {
            throw new \RuntimeException('El comprobante ya fue enviado al SRI; consulte su autorización.');
        }

        $resultado = app(SriWebService::class)->enviar($comprobante);

        if ($resultado['estado'] === 'RECIBIDA') {
            $this->programarAutorizacion($comprobante);
        }

        return $resultado;
    }

    /**
     * Consulta la autorización. Si una nota de crédito queda autorizada, su factura se anula.
     *
     * @return array{estado: string, numeroAutorizacion: string, fechaAutorizacion: string, mensajes: array}
     */
    public function autorizar(Factura|NotaCredito $comprobante): array
    {
        $resultado = app(SriWebService::class)->autorizar($comprobante);

        if ($resultado['estado'] === 'AUTORIZADO' && $comprobante instanceof NotaCredito) {
            $comprobante->factura->update(['estado' => 'anulada']);
        }

        // Entrega automática al paciente si tiene correo registrado
        if ($resultado['estado'] === 'AUTORIZADO' && !$comprobante->email_enviado_at) {
            EnviarComprobantePorCorreo::dispatch($comprobante);
        }

        return $resultado;
    }

    /** El SRI suele tardar unos segundos en autorizar: se consulta en segundo plano. */
    public function programarAutorizacion(Factura|NotaCredito $comprobante, int $segundos = 15): void
    {
        AutorizarComprobanteSri::dispatch($comprobante)->delay(now()->addSeconds($segundos));
    }

    private function generarXml(Factura|NotaCredito $comprobante): void
    {
        if ($comprobante instanceof NotaCredito) {
            $comprobante->loadMissing('factura.paciente', 'factura.items', 'medico');
            $resultado = app(SriXmlService::class)->generarNotaCredito($comprobante);
            $path = "sri/nota_credito_{$comprobante->id}.xml";
        } else {
            $comprobante->loadMissing('paciente', 'items', 'medico');
            if ($comprobante->items->isEmpty()) {
                throw new \RuntimeException('La factura no tiene ítems. No se puede generar el XML.');
            }
            $resultado = app(SriXmlService::class)->generar($comprobante);
            $path = "sri/xml_enviado_{$comprobante->id}.xml";
        }

        Storage::disk('private')->put($path, $resultado['xml']);
        $comprobante->update([
            'xml_enviado_path' => $path,
            'xml_firmado_path' => null,
            'clave_acceso' => $resultado['clave_acceso'],
            'estado_sri' => 'xml_generado',
            'mensajes_sri' => null,
        ]);
    }

    private function firmar(Factura|NotaCredito $comprobante): void
    {
        $medico = $comprobante->medico;
        if (!$medico) {
            throw new \RuntimeException('El comprobante no tiene médico emisor asignado.');
        }
        if (!app(SriXmlService::class)->claveAccesoValida($comprobante->clave_acceso)) {
            throw new \RuntimeException('La clave de acceso del XML no es válida; vuelva a generarlo.');
        }

        $firmado = app(SriSignService::class)->firmar(Storage::disk('private')->get($comprobante->xml_enviado_path), $medico);
        $path = $comprobante instanceof NotaCredito
            ? "sri/nota_credito_firmada_{$comprobante->id}.xml"
            : "sri/xml_firmado_{$comprobante->id}.xml";

        Storage::disk('private')->put($path, $firmado);
        $comprobante->update(['xml_firmado_path' => $path, 'estado_sri' => 'firmado']);
    }
}
