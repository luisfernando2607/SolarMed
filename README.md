<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# SolarMed

Sistema de gestión clínica integral para **SolarMed Software**, una consulta privada de Ginecología y Medicina General en Ecuador. Desarrollado con Laravel 12 y Livewire 3.

## Funcionalidades

- **Módulo de turnos** — Colas en tiempo real con auto-registro del paciente (kiosco virtual), numeración secuencial por especialidad y seguimiento de estados (esperando, en atención, completado, cancelado).
- **Atención médica** — Panel del médico para atender desde la sala de espera: consultas clínicas con diagnóstico CIE-10, ecografías y recetas.
- **Historia clínica (Expedientes)** — Consultas con anamnesis, examen físico, diagnóstico, tratamiento y referencias. Solo el autor corrige y cada corrección queda versionada con su motivo.
- **Control prenatal** — Embarazos por paciente: edad gestacional y FPP desde la FUM, controles (peso, PA, altura uterina, FCF, presentación, movimientos fetales, edemas) con alertas clínicas, checklist de exámenes por trimestre (`config/clinica.php`) y cierre con desenlace.
- **Recetas** — Receta en PDF (A5) con alergias resaltadas, vinculada a la consulta.
- **Agenda de citas** — Calendario (FullCalendar) por médico con colores por especialidad, paciente nuevo o existente, control de choques, reprogramación arrastrando y "Registrar llegada" que crea el turno en sala de espera.
- **Archivos del paciente** — Laboratorios, imágenes, referencias y fotos clínicas (PDF/JPG/PNG), con miniaturas y descarga auditada.
- **Ecografías** — Informes con biometría fetal (DBP, CC, CA, LF), peso fetal estimado y PDF.
- **Facturación electrónica SRI** — Factura y nota de crédito electrónicas con el RUC de cada médico, firma XAdES-BES, autorización automática en segundo plano y envío al paciente por correo (PDF + XML).
- **Recordatorios de citas** — Correo automático a las 17:00 a las pacientes con cita al día siguiente.
- **Reporte de ventas** — Resumen mensual por RUC (facturas, notas de crédito, neto, forma de pago) exportable a Excel para el contador.
- **Auditoría** — Registro de quién vio, descargó o modificó datos clínicos (LOPDP).
- **Dashboard administrativo** — Estadísticas (pacientes por sexo/edad/ciudad, turnos, facturación mensual, diagnósticos frecuentes).
- **Usuarios y permisos** — Roles (admin, médico, secretaria, enfermería) y permisos por ruta vía Spatie.

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

Ejecuta simultáneamente: servidor Laravel, listener de colas, logs, Vite HMR y el programador de tareas.

### Pruebas

```bash
php artisan test
```

Cubren permisos por rol, turnos, facturación, formulario de consulta y facturación electrónica SRI
(XML, firma XAdES-BES y web services simulados). Usan SQLite en memoria; no tocan la base `solarmed`.

## Historia clínica y auditoría

Una consulta solo la puede corregir el médico que la registró. Cada corrección exige un motivo y guarda la
versión anterior, visible en el *Historial de correcciones* de la consulta.

Cada vez que alguien ve la ficha de un paciente, abre una consulta, descarga un PDF o XML, o crea,
corrige o elimina información clínica, queda registrado (usuario, IP y fecha). El administrador lo
consulta en **Auditoría**.

## Usuarios y permisos

No hay registro público: los usuarios los crea un administrador en **Usuarios**. Cada ruta exige un
permiso (`routes/web.php`) y los roles se definen en `database/seeders/RolesSeeder.php`:

| Rol | Acceso |
|---|---|
| admin | Todo |
| medico | Pacientes, expedientes, ecografías, facturación |
| secretaria | Turnos, sala de espera, facturación |
| enfermeria | Ver turnos y pacientes |

## Verificación en dos pasos (2FA)

Cada usuario la activa en **Perfil** con una app autenticadora (Google Authenticator, Microsoft
Authenticator o Authy) y recibe 8 códigos de recuperación de un solo uso. Para exigirla a ciertos roles:

```
TWO_FACTOR_ROLES=admin,medico
```

Los usuarios de esos roles que aún no la tengan solo podrán entrar a su perfil hasta activarla.
Si alguien pierde el teléfono y sus códigos, un admin puede desactivarla desde la base de datos
(`two_factor_secret`, `two_factor_recovery_codes` y `two_factor_confirmed_at` en `NULL`).

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
4. En la factura: **Emitir al SRI** genera el XML, lo firma y lo envía. La autorización se consulta
   sola en segundo plano (cola `AutorizarComprobanteSri`), con reintentos si el SRI está caído o
   "EN PROCESO"; la pantalla se actualiza sola. Si el SRI devuelve el comprobante, sus mensajes se
   muestran en la factura y se puede corregir y reenviar.

5. **Anular una factura autorizada**: en la factura, *Emitir nota de crédito* con el motivo. La nota de
   crédito electrónica (codDoc 04) se genera, firma y envía al SRI; al ser autorizada, la factura queda anulada.

6. **Envío por correo**: al autorizarse, la factura o nota de crédito se envía sola al correo del paciente
   (PDF + XML autorizado). También se puede enviar o reenviar a otro correo desde la factura. Configurar
   `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` y `MAIL_FROM_ADDRESS` en `.env`
   (con `MAIL_MAILER=log` los correos solo se escriben en el log).

> El certificado `.p12` de cada médico debe corresponder a su RUC; de lo contrario el SRI rechaza el
> comprobante. Probar siempre primero en ambiente de pruebas.

## Producción

- `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`, `SESSION_ENCRYPT=true`,
  `APP_URL` con `https://`.
- Servir solo por HTTPS (la app maneja historias clínicas y certificados digitales).
- `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- **Cola** (autorización automática del SRI) siempre activa, por ejemplo con Supervisor:
  `php artisan queue:work --tries=1 --max-time=3600`
- **Programador de tareas** (`sri:reintentar` cada 15 min, `citas:recordatorios` 17:00, `respaldo:crear` 02:30) en el cron del servidor:
  `* * * * * cd /ruta/SolarMed && php artisan schedule:run >> /dev/null 2>&1`
- **Respaldos**: `php artisan respaldo:crear` corre cada día a las 02:30 (requiere el cron). Incluye la base
  `solarmed` y `storage/app/private` (certificados, XML del SRI, archivos de pacientes). Configurar en `.env`:
  `BACKUP_PATH` (idealmente un disco externo o carpeta sincronizada fuera del servidor), `BACKUP_DIAS` y
  `BACKUP_PASSWORD` (cifra con AES-256; guardarla en lugar seguro: sin ella no se puede restaurar).
  Restaurar:
  ```bash
  openssl enc -d -aes-256-cbc -pbkdf2 -in solarmed_FECHA.tar.gz.enc -out respaldo.tar.gz   # pide la contraseña
  tar -xzf respaldo.tar.gz            # base.sql y private/
  mysql -u solarmed -p solarmed < base.sql
  cp -r private/* storage/app/private/
  ```
- No versionar `.env` ni volcados de base de datos con datos reales.

## Licencia

MIT
