<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/** Verificación en dos pasos con códigos TOTP (Google Authenticator, Authy, Microsoft Authenticator). */
class DosFactoresService
{
    public function __construct(private Google2FA $google2fa = new Google2FA())
    {
    }

    public function generarSecreto(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /** QR en SVG para escanear con la app autenticadora */
    public function qrSvg(User $user, string $secreto): string
    {
        $uri = $this->google2fa->getQRCodeUrl(config('app.name'), $user->email, $secreto);
        return (string) QrCode::format('svg')->size(200)->margin(1)->generate($uri);
    }

    /** Acepta el código del período actual y del anterior (tolerancia de reloj ~30 s) */
    public function verificarCodigo(string $secreto, string $codigo): bool
    {
        $codigo = preg_replace('/\D/', '', $codigo);
        return strlen($codigo) === 6 && $this->google2fa->verifyKey($secreto, $codigo, 1);
    }

    /** @return string[] */
    public function generarCodigosRecuperacion(): array
    {
        return collect(range(1, 8))->map(fn () => Str::lower(Str::random(5) . '-' . Str::random(5)))->all();
    }

    /** Valida un código de recuperación y lo consume (cada uno sirve una sola vez) */
    public function usarCodigoRecuperacion(User $user, string $codigo): bool
    {
        $codigo = Str::lower(trim($codigo));
        $codigos = $user->two_factor_recovery_codes ?? [];

        foreach ($codigos as $i => $guardado) {
            if (hash_equals($guardado, $codigo)) {
                unset($codigos[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codigos)])->save();
                return true;
            }
        }

        return false;
    }
}
