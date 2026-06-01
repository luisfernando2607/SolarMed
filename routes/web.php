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

// Dashboard protegido
Route::get('dashboard', function () {
    if (request()->user()->can('configuracion.editar')) {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('admin/dashboard', \App\Livewire\AdminDashboard::class)
    ->middleware(['auth'])
    ->name('admin.dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Recepcionista — sala de espera
Route::get('sala-espera', SalaEspera::class)
    ->middleware(['auth'])
    ->name('sala-espera');

// Recepcionista — crear turno manual
Route::get('turnos/crear', \App\Livewire\CrearTurnoRecepcion::class)
    ->middleware(['auth'])
    ->name('turnos.crear');

// Médico — atender un turno
Route::get('atender/{turno}', AtenderTurno::class)
    ->middleware(['auth'])
    ->name('turno.atender');

// Pacientes
Route::get('pacientes', ListaPacientes::class)
    ->middleware(['auth'])
    ->name('pacientes');

// Consultas
Route::get('consultas/crear/{turno}', CrearConsulta::class)
    ->middleware(['auth'])
    ->name('consultas.crear');

Route::get('consultas/editar/{consulta}', EditarConsulta::class)
    ->middleware(['auth'])
    ->name('consultas.editar');

// Ecografías
Route::get('ecografias/crear/{turno}', CrearEcografia::class)
    ->middleware(['auth'])
    ->name('ecografias.crear');

Route::get('ecografias/pdf/{ecografia}', function (Ecografia $ecografia) {
    $ecografia->load('paciente', 'medico.especialidad');
    return Pdf::loadView('pdf.ecografia', ['eco' => $ecografia])
        ->setPaper('a4', 'portrait')
        ->stream("ecografia_{$ecografia->id}.pdf");
})->middleware(['auth'])->name('ecografias.pdf');

// Recetas
Route::get('recetas/crear/{turno}', CrearReceta::class)
    ->middleware(['auth'])
    ->name('recetas.crear');

// Expedientes clínicos
Route::get('expedientes', ListaExpedientes::class)
    ->middleware(['auth'])
    ->name('expedientes');

Route::get('expedientes/crear', CrearConsultaSinTurno::class)
    ->middleware(['auth'])
    ->name('expedientes.crear');

// Usuarios (solo admin)
Route::get('usuarios', GestionUsuarios::class)
    ->middleware(['auth'])
    ->name('usuarios');

// Facturación
Route::get('facturas', ListaFacturas::class)
    ->middleware(['auth'])
    ->name('facturas');

Route::get('facturas/crear', CrearFactura::class)
    ->middleware(['auth'])
    ->name('facturas.crear');

Route::get('facturas/{factura}', VerFactura::class)
    ->middleware(['auth'])
    ->name('facturas.ver');

// Factura PDF
Route::get('facturas/pdf/{factura}', function (App\Models\Factura $factura) {
    $factura->load('paciente', 'medico', 'items');
    return Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.factura', ['factura' => $factura])
        ->setPaper([0, 0, 226, 400], 'portrait')
        ->stream("factura_{$factura->numero_factura}.pdf");
})->middleware(['auth'])->name('facturas.pdf');

// Tarifario (solo admin)
Route::get('tarifario', GestionTarifario::class)
    ->middleware(['auth'])
    ->name('tarifario');

// SRI (solo admin)
Route::get('sri/configuracion', \App\Livewire\SriConfigPage::class)
    ->middleware(['auth'])
    ->name('sri.config');

require __DIR__.'/auth.php';
