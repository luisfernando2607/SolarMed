<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Orden: primero clave propia y acuerdo de confidencialidad, luego verificación en dos pasos
        $middleware->appendToGroup('web', \App\Http\Middleware\ExigirRequisitosCuenta::class);
        $middleware->appendToGroup('web', \App\Http\Middleware\ExigirDosFactores::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
