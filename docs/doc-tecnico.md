# 📋 Documento Técnico — Sistema de Gestión SolarMed Software
**Versión:** 3.0  
**Fecha:** Mayo 2026  
**Cliente:** SolarMed Software — Sistema Médico  
**Elaborado por:** Equipo de Desarrollo  

---

## 1. Resumen Ejecutivo

El presente documento describe el diseño técnico completo del **Sistema de Gestión para la SolarMed Software**, una solución web desarrollada en Laravel 12 orientada a optimizar los procesos operativos diarios de la clínica: gestión de turnos por QR, expedientes clínicos multi-especialidad, agenda de citas, generación de informes médicos en PDF y facturación local.

La clínica opera con **dos especialidades simultáneas**:
- 🏥 **Medicina General** — consultas generales abiertas a todo público
- 👶 **Ginecología y Obstetricia** — a cargo del Sistema Médico, con servicios especializados como ecografías, controles prenatales, partos y cesáreas

El sistema está diseñado para funcionar **completamente en red local (LAN)**, con hasta **5 usuarios simultáneos**, sin dependencia de servicios en la nube externos para su operación principal.

---

## 2. Descripción del Negocio

| Campo | Detalle |
|-------|---------|
| **Nombre** | SolarMed Software |
| **Especialidades** | Medicina General + Ginecología y Obstetricia |
| **Médico especialista** | Sistema Médico (Gineco-Obstetra) |
| **Médicos generales** | Uno o más (registrables en el sistema) |
| **Ubicación** | Ecuador |
| **Contacto** | 044619253 |

### 2.1 Servicios por especialidad

| Especialidad | Servicios |
|-------------|-----------|
| **Medicina General** | Consulta general, control de signos vitales, diagnóstico, receta médica, derivaciones |
| **Ginecología y Obstetricia** | Consulta ginecológica, Ecografía obstétrica ($20), Control prenatal, Planificación familiar, Partos, Cesáreas, Cirugías |

---

## 3. Objetivos del Sistema

### 3.1 Objetivo General
Desarrollar un sistema de gestión clínica integral y **multi-especialidad** que digitalice y optimice todos los procesos operativos de la SolarMed Software, desde la asignación de turnos hasta la facturación, con un expediente clínico que se adapta al tipo de consulta realizada.

### 3.2 Objetivos Específicos
- Eliminar el registro manual en papel de pacientes y consultas
- Implementar un sistema de turnos digitales mediante código QR con selección de especialidad
- Gestionar múltiples médicos desde un único sistema
- Mantener expedientes clínicos diferenciados: **consulta general** y **gineco-obstétrico**
- Generar informes de ecografía en PDF con membrete y firma del médico
- Controlar ingresos diarios y emisión de comprobantes de pago
- Garantizar acceso seguro por roles diferenciados

---

## 4. Stack Tecnológico

```
Backend:       Laravel 12 (PHP 8.3)
Frontend:      Blade + Livewire 3 + Alpine.js
Estilos:       Tailwind CSS
Base datos:    MySQL 8.0
Autenticación: Laravel Breeze
Roles:         Spatie Laravel Permission
PDFs:          barryvdh/laravel-dompdf
QR:            simplesoftwareio/simple-qrcode
Servidor:      Local (Laragon recomendado para Windows)
```

### 4.1 Justificación del Stack
- **Livewire 3** reemplaza completamente la necesidad de Vue.js o React, manteniendo todo en PHP
- **Sin API REST separada** — sistema monolítico apropiado para 5 usuarios simultáneos
- **Sin app móvil** — el formulario QR funciona desde el navegador del celular del paciente
- **Laragon** como servidor local por su facilidad de configuración en Windows

---

## 5. Arquitectura del Sistema

### 5.1 Arquitectura General

```
┌─────────────────────────────────────────────────────────┐
│                    RED LOCAL (LAN)                       │
│                                                          │
│  ┌─────────────┐        ┌──────────────────────────┐    │
│  │   ROUTER    │        │   PC SERVIDOR             │    │
│  │             │◄──────►│   Laravel 12 + MySQL      │    │
│  │ Red Admin   │        │   IP: 192.168.1.100       │    │
│  │ Red Turnos  │        └──────────────────────────┘    │
│  └─────────────┘         ▲        ▲         ▲            │
│         ▲                │        │         │            │
│         │         ┌──────┘   ┌────┘   ┌─────┘           │
│         │         │          │        │                  │
│   ┌─────┴────┐  ┌─┴────────┐ ┌───────┴──┐ ┌─────────┐  │
│   │ CELULAR  │  │    PC    │ │  PC/Tab  │ │ PC/Tab  │  │
│   │Paciente  │  │Secretaria│ │ Dr. Bury │ │ Médico  │  │
│   │(CSM-Turn)│  │          │ │  (Ginec) │ │ General │  │
│   └──────────┘  └──────────┘ └──────────┘ └─────────┘  │
└─────────────────────────────────────────────────────────┘
```

