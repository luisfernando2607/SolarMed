<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\FormularioTurno;
use App\Livewire\SalaEspera;
use App\Livewire\AtenderTurno;
use App\Livewire\ListaPacientes;
use App\Livewire\CrearConsulta;
use App\Livewire\EditarConsulta;
use App\Livewire\CrearEcografia;
use App\Livewire\CrearReceta;
use App\Livewire\ListaExpedientes;
use App\Livewire\CrearConsultaSinTurno;
use App\Livewire\GestionUsuarios;
use App\Livewire\ListaFacturas;
use App\Livewire\CrearFactura;
use App\Livewire\VerFactura;
use App\Livewire\GestionTarifario;
use App\Models\Ecografia;
use App\Services\EcografiaService;
use Barryvdh\DomPDF\Facade\Pdf;

// Landing público — formulario de turno para pacientes
Route::view('/', 'welcome')->name('turno.form');

// Portada pública de la clínica: especialidades, médicos, horario, contacto y verificación de certificados
Route::get('home', function () {
    $ajustes = app(App\Services\AjustesService::class);
    $nombresDias = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 0 => 'domingo'];
    $dias = collect($nombresDias)->only($ajustes->get('agenda.dias_laborables'))->values();
    $consecutivos = $dias->count() > 2 && $dias->count() === count($ajustes->get('agenda.dias_laborables'))
        && array_values(array_intersect(array_keys($nombresDias), $ajustes->get('agenda.dias_laborables'))) === range(1, $dias->count());

    return view('home', [
        'especialidades' => App\Models\Especialidad::where('activo', true)
            ->with(['medicos' => fn ($q) => $q->where('activo', true)->orderBy('apellidos')])
            ->orderBy('nombre')->get(),
        'diasTexto' => ucfirst($consecutivos ? "{$dias->first()} a {$dias->last()}" : $dias->implode(', ')),
        'horario' => $ajustes->get('agenda.hora_inicio') . ' – ' . $ajustes->get('agenda.hora_fin'),
    ]);
})->name('home');

// Formulario de la portada: lleva a la página de verificación del código ingresado
Route::get('verificar', function (Illuminate\Http\Request $request) {
    $codigo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('codigo')));
    return $codigo ? redirect()->route('certificados.verificar', $codigo) : redirect()->route('home');
})->middleware('throttle:30,1')->name('certificados.buscar');

// Dashboard protegido
// Inicio del personal (el admin es redirigido a su dashboard)
Route::get('dashboard', App\Livewire\InicioPersonal::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('admin/dashboard', \App\Livewire\AdminDashboard::class)
    ->middleware(['auth', 'can:configuracion.editar'])
    ->name('admin.dashboard');

// Imagen de la firma del médico autenticado (solo para la vista previa en su perfil)
Route::get('perfil/firma', function () {
    $medico = App\Models\Medico::where('user_id', auth()->id())->firstOrFail();
    abort_unless($medico->firmaRuta(), 404);
    return response()->file($medico->firmaRuta(), ['Cache-Control' => 'private, no-store']);
})->middleware(['auth'])->name('firma.ver');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Recepcionista — sala de espera
Route::get('sala-espera', SalaEspera::class)
    ->middleware(['auth', 'can:turnos.ver'])
    ->name('sala-espera');

// Agenda de citas
Route::get('agenda', \App\Livewire\Agenda::class)
    ->middleware(['auth', 'can:turnos.ver'])
    ->name('agenda');

// Recepcionista — crear turno manual
Route::get('turnos/crear', \App\Livewire\CrearTurnoRecepcion::class)
    ->middleware(['auth', 'can:turnos.ver'])
    ->name('turnos.crear');

// Médico — atender un turno
Route::get('atender/{turno}', AtenderTurno::class)
    ->middleware(['auth', 'can:expediente.ver'])
    ->name('turno.atender');

// Pacientes
Route::get('pacientes', ListaPacientes::class)
    ->middleware(['auth', 'can:pacientes.ver'])
    ->name('pacientes');
Route::get('pacientes/{paciente}', \App\Livewire\FichaPaciente::class)
    ->whereNumber('paciente')
    ->middleware(['auth', 'can:pacientes.ver'])
    ->name('pacientes.ver');

// Consultas
Route::get('consultas/crear/{turno}', CrearConsulta::class)
    ->middleware(['auth', 'can:expediente.crear'])
    ->name('consultas.crear');

Route::get('consultas/editar/{consulta}', EditarConsulta::class)
    ->middleware(['auth', 'can:update,consulta'])
    ->name('consultas.editar');

// Ecografías
Route::get('ecografias/crear/{turno}', CrearEcografia::class)
    ->middleware(['auth', 'can:ecografias.crear'])
    ->name('ecografias.crear');

Route::get('ecografias/pdf/{ecografia}', function (Ecografia $ecografia) {
    $ecografia->load('paciente', 'medico.especialidad');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'ecografia', $ecografia->id, $ecografia->paciente_id, 'PDF de ecografía');
    return Pdf::loadView('pdf.ecografia', ['eco' => $ecografia])
        ->setPaper('a4', 'portrait')
        ->stream("ecografia_{$ecografia->id}.pdf");
})->middleware(['auth', 'can:ecografias.ver'])->name('ecografias.pdf');

