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