### 5.2 Configuración de Red

| Red WiFi | Nombre | Acceso | Propósito |
|----------|--------|--------|-----------|
| Red 1 | `SantaMartha-Admin` | Contraseña privada | PCs del personal médico y administrativo |
| Red 2 | `CSM-Turnos` | Abierta | Solo pacientes, accede únicamente a la página de turnos |

> ⚠️ La Red 2 debe configurarse en el router para **bloquear todo tráfico externo** y permitir únicamente el acceso a `192.168.1.100` (IP del servidor local).

---

## 6. Módulos del Sistema

### MÓDULO 1 — QR + Sala de Espera (Multi-especialidad)

**Descripción:** Sistema de turnos digitales. El paciente escanea el QR en recepción, elige la especialidad, llena el formulario y obtiene su turno. La secretaria gestiona **dos colas separadas** en tiempo real.

#### Formulario QR (celular del paciente)

```
┌─────────────────────────┐
│   SolarMed Software  │
│                         │
│  Nombre:   __________   │
│  Cédula:   __________   │
│  Teléfono: __________   │
│                         │
│  ¿A qué área viene?     │
│  ○ Medicina General     │
│  ○ Ginecología /        │
│    Obstetricia          │
│                         │
│  Motivo de visita:      │
│  [Medicina General]     │
│  ○ Consulta general     │
│  ○ Control              │
│  ○ Otro                 │
│                         │
│  [Ginecología]          │
│  ○ Consulta ginecológica│
│  ○ Ecografía            │
│  ○ Control prenatal     │
│  ○ Planificación fam.   │
│  ○ Otro                 │
│                         │
│  [OBTENER TURNO]        │
└─────────────────────────┘
```

#### Pantalla de la secretaria (Sala de Espera)

```
┌──────────────────────────────────────────────────────────┐
│  SALA DE ESPERA — Miércoles 27 Mayo 2026                 │
│                                                          │
│  ┌─────────────────────┐  ┌─────────────────────────┐   │
│  │  MEDICINA GENERAL   │  │  GINECOLOGÍA/OBSTETRICIA │   │
│  │                     │  │                          │   │
│  │  EN ATENCIÓN:       │  │  EN ATENCIÓN:            │   │
│  │  #G3 Rosa Torres    │  │  #O2 Ana Pérez           │   │
│  │  Consulta general   │  │  Control prenatal        │   │
│  │                     │  │                          │   │
│  │  EN ESPERA:         │  │  EN ESPERA:              │   │
│  │  #G4 Juan Mora      │  │  #O3 Carmen Loja         │   │
│  │  #G5 Luis Pérez     │  │  #O4 María González      │   │
│  │                     │  │                          │   │
│  │ [LLAMAR #G4]        │  │ [LLAMAR #O3]             │   │
│  └─────────────────────┘  └─────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

#### Numeración de turnos diferenciada

| Especialidad | Prefijo | Ejemplo |
|-------------|---------|---------|
| Medicina General | G | G1, G2, G3... |
| Ginecología / Obstetricia | O | O1, O2, O3... |

> Ambas numeraciones **reinician cada día** desde 1. Esto permite que la secretaria y los médicos identifiquen de inmediato a qué especialidad pertenece cada turno.

#### Estados de turno

| Estado | Descripción | Quién cambia |
|--------|-------------|--------------|
| `esperando` | Paciente registrado, en sala | Automático |
| `en_atencion` | Siendo atendido | Secretaria |
| `completado` | Atención finalizada | Médico |
| `cancelado` | No se presentó | Secretaria |

#### Reglas de negocio
- Solo se muestran turnos del **día actual**
- Cada especialidad tiene su **propia cola independiente**
- Solo puede haber **un turno "en atención" por médico** a la vez
- El formulario QR es **público** (no requiere login)
- La sala de espera requiere **rol secretaria o superior**
- Tiempo estimado = turnos pendientes en esa cola × 10 minutos
- Detección de paciente existente por cédula al momento de registrar el turno

---

### MÓDULO 2 — Médicos y Especialidades

**Descripción:** Gestión de los médicos de la clínica. Cada médico tiene su propia agenda, sus propios pacientes y su propio expediente clínico asociado.

#### Ficha del médico

```
DATOS DEL MÉDICO
├── Nombres y apellidos
├── Número de colegiatura (registro MSP Ecuador)
├── Especialidad (Medicina General / Ginecología / Obstetricia / otra)
├── Teléfono y correo
├── Imagen de firma (para PDFs e informes)
├── Horario de atención
└── Estado: Activo / Inactivo
```

#### Especialidades registradas inicialmente

| Código | Especialidad |
|--------|-------------|
| `general` | Medicina General |
| `ginecologia` | Ginecología y Obstetricia |

> El sistema permite agregar nuevas especialidades desde la configuración sin modificar código.

---

### MÓDULO 3 — Pacientes

**Descripción:** Ficha centralizada de cada paciente. Es la entidad principal del sistema. Un mismo paciente puede tener consultas con el médico general **y** con el Dr. Bury, todo vinculado a su única ficha.

#### Ficha del paciente

```
DATOS PERSONALES (todos los pacientes)
├── Nombres y apellidos completos
├── Cédula de identidad (único)
├── Fecha de nacimiento / Edad calculada
├── Sexo
├── Teléfono principal y secundario
├── Dirección y ciudad
└── Correo electrónico (opcional)

