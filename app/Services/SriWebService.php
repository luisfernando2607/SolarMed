<?php
namespace App\Services;

use App\Models\Factura;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SriWebService {

    private function getWsdlUrl(string $ambiente): array
    {
        if ($ambiente === '2') {
            return [
                'recepcion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
                'autorizacion' => 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
            ];
        }
        return [
            'recepcion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl',
            'autorizacion' => 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl',
        ];
    }

    public function enviar(Factura $factura): array
    {
        $xmlFirmado = $factura->xml_autorizado_path
            ? Storage::disk('private')->get($factura->xml_autorizado_path)
            : null;

        if (!$xmlFirmado) {
            throw new \RuntimeException('XML firmado no encontrado para la factura ' . $factura->numero_factura);
        }

        $xmlBase64 = base64_encode($xmlFirmado);
        $urls = $this->getWsdlUrl($factura->ambiente_sri ?? '1');

        try {
            $client = new \SoapClient($urls['recepcion'], [
                'stream_context' => stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]),
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true,
            ]);

            $result = $client->validarComprobante([
                'xml' => $xmlBase64,
            ]);

            $response = $this->parseRecepcionResponse($result);

            if (($response['estado'] ?? '') === 'RECIBIDA') {
                $factura->update(['estado_sri' => 'enviado']);
            } else {
                $factura->update(['estado_sri' => 'rechazado']);
            }

            return $response;
        } catch (\SoapFault $e) {
            Log::error('SRI Recepción error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $factura->update(['estado_sri' => 'error_envio']);
            throw new \RuntimeException('Error al enviar al SRI: ' . $e->getMessage());
        }
    }

    public function autorizar(Factura $factura): array
    {
        $urls = $this->getWsdlUrl($factura->ambiente_sri ?? '1');
        $claveAcceso = $factura->clave_acceso;

        if (!$claveAcceso) {
            throw new \RuntimeException('Clave de acceso no disponible para la factura ' . $factura->numero_factura);
        }

        try {
            $client = new \SoapClient($urls['autorizacion'], [
                'stream_context' => stream_context_create([
                    'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                ]),
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true,
            ]);

            $result = $client->autorizacionComprobante([
                'claveAccesoComprobante' => $claveAcceso,
            ]);

            $response = $this->parseAutorizacionResponse($result);

            if (($response['estado'] ?? '') === 'AUTORIZADO') {
                $factura->update([
                    'estado_sri' => 'autorizado',
                    'numero_autorizacion' => $response['numeroAutorizacion'] ?? null,
                ]);
            } else {
                $factura->update(['estado_sri' => 'no_autorizado']);
            }

            return $response;
        } catch (\SoapFault $e) {
            Log::error('SRI Autorización error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $factura->update(['estado_sri' => 'error_autorizacion']);
            throw new \RuntimeException('Error al autorizar en el SRI: ' . $e->getMessage());
        }
    }

    private function parseRecepcionResponse($result): array
    {
        $estado = '';
        $mensajes = [];

        if (isset($result->RespuestaRecepcionComprobante->comprobante->mensajes)) {
            $mensajesRaw = $result->RespuestaRecepcionComprobante->comprobante->mensajes;
            if (isset($mensajesRaw->mensaje)) {
                foreach ((array) $mensajesRaw->mensaje as $msg) {
                    $mensajes[] = [
                        'identificador' => $msg->identificador ?? '',
                        'mensaje' => $msg->mensaje ?? '',
                        'informacionAdicional' => $msg->informacionAdicional ?? '',
                        'tipo' => $msg->tipo ?? '',
                    ];
                }
            }
        }

        $estado = $result->RespuestaRecepcionComprobante->comprobante->estado ?? '';

        return ['estado' => $estado, 'mensajes' => $mensajes];
    }

    private function parseAutorizacionResponse($result): array
    {
        $autorizaciones = $result->RespuestaAutorizacionComprobante?->autorizaciones?->autorizacion ?? [];
        $primera = is_array($autorizaciones) ? ($autorizaciones[0] ?? $autorizaciones) : $autorizaciones;

        return [
            'estado' => $primera->estado ?? '',
            'numeroAutorizacion' => $primera->numeroAutorizacion ?? '',
            'fechaAutorizacion' => $primera->fechaAutorizacion ?? '',
            'ambiente' => $primera->ambiente ?? '',
        ];
    }
}
