<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Retoma comprobantes que quedaron pendientes con el SRI (requiere el cron de schedule:run)
Schedule::command('sri:reintentar')->everyFifteenMinutes()->withoutOverlapping();

// Respaldo diario de la base de datos y los archivos privados
Schedule::command('respaldo:crear')->dailyAt(app(\App\Services\AjustesService::class)->get('tareas.hora_respaldo'))->withoutOverlapping();

// Recordatorio por correo de las citas del día siguiente
Schedule::command('citas:recordatorios')->dailyAt(app(\App\Services\AjustesService::class)->get('tareas.hora_recordatorios'))->withoutOverlapping();

// Integridad del registro de auditoría (AM 5216-A Art. 8: trazabilidad)
Schedule::command('auditoria:verificar')->dailyAt('02:30')->withoutOverlapping();
