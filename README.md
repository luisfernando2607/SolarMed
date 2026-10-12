<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# SolarMed

Sistema de gestión clínica integral para **SolarMed Software**, una consulta privada de Ginecología y Medicina General en Ecuador. Desarrollado con Laravel 12 y Livewire 3.

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
cd SolarMed
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Crear la base de datos `solarmed` y un usuario propio (no usar `root`):

```sql
CREATE DATABASE solarmed CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'solarmed'@'localhost' IDENTIFIED BY '<contraseña segura>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES,
      LOCK TABLES, CREATE TEMPORARY TABLES ON solarmed.* TO 'solarmed'@'localhost';
```

Completar `DB_USERNAME` y `DB_PASSWORD` en `.env` y luego:

```bash
php artisan migrate --seed
npm run build
```

## Desarrollo

```bash
composer dev
```

Ejecuta simultáneamente: servidor Laravel, listener de colas, logs y Vite HMR.

### Pruebas

```bash
php artisan test
```

Cubren permisos por rol, turnos, facturación, formulario de consulta y facturación electrónica SRI
(XML, firma XAdES-BES y web services simulados). Usan SQLite en memoria; no tocan la base `solarmed`.

## Historia clínica

Una consulta solo la puede corregir el médico que la registró. Cada corrección exige un motivo y guarda la
versión anterior, visible en el *Historial de correcciones* de la consulta.

## Usuarios y permisos

No hay registro público: los usuarios los crea un administrador en **Usuarios**. Cada ruta exige un
permiso (`routes/web.php`) y los roles se definen en `database/seeders/RolesSeeder.php`:

| Rol | Acceso |
|---|---|
| admin | Todo |
| medico | Pacientes, expedientes, ecografías, facturación |
| secretaria | Turnos, sala de espera, facturación |
| enfermeria | Ver turnos y pacientes |

## Facturación electrónica SRI

Cada médico factura con **su propio RUC** y su propia numeración (`001-001-000000001` por RUC).

1. **SRI > Configuración**: ambiente (`1` = pruebas, `2` = producción) y datos de la clínica, que se usan
   como respaldo para médicos que aún no tienen RUC configurado.
2. **Usuarios > editar médico**:
   - Datos tributarios: RUC, razón social, dirección matriz, obligado a llevar contabilidad,
     régimen RIMPE, establecimiento y punto de emisión.
   - Certificado `.p12` del médico y su contraseña. Se aceptan certificados *legacy*
     (Security Data, BCE) que OpenSSL 3 no abre por defecto.
3. **Facturación > Nueva factura**: elegir el médico emisor (se sugiere el que atendió al paciente).
4. En la factura: **Generar XML → Firmar → Enviar al SRI → Consultar autorización**.
   Si el SRI devuelve el comprobante, sus mensajes se muestran en la factura y se puede regenerar.

5. **Anular una factura autorizada**: en la factura, *Emitir nota de crédito* con el motivo. La nota de
   crédito electrónica (codDoc 04) se genera, firma y envía al SRI; al ser autorizada, la factura queda anulada.

> El certificado `.p12` de cada médico debe corresponder a su RUC; de lo contrario el SRI rechaza el
> comprobante. Probar siempre primero en ambiente de pruebas.

## Producción

- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_ENCRYPT=true`,
  `APP_URL` con `https://`.
- Servir solo por HTTPS (la app maneja historias clínicas y certificados digitales).
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Respaldar la base `solarmed` **y** `storage/app/private` (certificados, XML del SRI, archivos de pacientes).
- No versionar `.env` ni volcados de base de datos con datos reales.

## Licencia

MIT