DATOS CLÍNICOS BASE (todos los pacientes)
├── Grupo sanguíneo (A+, A-, B+, B-, AB+, AB-, O+, O-)
├── Alergias conocidas
└── Antecedentes médicos relevantes

DATOS GINECO-OBSTÉTRICOS (solo si aplica — pacientes femeninas)
├── FUM (Fecha de Última Menstruación)
├── Gestas (número de embarazos)
├── Partos (número de partos vaginales)
├── Cesáreas
├── Abortos
└── Método anticonceptivo actual
```

> Los campos gineco-obstétricos solo se muestran y se requieren cuando el sexo del paciente es femenino y accede a servicios de esa especialidad.

---

### MÓDULO 4 — Agenda y Citas (por médico)

**Descripción:** Calendario interactivo con vista por médico o vista general de la clínica.

#### Tipos de cita por especialidad

| Especialidad | Tipo de cita | Duración | Color |
|-------------|-------------|----------|-------|
| **Medicina General** | Consulta general | 20 min | Azul |
| **Medicina General** | Control de seguimiento | 15 min | Celeste |
| **Medicina General** | Procedimiento menor | 30 min | Gris |
| **Ginecología** | Consulta ginecológica | 20 min | Rosa |
| **Ginecología** | Ecografía obstétrica | 30 min | Verde |
| **Ginecología** | Control prenatal | 20 min | Naranja |
| **Ginecología** | Planificación familiar | 15 min | Morado |
| **Ginecología** | Cirugía / Parto | Variable | Rojo |

#### Vistas del calendario
- **Vista general** — todos los médicos en pantalla dividida
- **Vista por médico** — solo las citas del médico seleccionado
- Vistas: día, semana, mes
- Drag & drop para reagendar

---

### MÓDULO 5 — Expediente Clínico (Adaptable por especialidad)

**Descripción:** El expediente clínico es **inteligente**: muestra campos diferentes según la especialidad de la consulta. Un mismo paciente puede tener consultas generales y consultas ginecológicas, todas en su historial cronológico unificado.

#### Vista del historial del paciente

```
HISTORIAL DE [PACIENTE] — ordenado cronológicamente
│
├── 15/05/2026 — Medicina General — Dr. García
│   Motivo: Dolor de cabeza y fiebre
│   Diagnóstico: Faringitis aguda (J02.9)
│   Tratamiento: Amoxicilina 500mg...
│
├── 20/05/2026 — Ginecología — Dr. Bury
│   Motivo: Control prenatal semana 28
│   Peso: 68kg | PA: 110/70 | FCF: 142 lpm
│   Observaciones: Evolución normal...
│
└── 27/05/2026 — Ginecología — Dr. Bury
    Ecografía: 29 semanas + 2 días
    Peso fetal estimado: 1.320g
    [Ver PDF del informe]
