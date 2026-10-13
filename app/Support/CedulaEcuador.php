<?php

namespace App\Support;

/**
 * Cédula de identidad ecuatoriana: 10 dígitos, provincia 01–24 (o 30 para ecuatorianos en el exterior),
 * tercer dígito menor a 6 y dígito verificador por módulo 10 (coeficientes 2,1,2,1,…).
 */
class CedulaEcuador
{
    public static function valida(?string $cedula): bool
    {
        $cedula = (string) $cedula;
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if (!(($provincia >= 1 && $provincia <= 24) || $provincia === 30) || (int) $cedula[2] >= 6) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $producto = (int) $cedula[$i] * ($i % 2 === 0 ? 2 : 1);
            $suma += $producto > 9 ? $producto - 9 : $producto;
        }

        return (10 - $suma % 10) % 10 === (int) $cedula[9];
    }
}
