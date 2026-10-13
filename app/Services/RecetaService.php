<?php

namespace App\Services;

use App\Models\Receta;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Emisión de recetas con numeración secuencial única del establecimiento (ACESS-2023-0030 Art. 5 a.i).
 */
class RecetaService
{
    /**
     * @param  array  $datos  columnas de la receta (sin número)
     * @param  array  $items  medicamentos: dci, forma_farmaceutica, concentracion, via, cantidad, dosis, frecuencia, duracion, indicacion, antimicrobiano
     */
    public function emitir(array $datos, array $items): Receta
    {
        // Un número a la vez: dos médicos que guardan al mismo tiempo no reciben el mismo
        return Cache::lock('recetas-numeracion', 10)->block(5, fn () => DB::transaction(function () use ($datos, $items) {
            $numero = (int) Receta::max('numero') + 1;

            $receta = Receta::create($datos + [
                'numero' => $numero,
                'vigencia_dias' => Receta::VIGENCIA_CONSULTA_EXTERNA,
                // Copia en texto para las pantallas que listan recetas antiguas y nuevas por igual
                'medicamentos' => array_map(fn ($i) => [
                    'medicamento' => trim("{$i['dci']} {$i['concentracion']}"),
                    'presentacion' => $i['forma_farmaceutica'],
                    'dosis' => $i['dosis'], 'frecuencia' => $i['frecuencia'], 'duracion' => $i['duracion'],
                    'indicaciones' => $i['indicacion'] ?? null,
                ], $items),
            ]);

            foreach (array_values($items) as $orden => $item) {
                $receta->items()->create($item + ['orden' => $orden]);
            }

            return $receta;
        }));
    }
}
