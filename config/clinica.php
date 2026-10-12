<?php
return [
    'turno_rate_limit'  => env('TURNO_RATE_LIMIT', 3),
    // Tamaño máximo de los archivos de pacientes (KB). También limitado por upload_max_filesize de PHP
    // y por el límite de subida temporal de Livewire (12 MB por defecto).
    'max_archivo_kb'    => (int) env('ARCHIVO_MAX_KB', 5120),
    // Roles obligados a usar verificación en dos pasos, separados por coma (p. ej. "admin,medico")
    'dos_factores_roles' => array_values(array_filter(array_map('trim', explode(',', (string) env('TWO_FACTOR_ROLES', ''))))),
    /*
     * Exámenes del control prenatal por trimestre (checklist). Ajustar según la guía clínica que siga
     * la consulta (p. ej. GPC "Control prenatal" del MSP). Las claves no deben cambiar una vez usadas,
     * porque el checklist de cada embarazo guarda qué claves se marcaron.
     */
    'checklist_prenatal' => [
        1 => [
            'biometria_t1' => 'Biometría hemática',
            'grupo_rh' => 'Grupo sanguíneo y factor Rh',
            'glucosa_ayunas' => 'Glucosa en ayunas',
            'vih_t1' => 'Prueba de VIH',
            'sifilis_t1' => 'Prueba de sífilis (VDRL/rápida)',
            'hepatitis_b' => 'Hepatitis B (HBsAg)',
            'emo_urocultivo_t1' => 'EMO y urocultivo',
            'eco_11_14' => 'Ecografía 11–14 semanas',
        ],
        2 => [
            'eco_morfologica' => 'Ecografía morfológica 20–24 semanas',
            'ptog' => 'Tolerancia oral a la glucosa 24–28 semanas',
            'vacuna_dt' => 'Vacuna dT / dTpa',
        ],
        3 => [
            'biometria_t3' => 'Biometría hemática de control',
            'vih_sifilis_t3' => 'VIH y sífilis de control',
            'emo_urocultivo_t3' => 'EMO y urocultivo de control',
            'estreptococo_b' => 'Cultivo estreptococo grupo B 35–37 semanas',
            'eco_t3' => 'Ecografía de tercer trimestre',
        ],
    ],

];
