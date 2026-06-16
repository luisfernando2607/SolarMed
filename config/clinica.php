<?php
return [
    'nombre'            => env('CLINICA_NOMBRE', 'SolarMed Software'),
    'turno_rate_limit'  => env('TURNO_RATE_LIMIT', 3),
    'max_archivo_kb'    => 5120,
    'mimes_permitidos'  => ['pdf','jpg','jpeg','png'],
    'especialidades_prefijos' => [
        'general'     => 'G',
        'ginecologia' => 'O',
    ],
];
