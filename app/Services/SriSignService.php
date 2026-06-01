<?php
namespace App\Services;

use App\Models\Medico;
use Illuminate\Support\Facades\Storage;
use DOMDocument;

class SriSignService {

    /**
     * Firma el XML usando el certificado .p12 del médico.
     * Retorna el XML firmado como string.
     */
    public function firmar(string $xml, Medico $medico): string
    {
        $p12Path = $medico->p12_path;
        $password = $this->decryptPassword($medico->p12_password_encrypted);

        if (!$p12Path || !Storage::disk('private')->exists($p12Path)) {
            throw new \RuntimeException('Certificado digital no encontrado para el médico ' . $medico->nombre_completo);
        }

        $p12Content = Storage::disk('private')->get($p12Path);

        if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new \RuntimeException('Error al leer el certificado .p12. Verifique la contraseña.');
        }

        $privateKey = openssl_pkey_get_private($certs['pkey'] ?? '');
        if (!$privateKey) {
            throw new \RuntimeException('No se pudo extraer la clave privada del certificado.');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);
        $root = $dom->documentElement;

        $canonical = $root->C14N(true, false);
        $signatureValue = '';
        openssl_sign($canonical, $signatureValue, $privateKey, OPENSSL_ALGO_SHA1);
        $signatureB64 = base64_encode($signatureValue);

        $certB64 = base64_encode($certs['cert'] ?? '');

        $signatureXml = $this->buildSignatureXml($dom, $signatureB64, $certB64, $root->getAttribute('id') ?: 'comprobante');

        $root->appendChild($dom->importNode($signatureXml->documentElement, true));

        return $dom->saveXML();
    }

    private function buildSignatureXml(DOMDocument $dom, string $signatureB64, string $certB64, string $referenceId): DOMDocument
    {
        $sig = new DOMDocument('1.0', 'UTF-8');
        $sig->loadXML('<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
            <SignedInfo>
                <CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
                <SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
                <Reference URI="#' . $referenceId . '">
                    <Transforms>
                        <Transform Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
                    </Transforms>
                    <DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
                    <DigestValue></DigestValue>
                </Reference>
            </SignedInfo>
            <SignatureValue>' . $signatureB64 . '</SignatureValue>
            <KeyInfo>
                <X509Data>
                    <X509Certificate>' . $certB64 . '</X509Certificate>
                </X509Data>
            </KeyInfo>
        </Signature>');

        $signedInfo = $sig->getElementsByTagName('SignedInfo')->item(0);
        if ($signedInfo) {
            $canonSignedInfo = $signedInfo->C14N(true, false);
            $digestValue = base64_encode(sha1($canonSignedInfo, true));
            $digestValueEl = $sig->getElementsByTagName('DigestValue')->item(0);
            if ($digestValueEl) {
                $digestValueEl->nodeValue = $digestValue;
            }
        }

        return $sig;
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
