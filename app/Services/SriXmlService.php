<?php
namespace App\Services;

use App\Models\Factura;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Log;

class SriXmlService {

    /**
     * Genera el XML de factura electrónica para el SRI.
     *
     * @return array{xml: string, clave_acceso: string}
     */
    public function generar(Factura $factura): array
    {
        $medico = $factura->medico;
        $paciente = $factura->paciente;
        $config = app(SriConfigService::class);

        $ruc = $config->get('sri_ruc');
        if (!$ruc) {
            throw new \RuntimeException('Configure el RUC en SRI > Configuración antes de generar el XML.');
        }

        $razonSocial = $config->get('sri_razon_social');
        if (!$razonSocial) {
            throw new \RuntimeException('Configure la razón social en SRI > Configuración antes de generar el XML.');
        }

        $ambiente = $config->get('sri_ambiente', '1');
        $estab = $config->get('sri_establecimiento', '001');
        $ptoEmi = $config->get('sri_pto_emi', '001');

        // Extraer secuencial desde el numero_factura (NV-000001 → 000001)
        $secuencial = $this->extraerSecuencial($factura->numero_factura);
        $claveAcceso = $this->generarClaveAcceso($factura, $ruc, $ambiente, $estab, $ptoEmi, $secuencial);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $facturaEl = $doc->createElement('factura');
        $facturaEl->setAttribute('id', 'comprobante');
        $facturaEl->setAttribute('version', '1.1.0');
        $doc->appendChild($facturaEl);

        // infoTributaria
        $infoTributaria = $doc->createElement('infoTributaria');
        $facturaEl->appendChild($infoTributaria);
        $this->addChild($doc, $infoTributaria, 'ambiente', $ambiente);
        $this->addChild($doc, $infoTributaria, 'tipoEmision', '1');
        $this->addChild($doc, $infoTributaria, 'razonSocial', $config->get('sri_razon_social', 'Clínica'));
        $this->addChild($doc, $infoTributaria, 'nombreComercial', $config->get('sri_nombre_comercial', ''));
        $this->addChild($doc, $infoTributaria, 'ruc', $ruc);
        $this->addChild($doc, $infoTributaria, 'claveAcceso', $claveAcceso);
        $this->addChild($doc, $infoTributaria, 'codDoc', '01');
        $this->addChild($doc, $infoTributaria, 'estab', $estab);
        $this->addChild($doc, $infoTributaria, 'ptoEmi', $ptoEmi);
        $this->addChild($doc, $infoTributaria, 'secuencial', $secuencial);
        $this->addChild($doc, $infoTributaria, 'dirMatriz', $config->get('sri_direccion', ''));

        // infoFactura
        $infoFactura = $doc->createElement('infoFactura');
        $facturaEl->appendChild($infoFactura);
        $this->addChild($doc, $infoFactura, 'fechaEmision', $factura->created_at->format('d/m/Y'));
        $this->addChild($doc, $infoFactura, 'dirEstablecimiento', $config->get('sri_direccion', ''));
        $this->addChild($doc, $infoFactura, 'obligadoContabilidad', $config->get('sri_obligado_contabilidad', 'NO'));

        $tipoId = strlen($paciente?->cedula ?? '') === 13 ? '04' : '05';
        $this->addChild($doc, $infoFactura, 'tipoIdentificacionComprador', $tipoId);
        $this->addChild($doc, $infoFactura, 'razonSocialComprador', $paciente?->nombre_completo ?? '');
        $this->addChild($doc, $infoFactura, 'identificacionComprador', $paciente?->cedula ?? '');
        $this->addChild($doc, $infoFactura, 'direccionComprador', substr($paciente?->direccion ?? '', 0, 300));

        $totalSinImpuestos = $factura->subtotal;
        $this->addChild($doc, $infoFactura, 'totalSinImpuestos', number_format($totalSinImpuestos, 2, '.', ''));
        $this->addChild($doc, $infoFactura, 'totalDescuento', number_format($factura->descuento, 2, '.', ''));

        $totalConImpuestos = $doc->createElement('totalConImpuestos');
        $infoFactura->appendChild($totalConImpuestos);
        $totalImpuesto = $doc->createElement('totalImpuesto');
        $totalConImpuestos->appendChild($totalImpuesto);
        $this->addChild($doc, $totalImpuesto, 'codigo', '2');
        $this->addChild($doc, $totalImpuesto, 'codigoPorcentaje', '0');
        $this->addChild($doc, $totalImpuesto, 'baseImponible', number_format($totalSinImpuestos, 2, '.', ''));
        $this->addChild($doc, $totalImpuesto, 'tarifa', '0');
        $this->addChild($doc, $totalImpuesto, 'valor', '0.00');

        $this->addChild($doc, $infoFactura, 'propina', '0.00');
        $this->addChild($doc, $infoFactura, 'importeTotal', number_format($factura->total, 2, '.', ''));
        $this->addChild($doc, $infoFactura, 'moneda', 'DOLAR');

        $pagos = $doc->createElement('pagos');
        $infoFactura->appendChild($pagos);
        $pago = $doc->createElement('pago');
        $pagos->appendChild($pago);
        $formaPagoMap = ['efectivo' => '01', 'transferencia' => '16', 'tarjeta' => '19'];
        $this->addChild($doc, $pago, 'formaPago', $formaPagoMap[$factura->forma_pago] ?? '01');
        $this->addChild($doc, $pago, 'total', number_format($factura->total, 2, '.', ''));
        $this->addChild($doc, $pago, 'plazo', '0');
        $this->addChild($doc, $pago, 'unidadTiempo', 'dias');

        // detalles
        $detalles = $doc->createElement('detalles');
        $facturaEl->appendChild($detalles);
        foreach ($factura->items as $item) {
            $detalle = $doc->createElement('detalle');
            $detalles->appendChild($detalle);
            $this->addChild($doc, $detalle, 'codigoPrincipal', (string) ($item->servicio_id ?? $item->id));
            $this->addChild($doc, $detalle, 'descripcion', substr($item->descripcion ?? '', 0, 300));
            $this->addChild($doc, $detalle, 'cantidad', number_format($item->cantidad, 2, '.', ''));
            $this->addChild($doc, $detalle, 'precioUnitario', number_format($item->precio_unitario, 2, '.', ''));
            $this->addChild($doc, $detalle, 'descuento', '0.00');
            $this->addChild($doc, $detalle, 'precioTotalSinImpuesto', number_format($item->subtotal, 2, '.', ''));
            $impuestos = $doc->createElement('impuestos');
            $detalle->appendChild($impuestos);
            $impuesto = $doc->createElement('impuesto');
            $impuestos->appendChild($impuesto);
            $this->addChild($doc, $impuesto, 'codigo', '2');
            $this->addChild($doc, $impuesto, 'codigoPorcentaje', '0');
            $this->addChild($doc, $impuesto, 'tarifa', '0');
            $this->addChild($doc, $impuesto, 'baseImponible', number_format($item->subtotal, 2, '.', ''));
            $this->addChild($doc, $impuesto, 'valor', '0.00');
        }

        // infoAdicional
        $infoAdicional = $doc->createElement('infoAdicional');
        $facturaEl->appendChild($infoAdicional);
        if ($paciente?->email) {
            $campo = $doc->createElement('campoAdicional', substr($paciente->email, 0, 300));
            $campo->setAttribute('nombre', 'Email');
            $infoAdicional->appendChild($campo);
        }
        if ($medico) {
            $campo = $doc->createElement('campoAdicional', substr($medico->nombre_completo, 0, 300));
            $campo->setAttribute('nombre', 'Médico');
            $infoAdicional->appendChild($campo);
        }

        $xml = $doc->saveXML();

        return [
            'xml' => $xml,
            'clave_acceso' => $claveAcceso,
        ];
    }

