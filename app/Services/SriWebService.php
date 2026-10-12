<?php
namespace App\Services;

use App\Models\Factura;
use DOMDocument;
use DOMElement;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Cliente de los web services offline del SRI (recepción y autorización).
 * Usa SOAP 1.1 sobre HTTP directamente, sin depender de la extensión ext-soap.
 */
class SriWebService {

    private const NS_RECEPCION = 'http://ec.gob.sri.ws.recepcion';
    private const NS_AUTORIZACION = 'http://ec.gob.sri.ws.autorizacion';

    /** Identificador del SRI para "CLAVE ACCESO REGISTRADA": el comprobante ya fue recibido antes. */
    private const CLAVE_REGISTRADA = '43';

    private function urls(string $ambiente): array
    {
        $host = $ambiente === '2' ? 'https://cel.sri.gob.ec' : 'https://celcer.sri.gob.ec';
        return [
            'recepcion' => "{$host}/comprobantes-electronicos-ws/RecepcionComprobantesOffline",
            'autorizacion' => "{$host}/comprobantes-electronicos-ws/AutorizacionComprobantesOffline",
        ];
    }

    /**
     * Envía el comprobante firmado al servicio de recepción.
     *
     * @return array{estado: string, mensajes: array}
     */
    public function enviar(Factura $factura): array
    {
        $path = $factura->xml_firmado_path;
        $xmlFirmado = $path ? Storage::disk('private')->get($path) : null;

        if (!$xmlFirmado) {
            throw new \RuntimeException('XML firmado no encontrado para la factura ' . $factura->numero_factura);
        }

        $body = '<ec:validarComprobante><xml>' . base64_encode($xmlFirmado) . '</xml></ec:validarComprobante>';

        try {
            $respuesta = $this->llamar($this->urls($factura->ambiente_sri ?? '1')['recepcion'], self::NS_RECEPCION, $body);
        } catch (\Throwable $e) {
            Log::error('SRI Recepción error: ' . $e->getMessage(), ['factura' => $factura->id]);
            $factura->update(['estado_sri' => 'error_envio']);
            throw new \RuntimeException('No se pudo conectar con el SRI. Intente nuevamente en unos minutos.');
        }

        $estado = $this->texto($respuesta, 'estado');
        $mensajes = $this->mensajes($respuesta);

        $yaRegistrada = collect($mensajes)->contains(fn ($m) => $m['identificador'] === self::CLAVE_REGISTRADA);

        if ($estado === 'RECIBIDA' || $yaRegistrada) {
            $factura->update(['estado_sri' => 'enviado', 'mensajes_sri' => $mensajes ?: null]);
            $estado = 'RECIBIDA';
        } else {
            $factura->update(['estado_sri' => 'rechazado', 'mensajes_sri' => $mensajes]);
        }

        return ['estado' => $estado, 'mensajes' => $mensajes];
    }

    /**
     * Consulta la autorización del comprobante por su clave de acceso.
     *
     * @return array{estado: string, numeroAutorizacion: string, fechaAutorizacion: string, mensajes: array}
     */
    public function autorizar(Factura $factura): array
    {
        $claveAcceso = $factura->clave_acceso;

        if (!$claveAcceso) {
            throw new \RuntimeException('Clave de acceso no disponible para la factura ' . $factura->numero_factura);
        }

        $body = '<ec:autorizacionComprobante><claveAccesoComprobante>' . $claveAcceso . '</claveAccesoComprobante></ec:autorizacionComprobante>';

        try {
            $respuesta = $this->llamar($this->urls($factura->ambiente_sri ?? '1')['autorizacion'], self::NS_AUTORIZACION, $body);
        } catch (\Throwable $e) {
            Log::error('SRI Autorización error: ' . $e->getMessage(), ['factura' => $factura->id]);
            $factura->update(['estado_sri' => 'error_autorizacion']);
            throw new \RuntimeException('No se pudo conectar con el SRI. Intente nuevamente en unos minutos.');
        }

        $autorizacion = $respuesta->getElementsByTagName('autorizacion')->item(0);

        if (!$autorizacion instanceof DOMElement) {
            // El SRI aún no procesa el comprobante
            return ['estado' => 'EN PROCESO', 'numeroAutorizacion' => '', 'fechaAutorizacion' => '', 'mensajes' => []];
        }

        $resultado = [
            'estado' => $this->texto($autorizacion, 'estado'),
            'numeroAutorizacion' => $this->texto($autorizacion, 'numeroAutorizacion'),
            'fechaAutorizacion' => $this->texto($autorizacion, 'fechaAutorizacion'),
            'mensajes' => $this->mensajes($autorizacion),
        ];

        if ($resultado['estado'] === 'AUTORIZADO') {
            $path = "sri/xml_autorizado_{$factura->id}.xml";
            Storage::disk('private')->put($path, $this->xmlAutorizado($autorizacion));

            $factura->update([
                'estado_sri' => 'autorizado',
                'numero_autorizacion' => $resultado['numeroAutorizacion'] ?: $claveAcceso,
                'fecha_autorizacion' => $resultado['fechaAutorizacion'] ? \Carbon\Carbon::parse($resultado['fechaAutorizacion']) : now(),
                'xml_autorizado_path' => $path,
                'mensajes_sri' => $resultado['mensajes'] ?: null,
            ]);
        } elseif ($resultado['estado'] === 'EN PROCESO') {
            $factura->update(['estado_sri' => 'enviado']);
        } else {
            $factura->update(['estado_sri' => 'no_autorizado', 'mensajes_sri' => $resultado['mensajes']]);
        }

        return $resultado;
    }

