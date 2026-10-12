<?php

namespace Tests\Feature\Clinica;

use App\Models\Factura;
use App\Models\FacturaItem;
use App\Models\Paciente;
use App\Services\SriConfigService;
use App\Services\SriSignService;
use App\Services\SriWebService;
use App\Services\SriXmlService;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SriTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(SriConfigService::class, new class extends SriConfigService {
            private array $c = ['sri_ruc' => '1790011674001', 'sri_razon_social' => 'Clínica & Pruebas', 'sri_direccion' => 'Quito'];
            public function get(string $k, mixed $d = null): mixed { return $this->c[$k] ?? $d; }
        });
    }

    private function factura(array $atributos = [], ?string $cedula = '1710034065'): Factura
    {
        $factura = new class extends Factura {
            public function update(array $a = [], array $o = []) { $this->fill($a); return true; }
        };
        $factura->forceFill(array_merge([
            'id' => 1, 'numero_factura' => '001-002-000000123', 'ambiente_sri' => '1',
            'fecha' => '2026-10-12', 'descuento' => 5, 'forma_pago' => 'efectivo',
        ], $atributos));
        $factura->setRelation('paciente', new Paciente(['nombres' => 'Ana', 'apellidos' => 'Pérez', 'cedula' => $cedula]));
        $factura->setRelation('medico', null);
        $factura->setRelation('items', collect([
            new FacturaItem(['servicio_id' => 1, 'descripcion' => 'Consulta', 'cantidad' => 1, 'precio_unitario' => 30, 'subtotal' => 30]),
            new FacturaItem(['servicio_id' => 2, 'descripcion' => 'Ecografía', 'cantidad' => 1, 'precio_unitario' => 20, 'subtotal' => 20]),
        ]));
        return $factura;
    }

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $xp->registerNamespace('etsi', 'http://uri.etsi.org/01903/v1.3.2#');
        return $xp;
    }

    public function test_digito_verificador_modulo_11_del_ejemplo_oficial_del_sri(): void
    {
        $this->assertTrue(app(SriXmlService::class)->claveAccesoValida('2110201101179214673900110020010000000011234567813'));
        $this->assertFalse(app(SriXmlService::class)->claveAccesoValida('2110201101179214673900110020010000000011234567818'));
    }

    public function test_xml_reparte_descuento_y_cuadra_totales(): void
    {
        $r = app(SriXmlService::class)->generar($this->factura());
        $xp = $this->xpath($r['xml']);

        $this->assertSame('45.00', $xp->evaluate('string(//infoFactura/totalSinImpuestos)'));
        $this->assertSame('5.00', $xp->evaluate('string(//infoFactura/totalDescuento)'));
        $this->assertSame('45.00', $xp->evaluate('string(//infoFactura/importeTotal)'));
        $this->assertEquals(5.0, $xp->evaluate('sum(//detalle/descuento)'));
        $this->assertEquals(45.0, $xp->evaluate('sum(//detalle/precioTotalSinImpuesto)'));
        $this->assertSame('002', $xp->evaluate('string(//infoTributaria/ptoEmi)'));
        $this->assertSame('000000123', $xp->evaluate('string(//infoTributaria/secuencial)'));
        $this->assertSame('12/10/2026', $xp->evaluate('string(//infoFactura/fechaEmision)'));
        $this->assertSame('Clínica & Pruebas', $xp->evaluate('string(//infoTributaria/razonSocial)'));
        $this->assertTrue(app(SriXmlService::class)->claveAccesoValida($r['clave_acceso']));
        $this->assertStringStartsWith('12102026' . '01' . '1790011674001' . '1' . '001' . '002' . '000000123', $r['clave_acceso']);
    }

    public function test_reutiliza_la_clave_de_acceso_existente_si_es_valida(): void
    {
        $clave = '2110201101179214673900110020010000000011234567813';
        $this->assertSame($clave, app(SriXmlService::class)->generar($this->factura(['clave_acceso' => $clave]))['clave_acceso']);

        $invalida = '2110201101179214673900110020010000000011234567818';
        $this->assertNotSame($invalida, app(SriXmlService::class)->generar($this->factura(['clave_acceso' => $invalida]))['clave_acceso']);
    }

    public function test_tipo_de_identificacion_del_comprador(): void
    {
        $tipo = fn (?string $cedula) => $this->xpath(app(SriXmlService::class)->generar($this->factura([], $cedula))['xml'])
            ->evaluate('string(//tipoIdentificacionComprador)');

        $this->assertSame('05', $tipo('1710034065'));
        $this->assertSame('04', $tipo('1710034065001'));
        $this->assertSame('06', $tipo('AB123456'));
        $this->assertSame('07', $tipo(null));
    }

    public function test_firma_xades_bes_es_criptograficamente_valida(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $cert = openssl_csr_sign(openssl_csr_new(['commonName' => 'Prueba', 'countryName' => 'EC'], $key), null, $key, 30);
        openssl_pkcs12_export($cert, $p12, $key, 'clave');

        $xml = app(SriXmlService::class)->generar($this->factura())['xml'];
        $firmado = app(SriSignService::class)->firmarConP12($xml, $p12, 'clave');

        $dom = new DOMDocument();
        $dom->loadXML($firmado);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $this->assertSame(3, $xp->query('//ds:SignedInfo/ds:Reference')->length);

        // Cada referencia apunta a un nodo cuyo digest SHA-1 coincide
        foreach ($xp->query('//ds:SignedInfo/ds:Reference') as $ref) {
            $id = ltrim($ref->getAttribute('URI'), '#');
            $nodo = $xp->query("//*[@Id='{$id}' or @id='{$id}']")->item(0);
            $this->assertNotNull($nodo, "Referencia {$id} sin nodo");

            if ($nodo === $dom->documentElement) {
                $copia = new DOMDocument();
                $copia->loadXML($firmado);
                $sig = $copia->getElementsByTagNameNS('http://www.w3.org/2000/09/xmldsig#', 'Signature')->item(0);
                $sig->parentNode->removeChild($sig);
                $c14n = $copia->documentElement->C14N(false, false);
            } else {
                $c14n = $nodo->C14N(false, false);
            }

            $this->assertSame(
                base64_encode(sha1($c14n, true)),
                $xp->evaluate('string(ds:DigestValue)', $ref),
                "Digest incorrecto para {$id}"
            );
        }

        // La firma RSA-SHA1 sobre SignedInfo verifica con la clave pública del certificado
        $signedInfo = $xp->query('//ds:SignedInfo')->item(0)->C14N(false, false);
        $firma = base64_decode(preg_replace('/\s+/', '', $xp->evaluate('string(//ds:SignatureValue)')));
        $this->assertSame(1, openssl_verify($signedInfo, $firma, $cert, OPENSSL_ALGO_SHA1));
    }

    public function test_envio_recibido_y_devuelto(): void
    {
        Storage::fake('private');
        Storage::disk('private')->put('sri/firmado.xml', '<factura/>');

        Http::fakeSequence()
            ->push($this->soapRecepcion('RECIBIDA'))
            ->push($this->soapRecepcion('DEVUELTA', '<mensajes><mensaje><identificador>35</identificador><mensaje>ARCHIVO NO CUMPLE ESTRUCTURA XML</mensaje><tipo>ERROR</tipo></mensaje></mensajes>'))
            ->push($this->soapRecepcion('DEVUELTA', '<mensajes><mensaje><identificador>43</identificador><mensaje>CLAVE ACCESO REGISTRADA</mensaje><tipo>ERROR</tipo></mensaje></mensajes>'));

        $factura = $this->factura(['xml_firmado_path' => 'sri/firmado.xml']);
        $svc = app(SriWebService::class);

        $this->assertSame('RECIBIDA', $svc->enviar($factura)['estado']);
        $this->assertSame('enviado', $factura->estado_sri);

        $r = $svc->enviar($factura);
        $this->assertSame('DEVUELTA', $r['estado']);
        $this->assertSame('rechazado', $factura->estado_sri);
        $this->assertSame('35', $factura->mensajes_sri[0]['identificador']);

        // Si el SRI ya tenía la clave registrada, el comprobante se considera recibido
        $this->assertSame('RECIBIDA', $svc->enviar($factura)['estado']);
        $this->assertSame('enviado', $factura->estado_sri);
    }

    public function test_autorizacion_guarda_numero_y_xml(): void
    {
        Storage::fake('private');
        Http::fake(['*' => Http::response(
            '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . '<ns2:autorizacionComprobanteResponse xmlns:ns2="http://ec.gob.sri.ws.autorizacion"><RespuestaAutorizacionComprobante>'
            . '<claveAccesoConsultada>2110201101179214673900110020010000000011234567813</claveAccesoConsultada><numeroComprobantes>1</numeroComprobantes>'
            . '<autorizaciones><autorizacion><estado>AUTORIZADO</estado><numeroAutorizacion>2110201101179214673900110020010000000011234567813</numeroAutorizacion>'
            . '<fechaAutorizacion>2026-10-12T10:00:00-05:00</fechaAutorizacion><ambiente>PRUEBAS</ambiente><comprobante><![CDATA[<factura/>]]></comprobante><mensajes/></autorizacion></autorizaciones>'
            . '</RespuestaAutorizacionComprobante></ns2:autorizacionComprobanteResponse></soap:Body></soap:Envelope>'
        )]);

        $factura = $this->factura(['clave_acceso' => '2110201101179214673900110020010000000011234567813']);
        $r = app(SriWebService::class)->autorizar($factura);

        $this->assertSame('AUTORIZADO', $r['estado']);
        $this->assertSame('autorizado', $factura->estado_sri);
        $this->assertSame('2110201101179214673900110020010000000011234567813', $factura->numero_autorizacion);
        Storage::disk('private')->assertExists($factura->xml_autorizado_path);
        $this->assertStringContainsString('<![CDATA[<factura/>]]>', Storage::disk('private')->get($factura->xml_autorizado_path));
    }

    private function soapRecepcion(string $estado, string $mensajes = ''): string
    {
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body>'
            . '<ns2:validarComprobanteResponse xmlns:ns2="http://ec.gob.sri.ws.recepcion"><RespuestaRecepcionComprobante>'
            . "<estado>{$estado}</estado><comprobantes><comprobante><claveAcceso>x</claveAcceso>{$mensajes}</comprobante></comprobantes>"
            . '</RespuestaRecepcionComprobante></ns2:validarComprobanteResponse></soap:Body></soap:Envelope>';
    }
}
