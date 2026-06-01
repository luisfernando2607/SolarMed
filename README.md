<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# SolarMed - Clínica Santa Martha

Sistema de gestión clínica integral para **Clínica Santa Martha**, una consulta privada de Ginecología y Medicina General en Ecuador. Desarrollado con Laravel 12 y Livewire 3.

## Funcionalidades

- **Módulo de turnos** — Sistema de colas en tiempo real con auto-registro del paciente (kiosco virtual), numeración secuencial por especialidad y seguimiento de estados (esperando, en atención, completado, cancelado).
- **Atención médica** — Panel del doctor para gestionar pacientes desde la sala de espera, crear consultas clínicas, diagnósticos CIE-10, solicitar ecografías y recetar medicamentos.
- **Historia clínica (Expedientes)** — Registro completo de consultas del paciente con búsqueda, anamnesis, examen físico, diagnóstico, tratamiento y referencias.
- **Módulo de control prenatal** — Seguimiento especializado de embarazos con semanas de gestación, FPP, peso materno, presión arterial, altura uterina, latidos fetales y checklists.
- **Ecografías** — Reportes detallados con mediciones biométricas estándar (DBP, CC, CA, LF), peso fetal estimado y generación de PDF.
- **Agendamiento de citas** — Calendario interactivo FullCalendar con citas por especialidad codificadas por color.
- **Facturación electrónica SRI** — Facturación completa con integración SRI: generación de XML, firma digital con certificado .p12, envío vía SOAP, numeración autorizada y PDF en formato ticket (226x400pts).
- **Gestión de archivos** — Carga y categorización de documentos del paciente (resultados de laboratorio, imágenes, referencias, fotos).
- **Dashboard administrativo** — Estadísticas con gráficos (pacientes por sexo/edad/ciudad, tendencias de turnos, facturación mensual, mapa de calor de atención, diagnósticos más frecuentes).
- **Control de usuarios y permisos** — RBAC completo con roles (admin, secretaria, médico) y permisos granulares vía Spatie.

## Tecnologías

| Componente | Tecnología |
|---|---|
| Backend | PHP ^8.2, Laravel ^12 |
| Frontend | Livewire 3, Volt, Alpine.js, Tailwind CSS |
| Base de datos | MySQL |
| Build | Vite 7 |
| PDF | barryvdh/laravel-dompdf |
| QR | simplesoftwareio/simple-qrcode |
| Facturación SRI | XML + SOAP + Firma digital PKCS#12 |
| Roles/Permisos | spatie/laravel-permission |

## Requisitos

- PHP ^8.2
- Composer
- Node.js ^18
- MySQL 8.x

## Instalación

```bash
git clone <repo-url>
cd solarmed
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

## Desarrollo

```bash
composer dev
```

Ejecuta simultáneamente: servidor Laravel, listener de colas, logs y Vite HMR.

## Licencia

MIT