    private function extraerSecuencial(?string $numeroFactura): string
    {
        if (!$numeroFactura) {
            throw new \RuntimeException('La factura no tiene número asignado.');
        }
        // formato esperado: NV-000001 → 000001
        $partes = explode('-', $numeroFactura);
        $num = end($partes);
        return str_pad((int) $num, 9, '0', STR_PAD_LEFT);
    }

    public function generarClaveAcceso(Factura $factura, string $ruc, string $ambiente, string $estab, string $ptoEmi, string $secuencial): string
    {
        $fecha = $factura->created_at->format('dmY');
        $codDoc = '01';
        $codNum = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $tipoEmision = '1';
        $base = $fecha . $codDoc . $ruc . $ambiente . $estab . $ptoEmi . $secuencial . $codNum . $tipoEmision;
        $mod = $this->mod11($base);
        return $base . $mod;
    }

    private function mod11(string $numero): string
    {
        $factores = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
        $total = 0;
        $len = strlen($numero);
        $rev = strrev($numero);
        for ($i = 0; $i < $len; $i++) {
            $total += (int) $rev[$i] * $factores[$i % 12];
        }
        $residuo = $total % 11;
        $verificador = 11 - $residuo;
        if ($verificador === 11) return '0';
        if ($verificador === 10) return '1';
        return (string) $verificador;
    }

    private function addChild(DOMDocument $doc, DOMElement $parent, string $name, string $value): void
    {
        $child = $doc->createElement($name);
        $child->appendChild($doc->createTextNode($value));
        $parent->appendChild($child);
    }
}
