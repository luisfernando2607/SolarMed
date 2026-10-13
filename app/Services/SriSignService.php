<?php
namespace App\Services;

use App\Models\Medico;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Firma electrónica XAdES-BES (enveloped, RSA-SHA1) según la ficha técnica de comprobantes electrónicos del SRI.
 *
 * Estructura generada dentro del elemento raíz del comprobante:
 *   ds:Signature
 *     ds:SignedInfo         → referencias a SignedProperties, KeyInfo (certificado) y #comprobante
 *     ds:SignatureValue     → RSA-SHA1 sobre SignedInfo canonicalizado
 *     ds:KeyInfo            → certificado X.509 y clave pública
 *     ds:Object             → etsi:QualifyingProperties / SignedProperties (hora de firma, certificado firmante)
 *
 * Todos los digests se calculan con C14N inclusivo sobre los nodos ya insertados en el documento, para que
 * coincidan con lo que calcula el verificador del SRI (incluyendo los namespaces heredados de ds:Signature).
 */
class SriSignService {

    private const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';
    private const NS_ETSI = 'http://uri.etsi.org/01903/v1.3.2#';
    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const SHA1 = 'http://www.w3.org/2000/09/xmldsig#sha1';

    /**
     * Firma el XML usando el certificado .p12 del médico.
     * Retorna el XML firmado como string.
     */
    public function firmar(string $xml, Medico $medico): string
    {
        $p12Path = $medico->p12_path;

        if (!$p12Path || !Storage::disk('private')->exists($p12Path)) {
            throw new \RuntimeException('Certificado digital no encontrado para el médico ' . $medico->nombre_completo);
        }

        $p12Content = Storage::disk('private')->get($p12Path);
        $password = $this->decryptPassword($medico->p12_password_encrypted);

        return $this->firmarConP12($xml, $p12Content, $password);
    }