// Recetas
Route::get('recetas/crear/{turno}', CrearReceta::class)
    ->middleware(['auth', 'can:expediente.crear'])
    ->name('recetas.crear');

Route::get('recetas/pdf/{receta}', function (App\Models\Receta $receta) {
    $receta->load('paciente', 'medico.especialidad', 'consulta');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'receta', $receta->id, $receta->paciente_id, 'PDF de receta');
    return Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.receta', ['receta' => $receta])
        ->setPaper('a5', 'portrait')
        ->stream("receta_{$receta->id}.pdf");
})->middleware(['auth', 'can:expediente.ver'])->name('recetas.pdf');

// Archivos del paciente (laboratorios, imágenes, referencias)
Route::get('pacientes/{paciente}/archivos', \App\Livewire\ArchivosPaciente::class)
    ->middleware(['auth', 'can:expediente.ver'])
    ->name('pacientes.archivos');

Route::get('pacientes/archivos/{archivo}', function (App\Models\PacienteArchivo $archivo) {
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'archivo', $archivo->id, $archivo->paciente_id, $archivo->nombre_original);
    return Illuminate\Support\Facades\Storage::disk('private')->response($archivo->ruta, $archivo->nombre_original, [
        'Content-Type' => $archivo->mime_type,
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'private, no-store',
    ], 'inline');
})->middleware(['auth', 'can:expediente.ver'])->name('pacientes.archivo');

// Control prenatal
Route::get('prenatal/{paciente}', \App\Livewire\ControlPrenatalPaciente::class)
    ->middleware(['auth', 'can:expediente.ver'])
    ->name('prenatal');

// Pedidos de exámenes
Route::get('pedidos-examenes/crear/{paciente}', \App\Livewire\CrearPedidoExamenes::class)
    ->middleware(['auth', 'can:expediente.crear'])
    ->name('pedidos.crear');

Route::get('pedidos-examenes/pdf/{pedido}', function (App\Models\PedidoExamen $pedido) {
    $pedido->load('paciente', 'medico.especialidad');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'pedido_examenes', $pedido->id, $pedido->paciente_id, "PDF pedido de exámenes {$pedido->numero()}");
    return Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.pedido-examenes', ['p' => $pedido])
        ->setPaper('a5', 'portrait')
        ->stream("pedido_examenes_{$pedido->numero()}.pdf");
})->middleware(['auth', 'can:expediente.ver'])->name('pedidos.pdf');

// Certificados médicos
Route::get('certificados/crear/{paciente}', \App\Livewire\CrearCertificado::class)
    ->middleware(['auth', 'can:expediente.crear'])
    ->name('certificados.crear');

Route::get('certificados/pdf/{certificado}', function (App\Models\CertificadoMedico $certificado) {
    $certificado->load('paciente', 'medico.especialidad');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'certificado', $certificado->id, $certificado->paciente_id, "PDF certificado {$certificado->numero()}");
    $qr = base64_encode((string) SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')->size(160)->margin(0)->generate($certificado->urlVerificacion()));
    return Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.certificado', ['c' => $certificado, 'qr' => $qr])
        ->setPaper('a4', 'portrait')
        ->stream("certificado_{$certificado->numero()}.pdf");
})->middleware(['auth', 'can:expediente.ver'])->name('certificados.pdf');

// Verificación pública de certificados (sin diagnóstico; datos personales parcialmente ocultos)
Route::get('verificar/{codigo}', function (string $codigo) {
    $c = App\Models\CertificadoMedico::with('paciente', 'medico')->where('codigo_verificacion', strtoupper($codigo))->first();
    $ocultar = fn (?string $t) => collect(preg_split('/\s+/', trim((string) $t)))->filter()
        ->map(fn ($w) => mb_substr($w, 0, 1) . str_repeat('*', max(2, mb_strlen($w) - 1)))->implode(' ');
    return view('certificados.verificar', [
        'c' => $c,
        'pacienteOculto' => $c ? ($c->paciente?->identidad_reservada ? 'Identidad reservada' : $ocultar($c->paciente?->nombre_completo)) : null,
        'cedulaOculta' => $c ? str_repeat('*', max(0, strlen((string) $c->paciente?->cedula) - 3)) . substr((string) $c->paciente?->cedula, -3) : null,
    ]);
})->middleware('throttle:30,1')->name('certificados.verificar');