```

#### 5.1 Sección: Consulta Médica General

```
EVOLUCIÓN CLÍNICA GENERAL
├── Fecha y hora / Médico tratante
├── Motivo de consulta
├── Anamnesis (síntomas referidos)
├── Examen físico
│   ├── Peso, talla, IMC calculado
│   ├── Presión arterial
│   ├── Temperatura
│   ├── Frecuencia cardíaca y respiratoria
│   └── Saturación de oxígeno (opcional)
├── Diagnóstico (texto libre + código CIE-10)
├── Tratamiento y medicamentos indicados
├── Indicaciones al paciente
└── ¿Requiere derivación? → a qué especialidad
```

#### 5.2 Sección: Consulta Ginecológica

```
EVOLUCIÓN GINECOLÓGICA
├── Fecha y hora / Médico tratante
├── Motivo de consulta
├── Anamnesis ginecológica
├── Examen físico general
│   ├── Peso, talla, presión arterial
│   └── Frecuencia cardíaca
├── Examen ginecológico
├── Diagnóstico (texto libre + código CIE-10)
├── Tratamiento indicado
└── Indicaciones
```

#### 5.3 Sección: Control Prenatal

```
CONTROL PRENATAL
├── Fecha / Semanas de gestación (auto-calculadas desde FUM)
├── FPP (Fecha Probable de Parto)
├── Peso materno / Ganancia de peso
├── Presión arterial
├── Altura uterina (cm)
├── Frecuencia cardíaca fetal (FCF)
├── Presentación fetal
├── Movimientos fetales (sí/no)
├── Edemas (sí/no / localización)
└── Observaciones / próxima cita
```

#### 5.4 Archivos adjuntos (todos los tipos)
- Resultados de laboratorio (PDF, imagen)
- Ecografías externas
- Documentos de referencia / derivación

---

### MÓDULO 6 — Ecografías e Informes PDF

**Descripción:** Módulo exclusivo del Dr. Bury para registro y generación del informe oficial de ecografía obstétrica en PDF.

#### Campos del informe

```
ENCABEZADO
├── Logo SolarMed Software
├── Datos del Sistema Médico (especialidad, colegiatura)
└── Fecha del estudio

DATOS DEL ESTUDIO
├── Datos completos de la paciente
├── Edad gestacional (semanas + días)
├── FUM y FPP
└── Indicación del estudio

HALLAZGOS ECOGRÁFICOS
├── Presentación fetal
├── Latido cardíaco fetal (LCF)
├── Placenta (localización, grado Grannum)
├── Líquido amniótico (ILA)
├── Biometría: DBP, CC, CA, LF
├── Peso fetal estimado (gramos)
└── Observaciones adicionales

CONCLUSIÓN
└── Texto libre del médico

PIE DE PÁGINA
├── Imagen adjunta de la ecografía (si se carga)
└── Firma del Dr. Bury
```

#### Salida
- PDF descargable e imprimible en hoja A4
- Membrete completo de la clínica
- Imagen de ecografía embebida en el informe

---

### MÓDULO 7 — Facturación y Caja

**Descripción:** Registro de cobros por cualquier servicio de la clínica (general o especialidad), emisión de comprobantes y cierre de caja diario.

#### Tarifario configurable por especialidad

| Especialidad | Servicio | Precio base |
|-------------|----------|-------------|
| **Medicina General** | Consulta general | Configurable |
| **Medicina General** | Control seguimiento | Configurable |
| **Ginecología** | Consulta ginecológica | Configurable |
| **Ginecología** | Ecografía obstétrica | $20.00 |
| **Ginecología** | Control prenatal | Configurable |
| **Ginecología** | Planificación familiar | Configurable |
| **Cirugía** | Procedimientos | Configurable |

#### Formas de pago

| Método | Observación |
|--------|-------------|
| Efectivo | Registro inmediato |
| Transferencia bancaria | Registro con número de comprobante |
| Tarjeta de crédito/débito | Registro manual del voucher |

#### Documentos generados
- **Nota de venta** (formato Ecuador, imprimible A4 o media hoja)
- **Resumen de caja del día** (con desglose por especialidad)
- **Reporte de ingresos por médico / por período**

#### Preparación SRI
- Campos de RUC, razón social y dirección incluidos
- Estructura lista para conectar firmador electrónico a futuro
- **No se emite RIDE electrónico en esta versión**

---

### MÓDULO 8 — Configuración y Roles

**Descripción:** Administración de usuarios, permisos, médicos y datos de la clínica.

#### Roles del sistema

| Rol | Descripción | Accesos principales |
|-----|-------------|---------------------|
| **Admin** | Dueño / Dr. Bury | Acceso total. Configuración, reportes financieros, gestión de usuarios |
| **Médico** | Dr. Bury u otro médico | Su propia agenda, expedientes de sus pacientes, ecografías, informes PDF. **No ve finanzas** |
| **Secretaria** | Recepción | Sala de espera, agenda de todos los médicos, registro de pacientes, facturación. **No ve expediente clínico** |
| **Enfermería** *(opcional)* | Asistente | Solo agenda del día y datos básicos del paciente |

> Cada médico **solo ve los expedientes de sus propios pacientes** a menos que tenga rol Admin.

#### Configuración de la clínica

```
├── Nombre, logo e información de la clínica
├── Gestión de médicos (altas, bajas, especialidades)
├── Tarifario de servicios por especialidad
├── Plantilla de encabezado para PDFs
├── Horarios de atención por médico
└── Gestión de usuarios y contraseñas
```

---

## 7. Diseño de Base de Datos

### 7.1 Diagrama de entidades principales

```
medicos ─────────────────────────────────────────┐
    │                                             │
    ▼                                             │
