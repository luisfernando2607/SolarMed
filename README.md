<div align="center">

<img src="docs/SolarMed1.jpg" alt="SolarMed" width="220"/>

# SolarMed

**Sistema de gestión clínica integral para consultorios privados en Ecuador**

Turnos · Historia clínica · Control prenatal · Ecografías · Facturación electrónica SRI

![Laravel](https://img.shields.io/badge/Laravel_12-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Livewire](https://img.shields.io/badge/Livewire_3-4E56A6?style=for-the-badge&logo=livewire&logoColor=white)
![PHP](https://img.shields.io/badge/PHP_8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL_8-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![License](https://img.shields.io/badge/licencia-MIT-2ea44f?style=for-the-badge)

</div>

---

## 🩺 El problema

Muchos consultorios pequeños aún trabajan con **historias clínicas en papel, agendas manuales y facturación improvisada**: expedientes perdidos, salas de espera desordenadas, riesgo de multas del SRI y cero visibilidad del negocio.

**SolarMed** reúne todo en un solo sistema que funciona **en red local**, sin depender de internet ni de mensualidades.

---

## ✨ Funcionalidades

| Módulo | Qué hace |
|---|---|
| 🎫 **Turnos** | Cola en tiempo real con kiosco de auto-registro, numeración por especialidad y estados (esperando, en atención, completado, cancelado) |
| 👩‍⚕️ **Atención médica** | Panel del doctor: consulta clínica, diagnósticos **CIE-10**, solicitud de ecografías y recetas |
| 📂 **Historia clínica** | Anamnesis, examen físico, diagnóstico, tratamiento y referencias, con búsqueda |
| 🤰 **Control prenatal** | Semanas de gestación, FPP, peso, presión, altura uterina, latidos fetales y checklists |
| 🔬 **Ecografías** | Biometría estándar (DBP, CC, CA, LF), peso fetal estimado y reporte PDF |
| 📅 **Agenda** | Calendario interactivo (FullCalendar) con citas por especialidad |
| 🧾 **Facturación SRI** | XML, firma digital `.p12`, envío SOAP, numeración autorizada y PDF tipo ticket |
| 📎 **Archivos** | Carga y categorización de laboratorios, imágenes y referencias |
| 📊 **Dashboard** | Pacientes por sexo/edad/ciudad, tendencia de turnos, facturación mensual, mapa de calor y top diagnósticos |
| 🔐 **Usuarios y permisos** | RBAC con roles admin, secretaria y médico (Spatie) |

---

## 🛠️ Stack

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 · Laravel 12 |
| Frontend | Livewire 3 · Volt · Alpine.js · Tailwind CSS |
| Base de datos | MySQL 8 |
| Build | Vite 7 |
| PDF / QR | barryvdh/laravel-dompdf · simple-qrcode |
| Facturación | XML + SOAP + firma PKCS#12 |
| Permisos | spatie/laravel-permission |

---

## 🚀 Instalación

**Requisitos:** PHP 8.2+, Composer, Node.js 18+, MySQL 8

```bash
git clone https://github.com/luisfernando2607/SolarMed.git
cd SolarMed
composer install
npm install
cp .env.example .env          # configura la base de datos
php artisan key:generate
php artisan migrate --seed
npm run build
```

**Modo desarrollo** (servidor, colas, logs y Vite HMR en paralelo):

```bash
composer dev
```

📄 Documentación técnica: [`docs/doc-tecnico.md`](docs/doc-tecnico.md)

---

<div align="center">

Desarrollado por **[Luis Fernando Flores](https://github.com/luisfernando2607)** · DevStar · Guayaquil, Ecuador 🇪🇨

<sub>Licencia MIT</sub>

</div>
