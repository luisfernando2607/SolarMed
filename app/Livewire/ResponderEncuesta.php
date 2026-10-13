<?php

namespace App\Livewire;

use App\Models\Encuesta;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** Página pública (sin sesión) para responder la encuesta con el enlace de un solo uso */
class ResponderEncuesta extends Component
{
    #[Locked]
    public string $token;
    #[Locked]
    public bool $enDispositivo = false;

    public array $respuestas = [];
    public $recomendacion = null;
    public string $comentario = '';
    public bool $enviada = false;

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->enDispositivo = request()->boolean('dispositivo');
        abort_unless(Encuesta::where('token', $token)->exists(), 404);
    }

    private function encuesta(): Encuesta
    {
        return Encuesta::with('medico', 'especialidad')->where('token', $this->token)->firstOrFail();
    }

    public function enviar(): void
    {
        $clave = 'encuesta:' . request()->ip();
        if (RateLimiter::tooManyAttempts($clave, 20)) {
            $this->addError('recomendacion', 'Demasiados intentos. Intente más tarde.');
            return;
        }
        RateLimiter::hit($clave, 3600);

        $e = $this->encuesta();
        if (!$e->disponible()) {
            return;
        }

        $reglas = ['recomendacion' => 'required|integer|between:0,10', 'comentario' => 'nullable|string|max:1000'];
        foreach (array_keys(Encuesta::PREGUNTAS) as $campo) {
            $reglas["respuestas.{$campo}"] = 'required|integer|between:1,5';
        }
        $this->validate($reglas, [
            'respuestas.*.required' => 'Elija una calificación.',
            'recomendacion.required' => 'Elija un número del 0 al 10.',
        ]);

        $e->update(array_map('intval', $this->respuestas) + [
            'recomendacion' => (int) $this->recomendacion,
            'comentario' => trim($this->comentario) ?: null,
            'respondida_at' => now(),
            'canal_respuesta' => $this->enDispositivo ? 'dispositivo' : 'enlace',
        ]);
        $this->enviada = true;
    }

    public function render()
    {
        return view('livewire.responder-encuesta', ['encuesta' => $this->encuesta()])->layout('layouts.guest');
    }
}