especialidades                                    │
                                                  │
pacientes ──────┬──── turnos ──────── especialidades
     │           │
     │           └──── citas ───────── medicos
     │
     ├──── expediente_consultas ─────── medicos
     │           └── tipo: general / ginecologica
     │
     ├──── controles_prenatales ──────── medicos
     │
     ├──── ecografias ────────────────── medicos
     │
     └──── facturas ──────────────────── factura_items
                  └── medico_id
```

### 7.2 Tablas principales

#### `medicos`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| user_id | FK | Vinculado al usuario del sistema |
| nombres | varchar(100) | |
| apellidos | varchar(100) | |
| especialidad_id | FK | |
| colegiatura | varchar(30) | Número MSP Ecuador |
| telefono | varchar(15) | |
| email | varchar(100) | |
| firma_path | varchar(255) | Imagen de firma para PDFs |
| horario | json | Días y horas de atención |
| activo | boolean | |

#### `especialidades`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| nombre | varchar(100) | Medicina General, Ginecología... |
| codigo | varchar(20) | general, ginecologia... |
| color_agenda | varchar(7) | Hex color para el calendario |
| activo | boolean | |

#### `pacientes`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| nombres | varchar(100) | |
| apellidos | varchar(100) | |
| cedula | varchar(13) UNIQUE | |
| fecha_nacimiento | date | |
| sexo | enum | masculino, femenino |
| telefono | varchar(15) | |
| telefono_secundario | varchar(15) | |
| direccion | text | |
| ciudad | varchar(60) | |
| email | varchar(100) | |
| grupo_sanguineo | enum | A+,A-,B+,B-,AB+,AB-,O+,O- |
| alergias | text | |
| antecedentes | text | |
| fum | date | Solo si aplica |
| gestas | tinyint | Solo si aplica |
| partos | tinyint | Solo si aplica |
| cesareas | tinyint | Solo si aplica |
| abortos | tinyint | Solo si aplica |
| created_at / updated_at | timestamps | |

#### `turnos`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| numero_turno | int | Reinicia cada día por especialidad |
| prefijo | varchar(5) | G (general), O (obstetricia), etc. |
| especialidad_id | FK | |
| medico_id | FK nullable | |
| paciente_id | FK nullable | Null si es paciente nuevo |
| nombre_temporal | varchar(100) | |
| cedula | varchar(13) | |
| telefono | varchar(15) | |
| motivo | varchar(100) | |
| estado | enum | esperando, en_atencion, completado, cancelado |
| fecha | date | |
| hora_registro | timestamp | |
| hora_llamado | timestamp nullable | |
| hora_fin | timestamp nullable | |

#### `citas`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| paciente_id | FK | |
| medico_id | FK | |
| especialidad_id | FK | |
| tipo | varchar(60) | consulta_general, ecografia, control_prenatal... |
| fecha_hora | datetime | |
| duracion_min | int | |
| estado | enum | pendiente, confirmada, completada, cancelada |
| notas | text | |

#### `expediente_consultas`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| paciente_id | FK | |
| medico_id | FK | |
| especialidad_id | FK | |
| tipo_consulta | enum | **general, ginecologica, control_prenatal** |
| cita_id | FK nullable | |
| turno_id | FK nullable | |
| fecha | datetime | |
| motivo_consulta | text | |
| anamnesis | text | |
| examen_fisico | json | Campos variables según especialidad |
| diagnostico | text | |
| codigo_cie10 | varchar(10) | |
| tratamiento | text | |
| indicaciones | text | |
| requiere_derivacion | boolean | |
| derivacion_especialidad | varchar(60) | |

> El campo `examen_fisico` se guarda como JSON porque sus subcampos varían según si es consulta general (temperatura, saturación) o ginecológica (altura uterina, FCF, etc.)

#### `controles_prenatales`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| paciente_id | FK | |
| medico_id | FK | |
| fecha | date | |
| semanas_gestacion | int | Auto-calculado desde FUM |
| fpp | date | |
| peso_materno | decimal(5,2) | kg |
| presion_arterial | varchar(10) | |
| altura_uterina | decimal(4,1) | cm |
| fcf | int | lpm |
| presentacion | varchar(50) | |
| movimientos_fetales | boolean | |
| edemas | varchar(60) | |
| observaciones | text | |

#### `ecografias`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| paciente_id | FK | |
| medico_id | FK | |
| fecha | date | |
| semanas_gestacion | varchar(20) | Ej: "28+3" |
| presentacion | varchar(50) | |
| lcf | int | |
| placenta | varchar(100) | |
| liquido_amniotico | varchar(50) | |
| dbp | decimal(4,1) | mm |
| cc | decimal(5,1) | mm |
| ca | decimal(5,1) | mm |
| lf | decimal(4,1) | mm |
| peso_fetal_estimado | int | gramos |
| conclusion | text | |
| imagen_path | varchar(255) | |
| pdf_path | varchar(255) | |

#### `facturas`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| numero_factura | varchar(20) | Secuencial |
| paciente_id | FK | |
| medico_id | FK | Para reporte por médico |
| especialidad_id | FK | |
| turno_id / cita_id | FK nullable | |
| fecha | datetime | |
| subtotal | decimal(8,2) | |
| descuento | decimal(8,2) | |
| total | decimal(8,2) | |
| forma_pago | enum | efectivo, transferencia, tarjeta |
| referencia_pago | varchar(100) | |
| estado | enum | pagada, anulada |
| user_id | FK | Secretaria que registró |

#### `factura_items`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| factura_id | FK | |
| descripcion | varchar(200) | |
| cantidad | int | |
| precio_unitario | decimal(8,2) | |
| subtotal | decimal(8,2) | |

#### `servicios_tarifario`
| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| especialidad_id | FK | |
| nombre | varchar(150) | |
| descripcion | text | |
| precio | decimal(8,2) | |
| activo | boolean | |

---

## 8. Plan de Desarrollo (Roadmap)

```
FASE 1 — Fundación (Semanas 1-2)
├── Instalación Laravel 12 + Laragon
├── Configuración Tailwind + Livewire 3
├── Autenticación (Laravel Breeze)
├── Roles y permisos (Spatie Permission)
├── Módulo de Especialidades y Médicos
└── Layout base de la aplicación