// Expedientes clínicos
Route::get('expedientes', ListaExpedientes::class)
    ->middleware(['auth', 'can:expediente.ver'])
    ->name('expedientes');

Route::get('expedientes/crear', CrearConsultaSinTurno::class)
    ->middleware(['auth', 'can:expediente.crear'])
    ->name('expedientes.crear');

// Usuarios (solo admin)
Route::get('usuarios', GestionUsuarios::class)
    ->middleware(['auth', 'can:usuarios.ver'])
    ->name('usuarios');

// Facturación
Route::get('facturas', ListaFacturas::class)
    ->middleware(['auth', 'can:facturas.ver'])
    ->name('facturas');

Route::get('facturas/crear', CrearFactura::class)
    ->middleware(['auth', 'can:facturas.crear'])
    ->name('facturas.crear');

Route::get('facturas/{factura}', VerFactura::class)
    ->middleware(['auth', 'can:facturas.ver'])
    ->name('facturas.ver');

// Factura PDF
Route::get('facturas/pdf/{factura}', function (App\Models\Factura $factura) {
    $factura->load('paciente', 'medico', 'items');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'factura', $factura->id, $factura->paciente_id, "PDF factura {$factura->numero_factura}");
    return app(App\Services\RidePdfService::class)->factura($factura)
        ->stream("factura_{$factura->numero_factura}.pdf");
})->middleware(['auth', 'can:facturas.ver'])->name('facturas.pdf');

// XML autorizado por el SRI (para entregar al cliente)
Route::get('facturas/xml/{factura}', function (App\Models\Factura $factura) {
    abort_unless($factura->estado_sri === 'autorizado' && $factura->xml_autorizado_path, 404);
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'factura', $factura->id, $factura->paciente_id, "XML autorizado factura {$factura->numero_factura}");
    return Illuminate\Support\Facades\Storage::disk('private')
        ->download($factura->xml_autorizado_path, "factura_{$factura->numero_factura}.xml", ['Content-Type' => 'application/xml']);
})->middleware(['auth', 'can:facturas.ver'])->name('facturas.xml');

// Nota de crédito: PDF y XML autorizado
Route::get('notas-credito/pdf/{notaCredito}', function (App\Models\NotaCredito $notaCredito) {
    $notaCredito->load('factura.paciente', 'factura.items', 'medico');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'nota_credito', $notaCredito->id, $notaCredito->factura->paciente_id, "PDF nota de crédito {$notaCredito->numero}");
    return app(App\Services\RidePdfService::class)->notaCredito($notaCredito)
        ->stream("nota_credito_{$notaCredito->numero}.pdf");
})->middleware(['auth', 'can:facturas.ver'])->name('notas-credito.pdf');

Route::get('notas-credito/xml/{notaCredito}', function (App\Models\NotaCredito $notaCredito) {
    abort_unless($notaCredito->estado_sri === 'autorizado' && $notaCredito->xml_autorizado_path, 404);
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'nota_credito', $notaCredito->id, $notaCredito->factura?->paciente_id, "XML autorizado nota de crédito {$notaCredito->numero}");
    return Illuminate\Support\Facades\Storage::disk('private')
        ->download($notaCredito->xml_autorizado_path, "nota_credito_{$notaCredito->numero}.xml", ['Content-Type' => 'application/xml']);
})->middleware(['auth', 'can:facturas.ver'])->name('notas-credito.xml');

// Reporte mensual de ventas para el contador
Route::get('reportes/ventas', \App\Livewire\ReporteVentas::class)
    ->middleware(['auth', 'can:reportes.ver'])
    ->name('reportes.ventas');

// Auditoría de accesos a datos clínicos (solo admin)
Route::get('auditoria', \App\Livewire\Auditoria::class)
    ->middleware(['auth', 'can:auditoria.ver'])
    ->name('auditoria');
