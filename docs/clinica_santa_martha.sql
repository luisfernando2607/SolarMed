-- ============================================================
--  BASE DE DATOS — SOLARMED
--  Sistema de Gestión Multi-especialidad v3.0
--  Laravel 12 + MySQL 8.0
--  Elaborado según documento técnico — Mayo 2026
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE DATABASE IF NOT EXISTS clinica_santa_martha
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE clinica_santa_martha;


-- ============================================================
--  MÓDULO 0 — AUTENTICACIÓN (compatible con Laravel Breeze)
-- ============================================================

CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)        NOT NULL,
    email           VARCHAR(150)        NOT NULL UNIQUE,
    email_verified_at TIMESTAMP         NULL,
    password        VARCHAR(255)        NOT NULL,
    remember_token  VARCHAR(100)        NULL,
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tablas de Spatie Laravel Permission
CREATE TABLE permissions (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(125)        NOT NULL,
    guard_name      VARCHAR(125)        NOT NULL DEFAULT 'web',
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL,
    UNIQUE KEY permissions_name_guard_unique (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(125)        NOT NULL,
    guard_name      VARCHAR(125)        NOT NULL DEFAULT 'web',
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL,
    UNIQUE KEY roles_name_guard_unique (name, guard_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE model_has_roles (
    role_id         BIGINT UNSIGNED     NOT NULL,
    model_type      VARCHAR(125)        NOT NULL,
    model_id        BIGINT UNSIGNED     NOT NULL,
    PRIMARY KEY (role_id, model_id, model_type),
    CONSTRAINT fk_mhr_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE model_has_permissions (
    permission_id   BIGINT UNSIGNED     NOT NULL,
    model_type      VARCHAR(125)        NOT NULL,
    model_id        BIGINT UNSIGNED     NOT NULL,
    PRIMARY KEY (permission_id, model_id, model_type),
    CONSTRAINT fk_mhp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_has_permissions (
    permission_id   BIGINT UNSIGNED     NOT NULL,
    role_id         BIGINT UNSIGNED     NOT NULL,
    PRIMARY KEY (permission_id, role_id),
    CONSTRAINT fk_rhp_permission FOREIGN KEY (permission_id) REFERENCES permissions (id) ON DELETE CASCADE,
    CONSTRAINT fk_rhp_role       FOREIGN KEY (role_id)       REFERENCES roles (id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 2 — ESPECIALIDADES
-- ============================================================

CREATE TABLE especialidades (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)        NOT NULL COMMENT 'Ej: Medicina General, Ginecología y Obstetricia',
    codigo          VARCHAR(20)         NOT NULL UNIQUE COMMENT 'Ej: general, ginecologia',
    color_agenda    VARCHAR(7)          NOT NULL DEFAULT '#3B82F6' COMMENT 'Color HEX para el calendario',
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 2 — MÉDICOS
-- ============================================================

CREATE TABLE medicos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED     NOT NULL,
    nombres         VARCHAR(100)        NOT NULL,
    apellidos       VARCHAR(100)        NOT NULL,
    especialidad_id BIGINT UNSIGNED     NOT NULL,
    colegiatura     VARCHAR(30)         NULL COMMENT 'Número de registro MSP Ecuador',
    telefono        VARCHAR(15)         NULL,
    email           VARCHAR(100)        NULL,
    firma_path      VARCHAR(255)        NULL COMMENT 'Ruta a la imagen de firma para PDFs',
    horario         JSON                NULL COMMENT 'Días y horas de atención por día',
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL,

    CONSTRAINT fk_medicos_user         FOREIGN KEY (user_id)         REFERENCES users         (id) ON DELETE RESTRICT,
    CONSTRAINT fk_medicos_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 3 — PACIENTES
-- ============================================================

CREATE TABLE pacientes (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres                 VARCHAR(100)    NOT NULL,
    apellidos               VARCHAR(100)    NOT NULL,
    cedula                  VARCHAR(13)     NOT NULL UNIQUE COMMENT 'Cédula ecuatoriana (10 dígitos) o pasaporte',
    fecha_nacimiento        DATE            NULL,
    sexo                    ENUM('masculino','femenino','otro') NOT NULL,
    telefono                VARCHAR(15)     NULL,
    telefono_secundario     VARCHAR(15)     NULL,
    direccion               TEXT            NULL,
    ciudad                  VARCHAR(60)     NULL,
    email                   VARCHAR(100)    NULL,
    foto_path               VARCHAR(255)    NULL,

    -- Datos clínicos base
    grupo_sanguineo         ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-') NULL,
    alergias                TEXT            NULL,
    antecedentes            TEXT            NULL,

    -- Datos gineco-obstétricos (solo aplica a pacientes femeninas)
    fum                     DATE            NULL COMMENT 'Fecha de Última Menstruación',
    gestas                  TINYINT UNSIGNED NULL COMMENT 'Número de embarazos',
    partos                  TINYINT UNSIGNED NULL COMMENT 'Número de partos vaginales',
    cesareas                TINYINT UNSIGNED NULL,
    abortos                 TINYINT UNSIGNED NULL,
    metodo_anticonceptivo   VARCHAR(100)    NULL,

    created_at              TIMESTAMP       NULL,
    updated_at              TIMESTAMP       NULL,

    INDEX idx_pacientes_cedula (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 1 — TURNOS (QR + Sala de Espera)
-- ============================================================

CREATE TABLE turnos (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_turno        SMALLINT UNSIGNED   NOT NULL COMMENT 'Reinicia en 1 cada día por especialidad',
    prefijo             VARCHAR(5)          NOT NULL COMMENT 'G = General, O = Obstetricia',
    especialidad_id     BIGINT UNSIGNED     NOT NULL,
    medico_id           BIGINT UNSIGNED     NULL COMMENT 'Puede asignarse después de tomar el turno',
    paciente_id         BIGINT UNSIGNED     NULL COMMENT 'NULL si el paciente no está registrado aún',

    -- Datos capturados en el formulario QR (paciente nuevo o no registrado)
    nombre_temporal     VARCHAR(100)        NULL,
    cedula              VARCHAR(13)         NOT NULL,
    telefono            VARCHAR(15)         NULL,
    motivo              VARCHAR(100)        NULL,

    estado              ENUM('esperando','en_atencion','completado','cancelado') NOT NULL DEFAULT 'esperando',
    fecha               DATE                NOT NULL COMMENT 'Fecha del turno',
    hora_registro       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hora_llamado        TIMESTAMP           NULL,
    hora_fin            TIMESTAMP           NULL,

    created_at          TIMESTAMP           NULL,
    updated_at          TIMESTAMP           NULL,

    UNIQUE KEY uq_turno_dia (especialidad_id, fecha, numero_turno),
    INDEX idx_turnos_fecha (fecha),
    INDEX idx_turnos_estado (estado),

    CONSTRAINT fk_turnos_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades (id) ON DELETE RESTRICT,
    CONSTRAINT fk_turnos_medico       FOREIGN KEY (medico_id)       REFERENCES medicos         (id) ON DELETE SET NULL,
    CONSTRAINT fk_turnos_paciente     FOREIGN KEY (paciente_id)     REFERENCES pacientes        (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 4 — AGENDA / CITAS
-- ============================================================

CREATE TABLE citas (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id     BIGINT UNSIGNED     NOT NULL,
    medico_id       BIGINT UNSIGNED     NOT NULL,
    especialidad_id BIGINT UNSIGNED     NOT NULL,
    tipo            VARCHAR(60)         NOT NULL COMMENT 'Ej: consulta_general, ecografia, control_prenatal, cirugia',
    fecha_hora      DATETIME            NOT NULL,
    duracion_min    SMALLINT UNSIGNED   NOT NULL DEFAULT 20,
    estado          ENUM('pendiente','confirmada','completada','cancelada') NOT NULL DEFAULT 'pendiente',
    color           VARCHAR(7)          NULL COMMENT 'Color en el calendario (hereda de especialidad si NULL)',
    notas           TEXT                NULL,
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL,

    INDEX idx_citas_fecha (fecha_hora),
    INDEX idx_citas_medico (medico_id),

    CONSTRAINT fk_citas_paciente     FOREIGN KEY (paciente_id)     REFERENCES pacientes     (id) ON DELETE RESTRICT,
    CONSTRAINT fk_citas_medico       FOREIGN KEY (medico_id)       REFERENCES medicos        (id) ON DELETE RESTRICT,
    CONSTRAINT fk_citas_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 5 — EXPEDIENTE CLÍNICO
-- ============================================================

-- 5A. Consultas médicas (General + Ginecológica)
CREATE TABLE expediente_consultas (
    id                          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id                 BIGINT UNSIGNED NOT NULL,
    medico_id                   BIGINT UNSIGNED NOT NULL,
    especialidad_id             BIGINT UNSIGNED NOT NULL,
    tipo_consulta               ENUM('general','ginecologica','control_prenatal') NOT NULL,
    cita_id                     BIGINT UNSIGNED NULL,
    turno_id                    BIGINT UNSIGNED NULL,
    fecha                       DATETIME        NOT NULL,

    -- Anamnesis
    motivo_consulta             TEXT            NULL,
    anamnesis                   TEXT            NULL,

    -- Examen físico (JSON porque los campos varían por especialidad)
    -- General incluye: peso, talla, imc, presion_arterial, temperatura, fc, fr, saturacion
    -- Ginecológico incluye: peso, talla, presion_arterial, fc, examen_ginecologico
    examen_fisico               JSON            NULL,

    -- Diagnóstico y tratamiento
    diagnostico                 TEXT            NULL,
    codigo_cie10                VARCHAR(10)     NULL COMMENT 'Código CIE-10, ej: J02.9',
    tratamiento                 TEXT            NULL,
    indicaciones                TEXT            NULL,

    -- Derivación
    requiere_derivacion         TINYINT(1)      NOT NULL DEFAULT 0,
    derivacion_especialidad     VARCHAR(60)     NULL,

    created_at                  TIMESTAMP       NULL,
    updated_at                  TIMESTAMP       NULL,

    INDEX idx_exp_paciente (paciente_id),
    INDEX idx_exp_fecha    (fecha),

    CONSTRAINT fk_exp_paciente     FOREIGN KEY (paciente_id)     REFERENCES pacientes          (id) ON DELETE RESTRICT,
    CONSTRAINT fk_exp_medico       FOREIGN KEY (medico_id)       REFERENCES medicos             (id) ON DELETE RESTRICT,
    CONSTRAINT fk_exp_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades      (id) ON DELETE RESTRICT,
    CONSTRAINT fk_exp_cita         FOREIGN KEY (cita_id)         REFERENCES citas               (id) ON DELETE SET NULL,
    CONSTRAINT fk_exp_turno        FOREIGN KEY (turno_id)        REFERENCES turnos              (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5B. Controles prenatales (tabla separada por la riqueza de campos)
CREATE TABLE controles_prenatales (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id         BIGINT UNSIGNED     NOT NULL,
    medico_id           BIGINT UNSIGNED     NOT NULL,
    consulta_id         BIGINT UNSIGNED     NULL COMMENT 'Consulta del expediente a la que está vinculado',
    fecha               DATE                NOT NULL,
    semanas_gestacion   TINYINT UNSIGNED    NULL COMMENT 'Calculado automáticamente desde FUM',
    fpp                 DATE                NULL COMMENT 'Fecha Probable de Parto',
    peso_materno        DECIMAL(5,2)        NULL COMMENT 'kg',
    presion_arterial    VARCHAR(10)         NULL COMMENT 'Ej: 120/80',
    altura_uterina      DECIMAL(4,1)        NULL COMMENT 'cm',
    fcf                 SMALLINT UNSIGNED   NULL COMMENT 'Frecuencia Cardíaca Fetal en lpm',
    presentacion        VARCHAR(50)         NULL COMMENT 'Ej: cefálica, podálica',
    movimientos_fetales TINYINT(1)          NULL,
    edemas              VARCHAR(60)         NULL,
    observaciones       TEXT                NULL,
    created_at          TIMESTAMP           NULL,
    updated_at          TIMESTAMP           NULL,

    INDEX idx_cp_paciente (paciente_id),

    CONSTRAINT fk_cp_paciente  FOREIGN KEY (paciente_id) REFERENCES pacientes           (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cp_medico    FOREIGN KEY (medico_id)   REFERENCES medicos              (id) ON DELETE RESTRICT,
    CONSTRAINT fk_cp_consulta  FOREIGN KEY (consulta_id) REFERENCES expediente_consultas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 6 — ECOGRAFÍAS
-- ============================================================

CREATE TABLE ecografias (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id             BIGINT UNSIGNED     NOT NULL,
    medico_id               BIGINT UNSIGNED     NOT NULL,
    consulta_id             BIGINT UNSIGNED     NULL,
    fecha                   DATE                NOT NULL,

    -- Datos del estudio
    indicacion              VARCHAR(255)        NULL COMMENT 'Motivo del estudio ecográfico',
    semanas_gestacion       VARCHAR(20)         NULL COMMENT 'Formato: semanas+días, ej: 28+3',
    fum                     DATE                NULL,
    fpp                     DATE                NULL,

    -- Hallazgos ecográficos
    presentacion            VARCHAR(50)         NULL,
    lcf                     SMALLINT UNSIGNED   NULL COMMENT 'Latido Cardíaco Fetal en lpm',
    placenta                VARCHAR(100)        NULL COMMENT 'Localización y grado Grannum',
    liquido_amniotico       VARCHAR(50)         NULL COMMENT 'Descripción del ILA',

    -- Biometría fetal (en mm)
    dbp                     DECIMAL(4,1)        NULL COMMENT 'Diámetro Biparietal mm',
    cc                      DECIMAL(5,1)        NULL COMMENT 'Circunferencia Cefálica mm',
    ca                      DECIMAL(5,1)        NULL COMMENT 'Circunferencia Abdominal mm',
    lf                      DECIMAL(4,1)        NULL COMMENT 'Longitud del Fémur mm',
    peso_fetal_estimado     SMALLINT UNSIGNED   NULL COMMENT 'Gramos',

    -- Conclusión e imágenes
    conclusion              TEXT                NULL,
    imagen_path             VARCHAR(255)        NULL COMMENT 'Ruta de la imagen del ecógrafo',
    pdf_path                VARCHAR(255)        NULL COMMENT 'Ruta del PDF generado',

    created_at              TIMESTAMP           NULL,
    updated_at              TIMESTAMP           NULL,

    INDEX idx_eco_paciente (paciente_id),
    INDEX idx_eco_fecha    (fecha),

    CONSTRAINT fk_eco_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes           (id) ON DELETE RESTRICT,
    CONSTRAINT fk_eco_medico   FOREIGN KEY (medico_id)   REFERENCES medicos              (id) ON DELETE RESTRICT,
    CONSTRAINT fk_eco_consulta FOREIGN KEY (consulta_id) REFERENCES expediente_consultas (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 7 — FACTURACIÓN Y CAJA
-- ============================================================

-- Tarifario de servicios por especialidad
CREATE TABLE servicios_tarifario (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    especialidad_id BIGINT UNSIGNED     NOT NULL,
    nombre          VARCHAR(150)        NOT NULL COMMENT 'Ej: Ecografía obstétrica',
    descripcion     TEXT                NULL,
    precio          DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
    activo          TINYINT(1)          NOT NULL DEFAULT 1,
    created_at      TIMESTAMP           NULL,
    updated_at      TIMESTAMP           NULL,

    CONSTRAINT fk_st_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Facturas / Notas de venta
CREATE TABLE facturas (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_factura      VARCHAR(20)         NOT NULL UNIQUE COMMENT 'Secuencial: NV-000001',
    paciente_id         BIGINT UNSIGNED     NOT NULL,
    medico_id           BIGINT UNSIGNED     NOT NULL COMMENT 'Para reportes por médico',
    especialidad_id     BIGINT UNSIGNED     NOT NULL,
    turno_id            BIGINT UNSIGNED     NULL,
    cita_id             BIGINT UNSIGNED     NULL,
    user_id             BIGINT UNSIGNED     NOT NULL COMMENT 'Secretaria que registró el cobro',
    fecha               DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal            DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
    descuento           DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
    total               DECIMAL(8,2)        NOT NULL DEFAULT 0.00,
    forma_pago          ENUM('efectivo','transferencia','tarjeta') NOT NULL DEFAULT 'efectivo',
    referencia_pago     VARCHAR(100)        NULL COMMENT 'Número de comprobante de transferencia o voucher',
    estado              ENUM('pagada','anulada') NOT NULL DEFAULT 'pagada',
    observaciones       TEXT                NULL,
    created_at          TIMESTAMP           NULL,
    updated_at          TIMESTAMP           NULL,

    INDEX idx_facturas_fecha     (fecha),
    INDEX idx_facturas_paciente  (paciente_id),
    INDEX idx_facturas_medico    (medico_id),

    CONSTRAINT fk_fact_paciente     FOREIGN KEY (paciente_id)     REFERENCES pacientes     (id) ON DELETE RESTRICT,
    CONSTRAINT fk_fact_medico       FOREIGN KEY (medico_id)       REFERENCES medicos        (id) ON DELETE RESTRICT,
    CONSTRAINT fk_fact_especialidad FOREIGN KEY (especialidad_id) REFERENCES especialidades (id) ON DELETE RESTRICT,
    CONSTRAINT fk_fact_turno        FOREIGN KEY (turno_id)        REFERENCES turnos         (id) ON DELETE SET NULL,
    CONSTRAINT fk_fact_cita         FOREIGN KEY (cita_id)         REFERENCES citas          (id) ON DELETE SET NULL,
    CONSTRAINT fk_fact_user         FOREIGN KEY (user_id)         REFERENCES users          (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ítems de factura (detalle de servicios cobrados)
CREATE TABLE factura_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factura_id          BIGINT UNSIGNED     NOT NULL,
    servicio_id         BIGINT UNSIGNED     NULL COMMENT 'Referencia al tarifario (puede ser NULL si es descripción libre)',
    descripcion         VARCHAR(200)        NOT NULL,
    cantidad            SMALLINT UNSIGNED   NOT NULL DEFAULT 1,
    precio_unitario     DECIMAL(8,2)        NOT NULL,
    subtotal            DECIMAL(8,2)        NOT NULL,
    created_at          TIMESTAMP           NULL,
    updated_at          TIMESTAMP           NULL,

    CONSTRAINT fk_fi_factura  FOREIGN KEY (factura_id) REFERENCES facturas           (id) ON DELETE CASCADE,
    CONSTRAINT fk_fi_servicio FOREIGN KEY (servicio_id) REFERENCES servicios_tarifario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 9 — ARCHIVOS Y DOCUMENTOS ADJUNTOS
-- ============================================================

CREATE TABLE paciente_archivos (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id         BIGINT UNSIGNED     NOT NULL,
    medico_id           BIGINT UNSIGNED     NOT NULL COMMENT 'Usuario que subió el archivo',
    consulta_id         BIGINT UNSIGNED     NULL,
    ecografia_id        BIGINT UNSIGNED     NULL,
    categoria           ENUM('laboratorio','referencia','ecografia','foto','otro') NOT NULL,
    nombre_original     VARCHAR(255)        NOT NULL COMMENT 'Nombre del archivo tal como lo subió el usuario',
    nombre_almacenado   VARCHAR(255)        NOT NULL COMMENT 'Nombre UUID generado por el sistema',
    ruta                VARCHAR(500)        NOT NULL COMMENT 'Ruta relativa dentro de storage/private',
    mime_type           VARCHAR(100)        NULL COMMENT 'Tipo MIME real: image/jpeg, application/pdf...',
    tamanio_kb          INT UNSIGNED        NULL COMMENT 'Tamaño en kilobytes',
    descripcion         VARCHAR(255)        NULL COMMENT 'Descripción opcional del documento',
    created_at          TIMESTAMP           NULL,
    updated_at          TIMESTAMP           NULL,

    INDEX idx_pa_paciente (paciente_id),
    INDEX idx_pa_categoria (categoria),

    CONSTRAINT fk_pa_paciente  FOREIGN KEY (paciente_id) REFERENCES pacientes           (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pa_medico    FOREIGN KEY (medico_id)   REFERENCES medicos              (id) ON DELETE RESTRICT,
    CONSTRAINT fk_pa_consulta  FOREIGN KEY (consulta_id) REFERENCES expediente_consultas (id) ON DELETE SET NULL,
    CONSTRAINT fk_pa_ecografia FOREIGN KEY (ecografia_id) REFERENCES ecografias          (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  MÓDULO 8 — CONFIGURACIÓN DE LA CLÍNICA
-- ============================================================

CREATE TABLE configuracion (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clave           VARCHAR(100)    NOT NULL UNIQUE COMMENT 'Ej: clinica_nombre, clinica_logo, sri_ruc',
    valor           TEXT            NULL,
    descripcion     VARCHAR(255)    NULL,
    created_at      TIMESTAMP       NULL,
    updated_at      TIMESTAMP       NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones de Laravel
CREATE TABLE sessions (
    id              VARCHAR(255)    PRIMARY KEY,
    user_id         BIGINT UNSIGNED NULL,
    ip_address      VARCHAR(45)     NULL,
    user_agent      TEXT            NULL,
    payload         LONGTEXT        NOT NULL,
    last_activity   INT             NOT NULL,
    INDEX idx_sessions_user       (user_id),
    INDEX idx_sessions_activity   (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de cache de Laravel
CREATE TABLE cache (
    `key`           VARCHAR(255)    PRIMARY KEY,
    value           MEDIUMTEXT      NOT NULL,
    expiration      INT             NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
    `key`           VARCHAR(255)    PRIMARY KEY,
    owner           VARCHAR(255)    NOT NULL,
    expiration      INT             NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jobs de Laravel (para colas de tareas como generación de PDFs)
CREATE TABLE jobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue           VARCHAR(255)    NOT NULL,
    payload         LONGTEXT        NOT NULL,
    attempts        TINYINT UNSIGNED NOT NULL DEFAULT 0,
    reserved_at     INT UNSIGNED    NULL,
    available_at    INT UNSIGNED    NOT NULL,
    created_at      INT UNSIGNED    NOT NULL,
    INDEX idx_jobs_queue (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE failed_jobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid            VARCHAR(255)    NOT NULL UNIQUE,
    connection      TEXT            NOT NULL,
    queue           TEXT            NOT NULL,
    payload         LONGTEXT        NOT NULL,
    exception       LONGTEXT        NOT NULL,
    failed_at       TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de personal_access_tokens (Laravel Sanctum, por si se agrega API en el futuro)
CREATE TABLE personal_access_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type  VARCHAR(255)    NOT NULL,
    tokenable_id    BIGINT UNSIGNED NOT NULL,
    name            VARCHAR(255)    NOT NULL,
    token           VARCHAR(64)     NOT NULL UNIQUE,
    abilities       TEXT            NULL,
    last_used_at    TIMESTAMP       NULL,
    expires_at      TIMESTAMP       NULL,
    created_at      TIMESTAMP       NULL,
    updated_at      TIMESTAMP       NULL,
    INDEX idx_pat_tokenable (tokenable_type, tokenable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  DATOS INICIALES (Seeders de referencia)
-- ============================================================

-- Roles del sistema
INSERT INTO roles (name, guard_name, created_at, updated_at) VALUES
('admin',      'web', NOW(), NOW()),
('medico',     'web', NOW(), NOW()),
('secretaria', 'web', NOW(), NOW()),
('enfermeria', 'web', NOW(), NOW());

-- Especialidades iniciales
INSERT INTO especialidades (nombre, codigo, color_agenda, activo) VALUES
('Medicina General',          'general',    '#3B82F6', 1),  -- Azul
('Ginecología y Obstetricia', 'ginecologia','#EC4899', 1);  -- Rosa

-- Configuración base de la clínica
INSERT INTO configuracion (clave, valor, descripcion) VALUES
('clinica_nombre',      'Clínica',         'Nombre de la clínica'),
('clinica_telefono',    '044619253',                    'Teléfono principal'),
('clinica_ciudad',      'Ecuador',                      'Ciudad/país'),
('clinica_logo',        NULL,                           'Ruta al logo (storage/private/clinica/logo.png)'),
('sri_ruc',             NULL,                           'RUC para facturación'),
('sri_razon_social',    NULL,                           'Razón social para facturación'),
('sri_direccion',       NULL,                           'Dirección fiscal'),
('factura_secuencial',  '1',                            'Último número de nota de venta emitido'),
('turno_rate_limit',    '3',                            'Máximo de turnos por cédula por día');

-- Tarifario inicial
INSERT INTO servicios_tarifario (especialidad_id, nombre, precio, activo) VALUES
(1, 'Consulta general',          0.00, 1),
(1, 'Control de seguimiento',    0.00, 1),
(1, 'Procedimiento menor',       0.00, 1),
(2, 'Consulta ginecológica',     0.00, 1),
(2, 'Ecografía obstétrica',     20.00, 1),
(2, 'Control prenatal',          0.00, 1),
(2, 'Planificación familiar',    0.00, 1),
(2, 'Cirugía / Parto',           0.00, 1);


SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  FIN DEL SCRIPT
--  SOLARMED — v3.0 — Mayo 2026
-- ============================================================