FASE 2 — QR + Sala de Espera Multi-especialidad (Semanas 3-4)
├── Generación del código QR
├── Formulario público (con selección de especialidad)
├── Lógica de turnos por especialidad (prefijos G / O)
├── Sala de espera en tiempo real con dos colas (Livewire)
└── Estados y flujo de turnos

FASE 3 — Pacientes y Agenda (Semanas 5-6)
├── CRUD completo de pacientes
├── Ficha adaptable (general + gineco-obstétrica)
├── Integración paciente ↔ turno ↔ especialidad
├── Calendario FullCalendar multi-médico
└── Gestión de citas por especialidad

FASE 4 — Expediente Clínico + Archivos (Semanas 7-8)
├── Consulta médica general
├── Consulta ginecológica
├── Controles prenatales
├── Historial cronológico unificado
├── Subida y gestión de archivos adjuntos
└── Vista previa y descarga segura de documentos

FASE 5 — Ecografías + PDF (Semanas 9-10)
├── Formulario de informe ecográfico
├── Carga de imagen de ecografía
├── Generación de PDF con DomPDF
└── Plantilla con membrete y firma del Dr.

FASE 6 — Facturación (Semanas 11-12)
├── Tarifario por especialidad
├── Generación de nota de venta
├── Cierre de caja diario
└── Reportes de ingresos por médico y por período

