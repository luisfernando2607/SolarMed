<?php

namespace App\Jobs;

use App\Services\EstadoSistemaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/** Lo encola el programador cada 5 minutos: si nadie lo procesa, el trabajador de la cola está detenido */
class LatidoCola implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Cache::forever(EstadoSistemaService::LATIDO_COLA, now()->toIso8601String());
    }
}
