<?php
return [
    'nombre'            => env('CLINICA_NOMBRE', 'SolarMed Software'),
    'turno_rate_limit'  => env('TURNO_RATE_LIMIT', 3),
    'max_archivo_kb'    => 5120,
    'mimes_permitidos'  => ['pdf','jpg','jpeg','png'],
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

    'especialidades_prefijos' => [
        'general'     => 'G',
        'ginecologia' => 'O',
    ],
];