Route::get('auditoria/exportar', function (Illuminate\Http\Request $request) {
    $filtros = $request->only(['paciente', 'usuario', 'accion', 'desde', 'hasta']);
    app(\App\Services\AuditoriaService::class)->registrar('exportar', 'auditoria', null, null,
        'CSV del registro de accesos' . ($filtros ? ' · ' . http_build_query($filtros) : ''));

    return response()->streamDownload(function () use ($filtros) {
        $salida = fopen('php://output', 'w');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM: Excel abre bien las tildes
        fputcsv($salida, ['ID', 'Fecha', 'Usuario', 'Acción', 'Recurso', 'ID recurso', 'Paciente', 'Cédula', 'Detalle', 'IP', 'Navegador', 'Sello SHA-256'], ';');
        App\Models\RegistroAcceso::with('user', 'paciente')->filtrar($filtros)->orderBy('id')
            ->chunk(1000, function ($registros) use ($salida) {
                // Evita que Excel interprete como fórmula un texto escrito por un usuario (p. ej. el correo de un intento fallido)
                $seguro = fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v;
                foreach ($registros as $r) {
                    fputcsv($salida, array_map($seguro, [
                        $r->id, $r->created_at->format('Y-m-d H:i:s'), $r->user?->name, App\Models\RegistroAcceso::ACCIONES[$r->accion] ?? $r->accion,
                        $r->recurso, $r->recurso_id, $r->paciente?->nombre_completo, $r->paciente?->cedula, $r->detalle, $r->ip, $r->user_agent, $r->hash,
                    ]), ';');
                }
            });
        fclose($salida);
    }, 'auditoria_' . now()->format('Ymd_His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
})->middleware(['auth', 'can:auditoria.ver'])->name('auditoria.exportar');

// Solicitudes sobre la HC (AM 5216-A Arts. 12, 28-30; LOPDP). Ven auditoría y quienes editan pacientes.
Route::get('auditoria/solicitudes', \App\Livewire\SolicitudesInformacion::class)
    ->middleware(['auth'])
    ->name('auditoria.solicitudes');
Route::get('auditoria/solicitudes/{solicitud}/documento', function (App\Models\SolicitudInformacion $solicitud) {
    abort_unless(auth()->user()->canAny(['auditoria.ver', 'pacientes.editar']) && $solicitud->documento_ruta, 404);
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'solicitud', $solicitud->id, $solicitud->paciente_id, 'Respaldo de la solicitud');
    return Illuminate\Support\Facades\Storage::disk('private')->response($solicitud->documento_ruta, $solicitud->documento_nombre, [
        'Content-Type' => $solicitud->documento_mime,
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'private, no-store',
    ], 'inline');
})->middleware(['auth'])->name('auditoria.solicitudes.documento');

// Incidentes de seguridad de datos (LOPDP)
Route::get('auditoria/incidentes', \App\Livewire\IncidentesSeguridad::class)
    ->middleware(['auth', 'can:auditoria.ver'])
    ->name('auditoria.incidentes');

// Tarifario (solo admin)
Route::get('tarifario', GestionTarifario::class)
    ->middleware(['auth', 'can:configuracion.editar'])
    ->name('tarifario');

// Especialidades y configuración general (solo admin)
Route::get('especialidades', \App\Livewire\GestionEspecialidades::class)
    ->middleware(['auth', 'can:configuracion.editar'])
    ->name('especialidades');

Route::get('configuracion', \App\Livewire\ConfiguracionClinica::class)
    ->middleware(['auth', 'can:configuracion.editar'])
    ->name('configuracion');

// Roles y permisos (solo admin)
Route::get('roles', \App\Livewire\RolesPermisos::class)
    ->middleware(['auth', 'can:configuracion.editar'])
    ->name('roles');

// SRI (solo admin)
Route::get('sri/configuracion', \App\Livewire\SriConfigPage::class)
    ->middleware(['auth', 'can:configuracion.editar'])
    ->name('sri.config');

// Historial paciente PDF
Route::get('pacientes/pdf/{paciente}', function (App\Models\Paciente $paciente) {
    $paciente->load('consultas.medico', 'consultas.especialidad');
    app(\App\Services\AuditoriaService::class)->registrar('descargar', 'historia_clinica', $paciente->id, $paciente->id, 'PDF historia clínica completa');
    return Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.historial-paciente', ['paciente' => $paciente])
        ->setPaper('a4', 'portrait')
        ->stream("historial_{$paciente->id}.pdf");
})->middleware(['auth', 'can:expediente.ver'])->name('pacientes.pdf');

// Requisitos de la cuenta: clave personal y acuerdo de confidencialidad (AM 5216-A)
Route::get('cuenta/password', \App\Livewire\Cuenta\CambiarPassword::class)
    ->middleware('auth')->name('cuenta.password');
Route::get('cuenta/confidencialidad', \App\Livewire\Cuenta\AcuerdoConfidencialidad::class)
    ->middleware('auth')->name('cuenta.confidencialidad');

require __DIR__.'/auth.php';
