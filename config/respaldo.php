<?php

return [
    // Carpeta destino. Idealmente un disco externo o una carpeta sincronizada fuera del servidor.
    'ruta' => env('BACKUP_PATH') ?: storage_path('app/respaldos'),

    // Días que se conservan los respaldos
    'dias_retencion' => (int) (env('BACKUP_DIAS') ?: 30),

    // Si se define, el respaldo se cifra con AES-256 (los respaldos contienen datos de salud).
    // Guardar esta contraseña en un lugar seguro: sin ella el respaldo no se puede restaurar.
    'password' => env('BACKUP_PASSWORD') ?: null,
];