FASE 7 — Pulido y Entrega (Semana 13)
├── Pruebas con el equipo de la clínica
├── Ajustes de usabilidad
├── Manual de usuario básico
└── Respaldo y documentación final
```

---

## 9. Gestión de Archivos y Documentos

### 9.1 Dónde se permiten subir archivos

| Módulo | Qué se sube | Formatos permitidos | Quién puede subir |
|--------|-------------|---------------------|-------------------|
| **Expediente clínico** | Resultados de laboratorio externos | PDF, JPG, PNG | Médico |
| **Expediente clínico** | Documentos de referencia / derivación | PDF | Médico |
| **Ecografías** | Imagen capturada del ecógrafo | JPG, PNG | Médico |
| **Pacientes** | Foto del paciente *(opcional)* | JPG, PNG | Secretaria / Médico |
| **Configuración** | Logo de la clínica | JPG, PNG | Admin |
| **Configuración** | Firma del médico para PDFs | JPG, PNG | Admin / Médico |

---

### 9.2 Flujo de subida de archivos

```
Usuario selecciona archivo desde su PC o tablet
        │
        ▼
Laravel valida antes de guardar:
├── ¿El formato es permitido? (PDF, JPG, PNG)
├── ¿El tamaño es menor a 5 MB?
├── ¿El tipo real del archivo coincide? (no solo la extensión)
└── ¿El usuario tiene permiso en este módulo?
        │
        ▼
Archivo se guarda en almacenamiento privado del servidor:
storage/app/private/
    └── pacientes/
        └── {id_paciente}/
            ├── laboratorio/      ← resultados de exámenes
            ├── referencias/      ← documentos de derivación
            ├── ecografias/       ← imágenes del ecógrafo
            └── foto/             ← foto del paciente
        │
        ▼
En la base de datos se guarda únicamente la ruta del archivo
(nunca el archivo en sí dentro de la BD)
        │
        ▼
Médico accede al archivo desde el expediente del paciente:
├── Vista previa en pantalla (imágenes y PDFs)
├── Botón de descarga
└── Botón de eliminar (solo admin o quien subió)
```

---

### 9.3 Estructura de carpetas en el servidor

```
storage/
└── app/
    └── private/                     ← NO accesible por URL directa
        ├── pacientes/
        │   ├── 1/                   ← ID del paciente
        │   │   ├── laboratorio/
        │   │   │   ├── hemograma_2026-05-10.pdf
        │   │   │   └── glucosa_2026-05-20.jpg
        │   │   ├── referencias/
        │   │   │   └── derivacion_cardio.pdf
        │   │   ├── ecografias/
        │   │   │   └── eco_28sem_2026-05-27.jpg
        │   │   └── foto/
        │   │       └── perfil.jpg
        │   └── 2/
        │       └── ...
        └── clinica/
            ├── logo.png
            └── firmas/
                └── dr_bury_firma.png
```

> ⚠️ Los archivos **nunca son accesibles por URL directa**. Siempre se sirven a través de Laravel, que primero verifica si el usuario tiene permiso.

---

### 9.4 Tabla de base de datos: `paciente_archivos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint PK | |
| paciente_id | FK | Paciente al que pertenece |
| medico_id | FK | Quién subió el archivo |
| consulta_id | FK nullable | Consulta a la que está vinculado |
| ecografia_id | FK nullable | Ecografía a la que está vinculado |
| categoria | enum | `laboratorio`, `referencia`, `ecografia`, `foto`, `otro` |
| nombre_original | varchar(255) | Nombre del archivo tal como lo subió el usuario |
| nombre_almacenado | varchar(255) | Nombre único generado por el sistema |
| ruta | varchar(500) | Ruta relativa dentro de storage/private |
| mime_type | varchar(100) | Tipo real del archivo (image/jpeg, application/pdf...) |
| tamanio_kb | int | Tamaño en kilobytes |
| descripcion | varchar(255) | Descripción opcional del documento |
| created_at | timestamp | |

---

### 9.5 Límites y restricciones

| Parámetro | Valor |
|-----------|-------|
| Tamaño máximo por archivo | **5 MB** |
| Formatos permitidos | PDF, JPG, JPEG, PNG |
| Número de archivos por paciente | Sin límite |
| Almacenamiento estimado por paciente | ~50 MB promedio |
| Almacenamiento recomendado en servidor | 256 GB SSD (para años de operación) |

---

### 9.6 Seguridad de archivos

