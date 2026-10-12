<div align="center">

<img src="docs/SolarMed1.jpg" alt="SolarMed" width="220"/>

# SolarMed

**Sistema de gestión clínica integral para consultorios privados en Ecuador**

Turnos · Agenda · Historia clínica · Control prenatal · Ecografías · Facturación electrónica SRI

![Laravel](https://img.shields.io/badge/Laravel_12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire_3-4E56A6?style=for-the-badge&logo=livewire&logoColor=white)
![PHP](https://img.shields.io/badge/PHP_8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL_8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![License](https://img.shields.io/badge/licencia-MIT-2ea44f?style=for-the-badge)
[![Pruebas](https://github.com/luisfernando2607/SolarMed/actions/workflows/tests.yml/badge.svg)](https://github.com/luisfernando2607/SolarMed/actions/workflows/tests.yml)

</div>

---

## 🩺 El problema

Muchos consultorios pequeños aún trabajan con **historias clínicas en papel, agendas manuales y facturación improvisada**: expedientes perdidos, salas de espera desordenadas, riesgo de multas del SRI y cero visibilidad del negocio.

**SolarMed** reúne todo en un solo sistema que funciona **en red local**, sin mensualidades. Solo la facturación electrónica (SRI) y el envío de correos necesitan conexión a internet.

---

## ✨ Funcionalidades

| Módulo | Qué hace |
|---|---|
| 🎫 **Turnos** | Cola en tiempo real con kiosco de auto-registro, numeración por especialidad y estados (esperando, en atención, completado, cancelado) |
| 📅 **Agenda** | Calendario (FullCalendar) por médico con colores por especialidad, control de choques, reprogramación arrastrando, "Registrar llegada" que crea el turno y recordatorio por correo el día anterior |
| 👩‍⚕️ **Atención médica** | Panel del doctor: consulta clínica, diagnósticos **CIE-10**, ecografías y recetas en PDF (con alergias resaltadas) |
| 📂 **Historia clínica** | Anamnesis, examen físico, diagnóstico, tratamiento y referencias. Solo el autor corrige y cada corrección queda versionada con su motivo |
| 🤰 **Control prenatal** | Embarazos por paciente: EG y FPP desde la FUM, controles con **alertas clínicas** (hipertensión, FCF, altura uterina, movimientos fetales), checklist de exámenes por trimestre y cierre con desenlace |
| 🔬 **Ecografías** | Biometría estándar (DBP, CC, CA, LF), peso fetal estimado y reporte PDF |
| 📎 **Archivos** | Laboratorios, imágenes, referencias y fotos clínicas (PDF/JPG/PNG) con miniaturas y descarga auditada |
| 🧾 **Facturación SRI** | Factura y **nota de crédito** electrónicas con el RUC de cada médico, firma **XAdES-BES**, autorización automática en segundo plano y envío al paciente por correo (PDF + XML) |
| 📊 **Dashboard** | Panel de pendientes (citas, SRI, embarazos con alertas), facturación por médico, pacientes por sexo/edad/ciudad, tendencia de turnos y top diagnósticos |
| 📈 **Reportes** | Ventas mensuales por RUC para el contador, exportables a Excel |
| 🔐 **Seguridad** | Roles y permisos por ruta (Spatie), verificación en dos pasos, **auditoría** de accesos a datos clínicos (LOPDP) y respaldos diarios cifrados |

---

## 🛠️ Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 · Laravel 12 |
| Frontend | Livewire 3 · Volt · Alpine.js · Tailwind CSS · FullCalendar · Chart.js |
| Base de datos | MySQL 8 |
| Build | Vite 7 |
| PDF / QR | barryvdh/laravel-dompdf · simple-qrcode |
| Facturación | XML + web services SRI + firma XAdES-BES (PKCS#12) |
| Seguridad | spatie/laravel-permission · pragmarx/google2fa |
| CI | GitHub Actions |

---

## 🚀 Instalación

**Requisitos:** PHP 8.2+, Composer, Node.js 18+, MySQL 8

```bash
git clone https://github.com/luisfernando2607/SolarMed.git
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

**Modo desarrollo** (servidor, colas, logs, Vite HMR y tareas programadas en paralelo):

```bash
composer dev
```

📄 Documentación técnica: [`docs/doc-tecnico.md`](docs/doc-tecnico.md)

---

## 🧪 Pruebas

```bash
php artisan test
```

Más de 100 pruebas: permisos por rol, turnos, consultas e historial de correcciones, control prenatal,
agenda, archivos, facturación y nota de crédito SRI (XML, firma XAdES-BES verificada criptográficamente y
web services simulados), correo, auditoría, 2FA, respaldos y reportes. Usan SQLite en memoria (no tocan la
base `solarmed`) y se ejecutan automáticamente en GitHub Actions en cada PR.

---

## ⚙️ Configuración

Casi todo se ajusta sin tocar código:

| Dónde | Qué |
|---|---|
| **Configuración** (admin) | **Clínica:** nombre, subtítulo, logo (con versión en blanco y negro para tickets), dirección, teléfono, correo y color — se usan en el login, el menú, el formulario de turnos, los PDF y los correos. Tipos de cita, duraciones, horario y días de atención de la agenda; umbrales de las alertas prenatales; hora de los recordatorios y del respaldo |
| **Especialidades** (admin) | Nombre, prefijo del número de turno, color en la agenda, motivos de consulta del registro de turnos, activar/desactivar |
| **SRI** y **Usuarios** (admin) | Datos tributarios de la clínica y de cada médico, certificados `.p12`, ambiente de pruebas/producción |
| **Tarifario** (admin) | Servicios y precios |
| `.env` | Base de datos, correo, respaldos (`BACKUP_*`), roles con 2FA obligatoria (`TWO_FACTOR_ROLES`), tamaño máximo de archivos (`ARCHIVO_MAX_KB`), turnos por cédula al día (`TURNO_RATE_LIMIT`) |
| `config/clinica.php` | Checklist de exámenes prenatales por trimestre |

Aún requieren cambio de código: los permisos de cada rol (`RolesSeeder`) y la tarifa de IVA (todo se factura con IVA 0 %, propio de servicios de salud).

---

## 📄 Documentos médicos

- **Firma y sello:** cada médico sube su imagen desde **Perfil**; se imprime en recetas, certificados, pedidos de exámenes e informes de ecografía.
- **Certificado médico** (atención o reposo) desde la atención o la ficha del paciente, con **código QR de verificación**. La página `/verificar/{código}` confirma su autenticidad sin mostrar el diagnóstico.
  > Para que terceros (p. ej. un empleador) puedan verificar, `APP_URL` debe ser una dirección **pública** con HTTPS.
- **Pedido de exámenes** con el catálogo de laboratorio e imagen editable en **Configuración**.

---

## 🔐 Usuarios y permisos

No hay registro público: los usuarios los crea un administrador en **Usuarios**. Cada ruta exige un
permiso (`routes/web.php`) y los roles se definen en `database/seeders/RolesSeeder.php`:

| Rol | Acceso |
|---|---|
| admin | Todo |
| medico | Pacientes, expedientes, ecografías, facturación |
| secretaria | Turnos, sala de espera, facturación |
| enfermeria | Ver turnos y pacientes |

---

## 📱 Verificación en dos pasos (2FA)

Cada usuario la activa en **Perfil** con una app autenticadora (Google Authenticator, Microsoft
Authenticator o Authy) y recibe 8 códigos de recuperación de un solo uso. Para exigirla a ciertos roles:

```
TWO_FACTOR_ROLES=admin,medico
```

Los usuarios de esos roles que aún no la tengan solo podrán entrar a su perfil hasta activarla.
Si alguien pierde el teléfono y sus códigos, un admin puede desactivarla desde la base de datos
(`two_factor_secret`, `two_factor_recovery_codes` y `two_factor_confirmed_at` en `NULL`).

---

## 🗂️ Historia clínica y auditoría

Una consulta solo la puede corregir el médico que la registró. Cada corrección exige un motivo y guarda la
versión anterior, visible en el *Historial de correcciones* de la consulta.

Cada vez que alguien ve la ficha de un paciente, abre una consulta, descarga un PDF o XML, o crea,
corrige o elimina información clínica, queda registrado (usuario, IP y fecha). El administrador lo
consulta en **Auditoría**.

---

## 🧾 Facturación electrónica SRI

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

---

## 🏥 Producción

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

---

<div align="center">

Desarrollado por **[Luis Fernando Flores](https://github.com/luisfernando2607)** · DevStar · Guayaquil, Ecuador 🇪🇨

<sub>Licencia MIT</sub>

</div>
