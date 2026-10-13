<?php

namespace App\Services;

/**
 * Identidad de la clínica: nombre, logo, contacto y color. Se edita en Configuración > Clínica
 * y se usa en el login, el menú, el formulario público, los PDF y los correos.
 */
class MarcaService
{
    private const LOGO_DEFECTO = 'images/SolarMed.jpg';
    private const LOGO_BN_DEFECTO = 'images/SolarMed-bn.jpg';

    public function nombre(): string
    {
        return (string) ($this->ajuste('marca.nombre') ?: 'Clínica');
    }

    public function subtitulo(): string
    {
        return (string) $this->ajuste('marca.subtitulo');
    }

    /** Bloque A de los formularios MSP: institución, unicódigo, tipología y ubicación del establecimiento */
    public function establecimiento(): array
    {
        $datos = ['nombre' => $this->nombre()];
        foreach (['institucion', 'unicodigo', 'tipologia', 'provincia', 'canton', 'parroquia', 'distrito'] as $campo) {
            $datos[$campo] = (string) $this->ajuste("establecimiento.{$campo}");
        }

        return $datos;
    }

    public function direccion(): string
    {
        return (string) $this->ajuste('marca.direccion');
    }

    public function telefono(): string
    {
        return (string) $this->ajuste('marca.telefono');
    }

    public function email(): string
    {
        return (string) $this->ajuste('marca.email');
    }

    public function color(): string
    {
        $color = (string) $this->ajuste('marca.color');
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#0F6E56';
    }

    /** URL del logo para las páginas */
    public function logoUrl(): string
    {
        return asset($this->logoRelativo('marca.logo', self::LOGO_DEFECTO));
    }

    /** Ruta del logo en disco, para los PDF */
    public function logoRuta(): string
    {
        return public_path($this->logoRelativo('marca.logo', self::LOGO_DEFECTO));
    }

    /** Logo en blanco y negro (tickets térmicos) */
    public function logoBnRuta(): string
    {
        return public_path($this->logoRelativo('marca.logo_bn', self::LOGO_BN_DEFECTO));
    }

    /** Contacto en una línea: "Dirección · Tel: … · correo" */
    public function contacto(): string
    {
        return collect([
            $this->direccion(),
            $this->telefono() ? 'Tel: ' . $this->telefono() : null,
            $this->email(),
        ])->filter()->implode(' · ');
    }

    /**
     * Escala de tonos (50–950) del color principal como variables CSS "R G B", para Tailwind.
     * El color elegido es el tono 600; los claros se mezclan con blanco y los oscuros con negro.
     */
    public function paletaCss(): string
    {
        [$r, $g, $b] = sscanf($this->color(), '#%02x%02x%02x');
        $mezclas = [50 => 0.95, 100 => 0.88, 200 => 0.75, 300 => 0.58, 400 => 0.36, 500 => 0.16, 600 => 0.0,
                    700 => -0.15, 800 => -0.30, 900 => -0.45, 950 => -0.62];

        return collect($mezclas)->map(function ($f, $tono) use ($r, $g, $b) {
            $canal = fn ($c) => (int) round($f >= 0 ? $c + (255 - $c) * $f : $c * (1 + $f));
            return "--marca-{$tono}: {$canal($r)} {$canal($g)} {$canal($b)};";
        })->implode(' ');
    }

    /** Contraste del texto blanco sobre el color principal (WCAG) */
    public function contrasteConBlanco(): float
    {
        $lin = function (int $c) { $c /= 255; return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4; };
        [$r, $g, $b] = sscanf($this->color(), '#%02x%02x%02x');
        $l = 0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b);
        return round(1.05 / ($l + 0.05), 2);
    }

    private function logoRelativo(string $clave, string $defecto): string
    {
        $ruta = $this->ajuste($clave);
        return ($ruta && is_file(public_path($ruta))) ? $ruta : $defecto;
    }

    private function ajuste(string $clave): mixed
    {
        return app(AjustesService::class)->get($clave);
    }
}