    public function firmarConP12(string $xml, string $p12Content, string $password): string
    {
        $certs = self::leerP12($p12Content, $password);
        if (!$certs) {
            throw new \RuntimeException('Error al leer el certificado .p12. Verifique la contraseña.');
        }

        $privateKey = openssl_pkey_get_private($certs['pkey'] ?? '');
        $certPem = $certs['cert'] ?? '';
        $certInfo = $certPem ? openssl_x509_parse($certPem) : false;
        if (!$privateKey || !$certInfo) {
            throw new \RuntimeException('No se pudo extraer la clave privada o el certificado del archivo .p12.');
        }

        if (($certInfo['validTo_time_t'] ?? 0) < time()) {
            throw new \RuntimeException('El certificado digital está caducado (venció el ' . date('d/m/Y', $certInfo['validTo_time_t']) . ').');
        }

        $certDer = $this->pemToDer($certPem);
        $keyDetails = openssl_pkey_get_details($privateKey);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        if (!$dom->loadXML($xml)) {
            throw new \RuntimeException('El XML a firmar no es válido.');
        }
        $root = $dom->documentElement;
        $comprobanteId = $root->getAttribute('id') ?: 'comprobante';

        // Digest del comprobante antes de insertar la firma (equivale a aplicar la transformación enveloped-signature)
        $digestComprobante = $this->digest($root->C14N(false, false));

        $n = (string) random_int(100000, 999999);
        $ids = [
            'signature'        => "Signature{$n}",
            'signedInfo'       => "Signature-SignedInfo{$n}",
            'signedPropsRef'   => "SignedPropertiesID{$n}",
            'signedProps'      => "Signature{$n}-SignedProperties{$n}",
            'certificate'      => "Certificate{$n}",
            'comprobanteRef'   => "Reference-ID-{$n}",
            'signatureValue'   => "SignatureValue{$n}",
            'object'           => "Signature{$n}-Object{$n}",
        ];

        $signature = $this->ds($dom, 'Signature');
        $signature->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:etsi', self::NS_ETSI);
        $signature->setAttribute('Id', $ids['signature']);
        $root->appendChild($signature);

        // SignedInfo (los DigestValue se completan después)
        $signedInfo = $this->ds($dom, 'SignedInfo');
        $signedInfo->setAttribute('Id', $ids['signedInfo']);
        $signature->appendChild($signedInfo);
        $signedInfo->appendChild($this->ds($dom, 'CanonicalizationMethod'))->setAttribute('Algorithm', self::C14N);
        $signedInfo->appendChild($this->ds($dom, 'SignatureMethod'))->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

        $refSignedProps = $this->reference($dom, $signedInfo, '#' . $ids['signedProps']);
        $refSignedProps->setAttribute('Id', $ids['signedPropsRef']);
        $refSignedProps->setAttribute('Type', 'http://uri.etsi.org/01903#SignedProperties');

        $refCertificate = $this->reference($dom, $signedInfo, '#' . $ids['certificate']);

        $refComprobante = $this->reference($dom, $signedInfo, '#' . $comprobanteId, enveloped: true);
        $refComprobante->setAttribute('Id', $ids['comprobanteRef']);

        // SignatureValue (se completa al final)
        $signatureValue = $this->ds($dom, 'SignatureValue');
        $signatureValue->setAttribute('Id', $ids['signatureValue']);
        $signature->appendChild($signatureValue);

        // KeyInfo
        $keyInfo = $this->ds($dom, 'KeyInfo');
        $keyInfo->setAttribute('Id', $ids['certificate']);
        $signature->appendChild($keyInfo);
        $x509Data = $keyInfo->appendChild($this->ds($dom, 'X509Data'));
        $x509Data->appendChild($this->ds($dom, 'X509Certificate', chunk_split(base64_encode($certDer), 76, "\n")));
        $rsaKeyValue = $keyInfo->appendChild($this->ds($dom, 'KeyValue'))->appendChild($this->ds($dom, 'RSAKeyValue'));
        $rsaKeyValue->appendChild($this->ds($dom, 'Modulus', chunk_split(base64_encode($keyDetails['rsa']['n'] ?? ''), 76, "\n")));
        $rsaKeyValue->appendChild($this->ds($dom, 'Exponent', base64_encode($keyDetails['rsa']['e'] ?? '')));

        // Object / QualifyingProperties / SignedProperties
        $object = $this->ds($dom, 'Object');
        $object->setAttribute('Id', $ids['object']);
        $signature->appendChild($object);
        $qualifying = $object->appendChild($this->etsi($dom, 'QualifyingProperties'));
        $qualifying->setAttribute('Target', '#' . $ids['signature']);
        $signedProps = $qualifying->appendChild($this->etsi($dom, 'SignedProperties'));
        $signedProps->setAttribute('Id', $ids['signedProps']);

        $signedSigProps = $signedProps->appendChild($this->etsi($dom, 'SignedSignatureProperties'));
        $signedSigProps->appendChild($this->etsi($dom, 'SigningTime', now('America/Guayaquil')->format('Y-m-d\TH:i:sP')));
        $cert = $signedSigProps->appendChild($this->etsi($dom, 'SigningCertificate'))->appendChild($this->etsi($dom, 'Cert'));
        $certDigest = $cert->appendChild($this->etsi($dom, 'CertDigest'));
        $certDigest->appendChild($this->ds($dom, 'DigestMethod'))->setAttribute('Algorithm', self::SHA1);
        $certDigest->appendChild($this->ds($dom, 'DigestValue', $this->digest($certDer)));
        $issuerSerial = $cert->appendChild($this->etsi($dom, 'IssuerSerial'));
        $issuerSerial->appendChild($this->ds($dom, 'X509IssuerName', $this->issuerName($certInfo['issuer'] ?? [])));
        $issuerSerial->appendChild($this->ds($dom, 'X509SerialNumber', $this->serialDecimal($certInfo)));

        $dataObjectFormat = $signedProps
            ->appendChild($this->etsi($dom, 'SignedDataObjectProperties'))
            ->appendChild($this->etsi($dom, 'DataObjectFormat'));
        $dataObjectFormat->setAttribute('ObjectReference', '#' . $ids['comprobanteRef']);
        $dataObjectFormat->appendChild($this->etsi($dom, 'Description', 'contenido comprobante'));
        $dataObjectFormat->appendChild($this->etsi($dom, 'MimeType', 'text/xml'));

        // Digests de las referencias, calculados sobre los nodos ya ubicados en el documento
        $this->setDigest($refSignedProps, $this->digest($signedProps->C14N(false, false)));
        $this->setDigest($refCertificate, $this->digest($keyInfo->C14N(false, false)));
        $this->setDigest($refComprobante, $digestComprobante);

        // Firma de SignedInfo
        if (!openssl_sign($signedInfo->C14N(false, false), $rawSignature, $privateKey, OPENSSL_ALGO_SHA1)) {
            throw new \RuntimeException('No se pudo firmar el comprobante con la clave privada.');
        }
        $signatureValue->appendChild($dom->createTextNode(chunk_split(base64_encode($rawSignature), 76, "\n")));

        return $dom->saveXML();
    }

