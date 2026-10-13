<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Números enteros en letras (español), para la cantidad de medicamento de la receta
 * (ACESS-2023-0030 Art. 5 c.v: "cantidad del medicamento en números y letras").
 */
class NumeroALetras
{
    private const UNIDADES = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
        'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];

    private const DECENAS = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];

    private const CENTENAS = [1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos', 5 => 'quinientos',
        6 => 'seiscientos', 7 => 'setecientos', 8 => 'ochocientos', 9 => 'novecientos'];

    public static function convertir(int $n): string
    {
        if ($n < 0 || $n > 999999) {
            throw new InvalidArgumentException('Solo se convierten enteros entre 0 y 999 999.');
        }
        if ($n < 1000) {
            return self::hastaMil($n);
        }

        $miles = intdiv($n, 1000);
        $resto = $n % 1000;
        $texto = $miles === 1 ? 'mil' : self::apocopar(self::hastaMil($miles)) . ' mil';

        return $resto ? "{$texto} " . self::hastaMil($resto) : $texto;
    }

    /** "Cantidad: 30 (treinta)" */
    public static function conNumero(int $n): string
    {
        return $n . ' (' . self::convertir($n) . ')';
    }

    private static function hastaMil(int $n): string
    {
        if ($n < 30) {
            return self::UNIDADES[$n];
        }
        if ($n < 100) {
            $u = $n % 10;
            return self::DECENAS[intdiv($n, 10)] . ($u ? ' y ' . self::UNIDADES[$u] : '');
        }
        if ($n === 100) {
            return 'cien';
        }
        $resto = $n % 100;

        return self::CENTENAS[intdiv($n, 100)] . ($resto ? ' ' . self::hastaMil($resto) : '');
    }

    /** Delante de "mil": "veintiuno mil" → "veintiún mil", "uno" → "un" */
    private static function apocopar(string $texto): string
    {
        return preg_replace(['/veintiuno$/', '/uno$/'], ['veintiún', 'un'], $texto);
    }
}
