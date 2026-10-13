<?php

namespace App\Livewire;

use App\Models\Consentimiento;
use App\Models\Medico;
use App\Services\ArchivoService;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Detalle del consentimiento y registro de los bloques C (declaración), D (negativa) y E (revocatoria).
 * Las firmas se hacen a mano sobre el formulario impreso; aquí se registra el hecho y se adjunta la copia firmada.
 */
class VerConsentimiento extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $consentimientoId;

    // Quien firma en lugar del paciente (menor de edad o sin capacidad para firmar)
    public bool $firmaRepresentante = false;
    public $representante_nombre;
    public $representante_cedula;
    public $representante_parentesco;

    // Bloque D: el paciente rechaza y además se niega a firmar
    public bool $negativaSinFirma = false;
    public $testigo_nombre;
    public $testigo_cedula;

    public $motivo_revocatoria;
    public $documento;

    public function mount(Consentimiento $consentimiento): void
    {
        $this->consentimientoId = $consentimiento->id;
        $this->firmaRepresentante = $consentimiento->requiereRepresentante();
        $this->representante_nombre = $consentimiento->representante_nombre;
        $this->representante_cedula = $consentimiento->representante_cedula;
        $this->representante_parentesco = $consentimiento->representante_parentesco;
        app(AuditoriaService::class)->registrar('ver', 'consentimiento', $consentimiento->id, $consentimiento->paciente_id, $consentimiento->procedimiento);
    }

    private function consentimiento(): Consentimiento
    {
        return Consentimiento::with('paciente', 'medico.especialidad', 'medico.user')->findOrFail($this->consentimientoId);
    }

    /** Datos del representante legal, obligatorios para menores de edad o si el paciente no puede firmar */
    private function representante(Consentimiento $c): array
    {
        if (!$this->firmaRepresentante && !$c->requiereRepresentante()) {
            return ['representante_nombre' => null, 'representante_cedula' => null, 'representante_parentesco' => null];
        }

        $this->validate([
            'representante_nombre' => 'required|string|max:150',
            'representante_cedula' => 'required|string|max:20',
            'representante_parentesco' => 'required|string|max:60',
        ], [
            'representante_nombre.required' => 'Escriba el nombre del representante legal.',
            'representante_cedula.required' => 'Escriba la cédula del representante legal.',
            'representante_parentesco.required' => 'Indique el parentesco.',
        ]);

        return [
            'representante_nombre' => trim($this->representante_nombre),
            'representante_cedula' => trim($this->representante_cedula),
            'representante_parentesco' => trim($this->representante_parentesco),
        ];
    }

    private function exigirEstado(Consentimiento $c, string $estado): void
    {
        if ($c->estado !== $estado) {
            throw ValidationException::withMessages(['accion' => 'Esta acción no corresponde al estado actual del consentimiento.']);
        }
    }

    /** Bloque C: el paciente (o su representante) firmó la declaración */
    public function aceptar(): void
    {
        $this->authorize('expediente.crear');
        $c = $this->consentimiento();
        $this->exigirEstado($c, 'pendiente_firma');

        $c->update($this->representante($c) + ['estado' => 'aceptado', 'fecha_aceptacion' => now()]);
        app(AuditoriaService::class)->registrar('editar', 'consentimiento', $c->id, $c->paciente_id, 'Consentimiento aceptado y firmado');
        $this->dispatch('notify', message: 'Aceptación registrada. Adjunte el formulario firmado.', type: 'success');
    }

    /** Bloque D: el paciente no autoriza el procedimiento */
    public function negar(): void
    {
        $this->authorize('expediente.crear');
        $c = $this->consentimiento();
        $this->exigirEstado($c, 'pendiente_firma');

        $datos = $this->representante($c);
        if ($this->negativaSinFirma) {
            $this->validate([
                'testigo_nombre' => 'required|string|max:150',
                'testigo_cedula' => 'required|string|max:20',
            ], [
                'testigo_nombre.required' => 'Si el paciente se niega a firmar, debe firmar un testigo.',
                'testigo_cedula.required' => 'Escriba la cédula del testigo.',
            ]);
        }

        $c->update($datos + [
            'estado' => 'negado',
            'fecha_negativa' => now(),
            'negativa_sin_firma' => $this->negativaSinFirma,
            'testigo_nombre' => $this->negativaSinFirma ? trim($this->testigo_nombre) : null,
            'testigo_cedula' => $this->negativaSinFirma ? trim($this->testigo_cedula) : null,
        ]);
        app(AuditoriaService::class)->registrar('editar', 'consentimiento', $c->id, $c->paciente_id,
            'Negativa del consentimiento' . ($this->negativaSinFirma ? ' (firma un testigo)' : ''));
        $this->dispatch('notify', message: 'Negativa registrada.', type: 'success');
    }

    /** Bloque E: el paciente retira un consentimiento ya otorgado */
    public function revocar(): void
    {
        $this->authorize('expediente.crear');
        $c = $this->consentimiento();
        $this->exigirEstado($c, 'aceptado');
        $this->validate(['motivo_revocatoria' => 'nullable|string|max:500']);

        $c->update($this->representante($c) + [
            'estado' => 'revocado',
            'fecha_revocatoria' => now(),
            'motivo_revocatoria' => filled($this->motivo_revocatoria) ? trim($this->motivo_revocatoria) : null,
        ]);
        app(AuditoriaService::class)->registrar('editar', 'consentimiento', $c->id, $c->paciente_id, 'Consentimiento revocado por el paciente');
        $this->dispatch('notify', message: 'Revocatoria registrada. Quedó anotada en la historia clínica.', type: 'success');
    }

    /** Copia escaneada o fotografía del formulario firmado en papel */
    public function subirDocumento(): void
    {
        $this->authorize('expediente.crear');
        $c = $this->consentimiento();
        if ($c->estado === 'pendiente_firma') {
            throw ValidationException::withMessages(['documento' => 'Registre primero la aceptación o la negativa.']);
        }

        $this->validate(['documento' => 'required|file|max:' . ArchivoService::maxKb()], [
            'documento.required' => 'Seleccione el archivo firmado.',
            'documento.max' => 'El archivo supera el límite de ' . round(ArchivoService::maxKb() / 1024, 1) . ' MB.',
        ]);
        $mime = $this->documento->getMimeType();
        if (!isset(ArchivoService::MIME_PERMITIDOS[$mime])) {
            throw ValidationException::withMessages(['documento' => 'Formato no permitido. Solo PDF, JPG y PNG.']);
        }

        // Las copias anteriores se conservan: el archivo nuevo no reemplaza físicamente al previo
        $ruta = $this->documento->storeAs("pacientes/{$c->paciente_id}/consentimientos",
            Str::uuid() . '.' . ArchivoService::MIME_PERMITIDOS[$mime], 'private');
        $c->update(['documento_firmado_ruta' => $ruta, 'documento_firmado_mime' => $mime, 'documento_firmado_at' => now()]);
        app(AuditoriaService::class)->registrar('crear', 'consentimiento', $c->id, $c->paciente_id, 'Formulario firmado adjuntado');

        $this->reset('documento');
        $this->dispatch('notify', message: 'Formulario firmado adjuntado.', type: 'success');
    }

    /** Un borrador sin firmar se puede descartar; uno firmado es parte de la historia clínica */
    public function descartar(): void
    {
        $this->authorize('expediente.crear');
        $c = $this->consentimiento();
        $this->exigirEstado($c, 'pendiente_firma');
        abort_unless($c->medico_id === Medico::where('user_id', Auth::id())->value('id'), 403);

        $pacienteId = $c->paciente_id;
        if ($c->grafico_ruta) {
            Storage::disk('private')->delete($c->grafico_ruta);
        }
        app(AuditoriaService::class)->registrar('eliminar', 'consentimiento', $c->id, $pacienteId, "Borrador descartado: {$c->procedimiento}");
        $c->delete();

        session()->flash('notify', ['message' => 'Borrador descartado.', 'type' => 'success']);
        $this->redirect(route('pacientes.ver', $pacienteId), navigate: true);
    }

    public function render()
    {
        $c = $this->consentimiento();

        return view('livewire.ver-consentimiento', [
            'c' => $c,
            'esAutor' => $c->medico_id === Medico::where('user_id', Auth::id())->value('id'),
        ])->layout('layouts.app');
    }
}