    /**
     * Lee un .p12 y devuelve ['cert' => PEM, 'pkey' => PEM], o null si la contraseña o el archivo no son válidos.
     * Muchos certificados emitidos en Ecuador usan cifrado RC2 (legacy) que OpenSSL 3 no abre por defecto;
     * en ese caso se convierte con el binario openssl usando -legacy.
     */
    public static function leerP12(string $p12Content, string $password): ?array
    {
        if (openssl_pkcs12_read($p12Content, $certs, $password)) {
            return $certs;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'p12');
        try {
            file_put_contents($tmp, $p12Content);
            $process = new Process(
                ['openssl', 'pkcs12', '-legacy', '-in', $tmp, '-nodes', '-passin', 'env:SRI_P12_PASS'],
                null,
                ['SRI_P12_PASS' => $password]
            );
            $process->run();
            if (!$process->isSuccessful()) {
                return null;
            }
            $pem = $process->getOutput();
        } finally {
            @unlink($tmp);
        }

        preg_match('/-----BEGIN (?:RSA |ENCRYPTED )?PRIVATE KEY-----.+?-----END (?:RSA |ENCRYPTED )?PRIVATE KEY-----/s', $pem, $key);
        preg_match_all('/-----BEGIN CERTIFICATE-----.+?-----END CERTIFICATE-----/s', $pem, $certificados);
        if (!$key) {
            return null;
        }

        // El .p12 puede traer la cadena de la entidad certificadora: se usa el certificado que corresponde a la clave
        foreach ($certificados[0] as $cert) {
            if (openssl_x509_check_private_key($cert, $key[0])) {
                return ['cert' => $cert, 'pkey' => $key[0]];
            }
        }

        return null;
    }

    private function ds(DOMDocument $dom, string $name, ?string $text = null): DOMElement
    {
        $el = $dom->createElementNS(self::NS_DS, "ds:{$name}");
        if ($text !== null) {
            $el->appendChild($dom->createTextNode($text));
        }
        return $el;
    }

    private function etsi(DOMDocument $dom, string $name, ?string $text = null): DOMElement
    {
        $el = $dom->createElementNS(self::NS_ETSI, "etsi:{$name}");
        if ($text !== null) {
            $el->appendChild($dom->createTextNode($text));
        }
        return $el;
    }

    private function reference(DOMDocument $dom, DOMElement $signedInfo, string $uri, bool $enveloped = false): DOMElement
    {
        $ref = $signedInfo->appendChild($this->ds($dom, 'Reference'));
        $ref->setAttribute('URI', $uri);
        if ($enveloped) {
            $ref->appendChild($this->ds($dom, 'Transforms'))
                ->appendChild($this->ds($dom, 'Transform'))
                ->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
        }
        $ref->appendChild($this->ds($dom, 'DigestMethod'))->setAttribute('Algorithm', self::SHA1);
        $ref->appendChild($this->ds($dom, 'DigestValue'));
        return $ref;
    }

    private function setDigest(DOMElement $reference, string $digest): void
    {
        $reference->getElementsByTagNameNS(self::NS_DS, 'DigestValue')->item(0)->nodeValue = $digest;
    }

    private function digest(string $data): string
    {
        return base64_encode(sha1($data, true));
    }

    private function pemToDer(string $pem): string
    {
        $b64 = preg_replace('/-----(BEGIN|END) CERTIFICATE-----|\s+/', '', $pem);
        return base64_decode($b64);
    }

    /** Nombre del emisor en formato RFC 2253 (del atributo más específico al más general). */
    private function issuerName(array $issuer): string
    {
        $partes = [];
        foreach (array_reverse($issuer, true) as $clave => $valor) {
            foreach ((array) $valor as $v) {
                $partes[] = $clave . '=' . addcslashes($v, ',+"\\<>;');
            }
        }
        return implode(',', $partes);
    }

    private function serialDecimal(array $certInfo): string
    {
        $hex = $certInfo['serialNumberHex'] ?? null;
        if (!$hex) {
            return (string) ($certInfo['serialNumber'] ?? '0');
        }
        $dec = '0';
        foreach (str_split(strtoupper($hex)) as $c) {
            $dec = bcadd(bcmul($dec, '16'), (string) hexdec($c));
        }
        return $dec;
    }

    /** Fecha de vencimiento del certificado .p12 del médico, o null si no tiene o no se puede leer */
    public function vencimientoCertificado(Medico $medico): ?\Carbon\Carbon
    {
        if (!$medico->p12_path || !Storage::disk('private')->exists($medico->p12_path)) {
            return null;
        }
        try {
            $certs = self::leerP12(Storage::disk('private')->get($medico->p12_path), $this->decryptPassword($medico->p12_password_encrypted));
            $info = !empty($certs['cert']) ? openssl_x509_parse($certs['cert']) : false;

            return $info && isset($info['validTo_time_t']) ? \Carbon\Carbon::createFromTimestamp($info['validTo_time_t'], config('app.timezone')) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function decryptPassword(?string $encrypted): string
    {
        if (!$encrypted) return '';
        return decrypt($encrypted);
    }

    public static function encryptPassword(string $password): string
    {
        return encrypt($password);
    }
}
