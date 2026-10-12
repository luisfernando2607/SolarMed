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

        $emisor = $config->emisor($medico);
        $dondeConfigurar = $emisor['origen'] === 'medico' ? 'Usuarios > datos tributarios del médico' : 'SRI > Configuración';

        $ruc = $emisor['ruc'];
        if (!$ruc || !preg_match('/^\d{13}$/', $ruc)) {
            throw new \RuntimeException("Configure un RUC válido (13 dígitos) en {$dondeConfigurar} antes de generar el XML.");
        }

        // La numeración de la factura pertenece al RUC con el que se emitió
        if ($factura->ruc_emisor && $factura->ruc_emisor !== $ruc) {
            throw new \RuntimeException("La factura se numeró con el RUC {$factura->ruc_emisor}, pero el emisor actual tiene RUC {$ruc}. Corrija el RUC del médico o emita una nueva factura.");
        }

        $razonSocial = $emisor['razon_social'];
        if (!$razonSocial) {
            throw new \RuntimeException("Configure la razón social en {$dondeConfigurar} antes de generar el XML.");
        }

        $direccion = $emisor['direccion'];
        if (!$direccion) {
            throw new \RuntimeException("Configure la dirección matriz en {$dondeConfigurar} antes de generar el XML.");
        }

        // Ambiente, establecimiento, punto de emisión y secuencial salen de la propia factura,
        // para que el XML no cambie si luego se modifica la configuración.
        $ambiente = (string) ($factura->ambiente_sri ?: $config->get('sri_ambiente', '1'));
        [$estab, $ptoEmi, $secuencial] = $this->partesNumero($factura->numero_factura);
        $fechaEmision = $factura->fecha ?? $factura->created_at;

        // La clave de acceso se genera una sola vez por factura: el SRI identifica el comprobante con ella
        $claveAcceso = $this->claveAccesoValida($factura->clave_acceso)
            ? $factura->clave_acceso
            : $this->generarClaveAcceso($factura, $ruc, $ambiente, $estab, $ptoEmi, $secuencial);

        $comprador = $this->comprador($paciente);
        $lineas = $this->lineasConDescuento($factura);
        $totalDescuento = array_sum(array_column($lineas, 'descuento'));
        $totalSinImpuestos = array_sum(array_column($lineas, 'total'));

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
        $this->addChild($doc, $infoTributaria, 'razonSocial', mb_substr($razonSocial, 0, 300));
        if ($nombreComercial = $emisor['nombre_comercial']) {
            $this->addChild($doc, $infoTributaria, 'nombreComercial', mb_substr($nombreComercial, 0, 300));
        }
        $this->addChild($doc, $infoTributaria, 'ruc', $ruc);
        $this->addChild($doc, $infoTributaria, 'claveAcceso', $claveAcceso);
        $this->addChild($doc, $infoTributaria, 'codDoc', '01');
        $this->addChild($doc, $infoTributaria, 'estab', $estab);
        $this->addChild($doc, $infoTributaria, 'ptoEmi', $ptoEmi);
        $this->addChild($doc, $infoTributaria, 'secuencial', $secuencial);
        $this->addChild($doc, $infoTributaria, 'dirMatriz', mb_substr($direccion, 0, 300));
        if ($emisor['regimen_rimpe']) {
            $this->addChild($doc, $infoTributaria, 'contribuyenteRimpe', $emisor['regimen_rimpe']);
        }

        // infoFactura
        $infoFactura = $doc->createElement('infoFactura');
        $facturaEl->appendChild($infoFactura);
        $this->addChild($doc, $infoFactura, 'fechaEmision', $fechaEmision->format('d/m/Y'));
        $this->addChild($doc, $infoFactura, 'dirEstablecimiento', mb_substr($direccion, 0, 300));
        if ($contribuyenteEspecial = $emisor['contribuyente_especial']) {
            $this->addChild($doc, $infoFactura, 'contribuyenteEspecial', $contribuyenteEspecial);
        }
        $this->addChild($doc, $infoFactura, 'obligadoContabilidad', $emisor['obligado_contabilidad']);
        $this->addChild($doc, $infoFactura, 'tipoIdentificacionComprador', $comprador['tipo']);
        $this->addChild($doc, $infoFactura, 'razonSocialComprador', $comprador['nombre']);
        $this->addChild($doc, $infoFactura, 'identificacionComprador', $comprador['identificacion']);
        if ($comprador['direccion']) {
            $this->addChild($doc, $infoFactura, 'direccionComprador', $comprador['direccion']);
        }
        $this->addChild($doc, $infoFactura, 'totalSinImpuestos', $this->monto($totalSinImpuestos));
        $this->addChild($doc, $infoFactura, 'totalDescuento', $this->monto($totalDescuento));

        // Servicios de salud: IVA 0 %
        $totalConImpuestos = $doc->createElement('totalConImpuestos');
        $infoFactura->appendChild($totalConImpuestos);
        $totalImpuesto = $doc->createElement('totalImpuesto');
        $totalConImpuestos->appendChild($totalImpuesto);
        $this->addChild($doc, $totalImpuesto, 'codigo', '2');
        $this->addChild($doc, $totalImpuesto, 'codigoPorcentaje', '0');
        $this->addChild($doc, $totalImpuesto, 'baseImponible', $this->monto($totalSinImpuestos));
        $this->addChild($doc, $totalImpuesto, 'tarifa', '0');
        $this->addChild($doc, $totalImpuesto, 'valor', '0.00');

        $this->addChild($doc, $infoFactura, 'propina', '0.00');
        $this->addChild($doc, $infoFactura, 'importeTotal', $this->monto($totalSinImpuestos));
        $this->addChild($doc, $infoFactura, 'moneda', 'DOLAR');

        $pagos = $doc->createElement('pagos');
        $infoFactura->appendChild($pagos);
        $pago = $doc->createElement('pago');
        $pagos->appendChild($pago);
        $formaPagoMap = ['efectivo' => '01', 'transferencia' => '20', 'tarjeta' => '19'];
        $this->addChild($doc, $pago, 'formaPago', $formaPagoMap[$factura->forma_pago] ?? '01');
        $this->addChild($doc, $pago, 'total', $this->monto($totalSinImpuestos));

        // detalles
        $detalles = $doc->createElement('detalles');
        $facturaEl->appendChild($detalles);
        foreach ($lineas as $linea) {
            $item = $linea['item'];
            $detalle = $doc->createElement('detalle');
            $detalles->appendChild($detalle);
            $this->addChild($doc, $detalle, 'codigoPrincipal', (string) ($item->servicio_id ?? $item->id));
            $this->addChild($doc, $detalle, 'descripcion', mb_substr($item->descripcion ?? '', 0, 300));
            $this->addChild($doc, $detalle, 'cantidad', number_format($item->cantidad, 2, '.', ''));
            $this->addChild($doc, $detalle, 'precioUnitario', $this->monto($item->precio_unitario));
            $this->addChild($doc, $detalle, 'descuento', $this->monto($linea['descuento']));
            $this->addChild($doc, $detalle, 'precioTotalSinImpuesto', $this->monto($linea['total']));
            $impuestos = $doc->createElement('impuestos');
            $detalle->appendChild($impuestos);
            $impuesto = $doc->createElement('impuesto');
            $impuestos->appendChild($impuesto);
            $this->addChild($doc, $impuesto, 'codigo', '2');
            $this->addChild($doc, $impuesto, 'codigoPorcentaje', '0');
            $this->addChild($doc, $impuesto, 'tarifa', '0');
            $this->addChild($doc, $impuesto, 'baseImponible', $this->monto($linea['total']));
            $this->addChild($doc, $impuesto, 'valor', '0.00');
        }

        // infoAdicional (el esquema exige al menos un campo si el nodo existe)
        $adicionales = array_filter([
            'Email' => $paciente?->email,
            'Teléfono' => $paciente?->telefono,
            'Médico' => $medico?->nombre_completo,
        ]);
        if ($adicionales) {
            $infoAdicional = $doc->createElement('infoAdicional');
            $facturaEl->appendChild($infoAdicional);
            foreach ($adicionales as $nombre => $valor) {
                $campo = $doc->createElement('campoAdicional');
                $campo->setAttribute('nombre', $nombre);
                $campo->appendChild($doc->createTextNode(mb_substr($valor, 0, 300)));
                $infoAdicional->appendChild($campo);
            }
        }

        return [
            'xml' => $doc->saveXML(),
            'clave_acceso' => $claveAcceso,
        ];
    }

    /** "001-002-000000123" → ['001', '002', '000000123'] */
    private function partesNumero(?string $numeroFactura): array
    {
        if (!$numeroFactura || !preg_match('/^(\d{3})-(\d{3})-(\d{1,9})$/', $numeroFactura, $m)) {
            throw new \RuntimeException("El número de factura '{$numeroFactura}' no tiene el formato 001-001-000000001.");
        }
        return [$m[1], $m[2], str_pad($m[3], 9, '0', STR_PAD_LEFT)];
    }

    /** Tipo de identificación del comprador según la tabla 6 de la ficha técnica del SRI. */
    private function comprador($paciente): array
    {
        $identificacion = preg_replace('/\s+/', '', (string) ($paciente?->cedula ?? ''));

        if ($identificacion === '' || $identificacion === '9999999999999') {
            return ['tipo' => '07', 'identificacion' => '9999999999999', 'nombre' => 'CONSUMIDOR FINAL', 'direccion' => null];
        }

        $tipo = match (true) {
            (bool) preg_match('/^\d{13}$/', $identificacion) => '04', // RUC
            (bool) preg_match('/^\d{10}$/', $identificacion) => '05', // Cédula
            default => '06',                                          // Pasaporte
        };

        return [
            'tipo' => $tipo,
            'identificacion' => $identificacion,
            'nombre' => mb_substr($paciente->nombre_completo, 0, 300),
            'direccion' => $paciente->direccion ? mb_substr($paciente->direccion, 0, 300) : null,
        ];
    }

    /**
     * Reparte el descuento global de la factura entre sus líneas (proporcional al subtotal de cada una),
     * porque el SRI valida que totalDescuento y totalSinImpuestos cuadren con la suma de los detalles.
     */
    private function lineasConDescuento(Factura $factura): array
    {
        $items = $factura->items->values();
        $descuentoTotal = round((float) $factura->descuento, 2);
        $subtotal = (float) $items->sum(fn ($i) => (float) $i->subtotal);
        $lineas = [];
        $repartido = 0.0;

        foreach ($items as $idx => $item) {
            $bruto = round((float) $item->subtotal, 2);
            $esUltima = $idx === $items->count() - 1;
            $descuento = $subtotal > 0
                ? ($esUltima ? round($descuentoTotal - $repartido, 2) : round($descuentoTotal * $bruto / $subtotal, 2))
                : 0.0;
            $repartido += $descuento;
            $lineas[] = ['item' => $item, 'descuento' => $descuento, 'total' => round($bruto - $descuento, 2)];
        }

        return $lineas;
    }

    private function monto($valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    public function generarClaveAcceso(Factura $factura, string $ruc, string $ambiente, string $estab, string $ptoEmi, string $secuencial): string
    {
        $fecha = ($factura->fecha ?? $factura->created_at)->format('dmY');
        $codDoc = '01';
        $codNum = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $tipoEmision = '1';
        $base = $fecha . $codDoc . $ruc . $ambiente . $estab . $ptoEmi . $secuencial . $codNum . $tipoEmision;
        $mod = $this->mod11($base);
        return $base . $mod;
    }

    public function claveAccesoValida(?string $clave): bool
    {
        return $clave !== null
            && preg_match('/^\d{49}$/', $clave)
            && $this->mod11(substr($clave, 0, 48)) === $clave[48];
    }

    private function mod11(string $numero): string
    {
        // Módulo 11 del SRI: factores 2..7 aplicados de derecha a izquierda, en ciclo
        $factores = [2, 3, 4, 5, 6, 7];
        $total = 0;
        $len = strlen($numero);
        $rev = strrev($numero);
        for ($i = 0; $i < $len; $i++) {
            $total += (int) $rev[$i] * $factores[$i % 6];
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