    /** Envía un sobre SOAP 1.1 y devuelve el cuerpo de la respuesta como DOM. */
    private function llamar(string $url, string $namespace, string $body): DOMDocument
    {
        $sobre = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ec="' . $namespace . '">'
            . '<soapenv:Header/><soapenv:Body>' . $body . '</soapenv:Body></soapenv:Envelope>';

        $response = Http::withHeaders(['Content-Type' => 'text/xml; charset=utf-8', 'SOAPAction' => '""'])
            ->timeout(30)
            ->retry(2, 1000, fn ($e) => $e instanceof ConnectionException, throw: false)
            ->withBody($sobre, 'text/xml; charset=utf-8')
            ->post($url);

        $dom = new DOMDocument();
        if (!$response->body() || !@$dom->loadXML($response->body())) {
            throw new \RuntimeException("Respuesta inválida del SRI (HTTP {$response->status()}).");
        }

        $fault = $dom->getElementsByTagName('faultstring')->item(0);
        if ($fault) {
            throw new \RuntimeException('SOAP Fault del SRI: ' . $fault->textContent);
        }

        return $dom;
    }

    private function texto(DOMDocument|DOMElement $nodo, string $tag): string
    {
        return trim($nodo->getElementsByTagName($tag)->item(0)?->textContent ?? '');
    }

    /** @return array<int, array{identificador: string, mensaje: string, informacionAdicional: string, tipo: string}> */
    private function mensajes(DOMDocument|DOMElement $nodo): array
    {
        $mensajes = [];
        foreach ($nodo->getElementsByTagName('mensaje') as $m) {
            // <mensaje> contiene a su vez un <mensaje> con el texto: solo se procesan los contenedores
            if (!$m instanceof DOMElement || !$m->getElementsByTagName('identificador')->length) {
                continue;
            }
            $mensajes[] = [
                'identificador' => $this->texto($m, 'identificador'),
                'mensaje' => trim($m->getElementsByTagName('mensaje')->item(0)?->textContent ?? ''),
                'informacionAdicional' => $this->texto($m, 'informacionAdicional'),
                'tipo' => $this->texto($m, 'tipo'),
            ];
        }
        return $mensajes;
    }

    /** Arma el XML de autorización que se entrega al cliente (estado, número, fecha y comprobante firmado). */
    private function xmlAutorizado(DOMElement $autorizacion): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $raiz = $doc->appendChild($doc->createElement('autorizacion'));
        foreach (['estado', 'numeroAutorizacion', 'fechaAutorizacion', 'ambiente'] as $tag) {
            $raiz->appendChild($doc->createElement($tag))->appendChild($doc->createTextNode($this->texto($autorizacion, $tag)));
        }
        $raiz->appendChild($doc->createElement('comprobante'))
            ->appendChild($doc->createCDATASection($this->texto($autorizacion, 'comprobante')));

        return $doc->saveXML();
    }
}