| Medida | Detalle |
|--------|---------|
| Carpeta privada | `storage/private` no tiene acceso web directo |
| Descarga controlada | Todos los archivos pasan por un controlador Laravel que verifica permisos |
| Validación de tipo real | Se valida el MIME type real, no solo la extensión del archivo |
| Aislamiento por rol | Secretaria **no puede** ver ni descargar archivos del expediente clínico |
| Nombres únicos | El sistema renombra cada archivo con un UUID para evitar sobreescrituras |
| Archivos del ecógrafo | Se suben manualmente desde USB o PC; no hay integración directa con el equipo en esta versión |

---

### 9.7 Experiencia de uso (interfaz)

```
DENTRO DEL EXPEDIENTE DEL PACIENTE:

┌─────────────────────────────────────────────────┐
│  📎 Documentos adjuntos                          │
│                                                  │
│  [+ Subir documento]                             │
│                                                  │
│  📄 hemograma_mayo.pdf          10/05/2026  🔽 🗑 │
│  🖼  eco_28semanas.jpg           27/05/2026  🔽 🗑 │
│  📄 derivacion_cardio.pdf       15/04/2026  🔽 🗑 │
│                                                  │
│  🔽 = Descargar    🗑 = Eliminar                 │
└─────────────────────────────────────────────────┘
```

---

## 10. Seguridad

| Medida | Implementación |
|--------|---------------|
| Autenticación | Laravel Breeze (sesiones seguras) |
| Autorización | Spatie Permission (roles y permisos por ruta) |
| Aislamiento de expedientes | Cada médico solo ve expedientes de sus pacientes |
| Protección CSRF | Incluida en Laravel por defecto |
| Inyección SQL | Eloquent ORM previene ataques |
| XSS | Blade escapa variables automáticamente |
| Expediente clínico | Solo accesible con rol `medico` o `admin` |
| Formulario QR | Público pero con rate limiting (máx 3 turnos por cédula por día) |
| Archivos clínicos | Servidos por Laravel con verificación de permisos, nunca por URL directa |
| Validación de archivos | MIME type real validado en servidor, no solo extensión |

---

## 11. Lo que NO incluye esta versión (preparado para futuro)

| Funcionalidad | Estado |
|---------------|--------|
| Facturación electrónica SRI (RIDE) | Estructura lista, no activo |
| Notificaciones por WhatsApp/SMS | Fuera del MVP |
| Telemedicina / videoconsulta | Fuera del alcance |
| App móvil nativa | No necesaria (web responsiva) |
| Multi-sede | No aplica actualmente |
| Integración con laboratorio externo | Futura fase |
| Más especialidades médicas | Arquitectura preparada, solo agregar en BD |

---

## 12. Requerimientos de Infraestructura

### Servidor local (PC de la clínica)
| Componente | Mínimo recomendado |
|------------|-------------------|
| Sistema operativo | Windows 10/11 |
| RAM | 8 GB |
| Almacenamiento | 256 GB SSD |
| Software servidor | Laragon (incluye PHP 8.3, MySQL 8, Nginx) |

### Red
| Elemento | Especificación |
|----------|---------------|
| Router | TP-Link, D-Link o similar con soporte multi-SSID |
| Red Admin | WPA2, contraseña segura |
| Red Turnos (CSM-Turnos) | Abierta, con restricción de tráfico solo a IP local |
| IP servidor | Fija (192.168.1.100 recomendada) |

---

## 13. Glosario

| Término | Definición |
|---------|-----------|
| **UUID** | Identificador único universal, usado para nombrar archivos sin repetición |
| **MIME type** | Tipo de archivo según su contenido real (ej: image/jpeg, application/pdf) |
| **FUM** | Fecha de Última Menstruación |
| **FPP** | Fecha Probable de Parto |
| **FCF / LCF** | Frecuencia / Latido Cardíaco Fetal |
| **DBP** | Diámetro Biparietal (medida ecográfica) |
| **ILA** | Índice de Líquido Amniótico |
| **CIE-10** | Clasificación Internacional de Enfermedades, versión 10 |
| **MSP** | Ministerio de Salud Pública (Ecuador) |
| **MVP** | Producto Mínimo Viable |
| **LAN** | Red de Área Local |
| **SRI** | Servicio de Rentas Internas (Ecuador) |
| **RIDE** | Representación Impresa de Documento Electrónico (Ecuador) |
| **Livewire** | Framework de componentes reactivos para Laravel |
| **Multi-especialidad** | Sistema que gestiona más de una especialidad médica simultáneamente |

---

*Documento sujeto a revisión y actualización conforme avance el desarrollo.*  
*Versión 3.0 — SolarMed Software — Sistema Médico*
