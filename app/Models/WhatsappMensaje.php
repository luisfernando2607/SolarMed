<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMensaje extends Model
{
    protected $table = 'whatsapp_mensajes';

    public const TIPOS = [
        'recordatorio_cita' => 'Recordatorio de cita',
        'confirmacion_reserva' => 'Confirmación de reserva',
        'encuesta' => 'Encuesta',
        'prueba' => 'Prueba',
    ];

    public const ESTADOS = [
        'pendiente' => ['En cola', 'bg-gray-100 text-gray-600'],
        'enviado' => ['Enviado', 'bg-sky-50 text-sky-700'],
        'entregado' => ['Entregado', 'bg-emerald-50 text-emerald-700'],
        'leido' => ['Leído', 'bg-emerald-100 text-emerald-800'],
        'fallido' => ['Fallido', 'bg-red-50 text-red-700'],
    ];

    /** Orden de avance: un estado nunca retrocede (Meta puede enviar los avisos desordenados o repetidos) */
    public const RANGO = ['pendiente' => 0, 'enviado' => 1, 'entregado' => 2, 'leido' => 3];

    protected $fillable = [
        'paciente_id', 'cita_id', 'encuesta_id', 'user_id', 'tipo', 'telefono', 'plantilla', 'parametros', 'estado', 'wamid',
        'error_codigo', 'error_mensaje', 'intentos', 'enviado_at', 'entregado_at', 'leido_at',
        'respuesta_texto', 'respuesta_payload', 'respondido_at', 'respuesta_revisada_at',
    ];

    protected $casts = [
        'parametros' => 'array',
        'enviado_at' => 'datetime', 'entregado_at' => 'datetime', 'leido_at' => 'datetime',
        'respondido_at' => 'datetime', 'respuesta_revisada_at' => 'datetime',
    ];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function cita(): BelongsTo     { return $this->belongsTo(Cita::class); }
    public function encuesta(): BelongsTo { return $this->belongsTo(Encuesta::class); }
    public function user(): BelongsTo     { return $this->belongsTo(User::class); }

    /** Aplica un aviso de estado de Meta sin retroceder: leído > entregado > enviado; un fallo no pisa una entrega */
    public function avanzarEstado(string $nuevo, ?\DateTimeInterface $cuando = null, ?string $codigo = null, ?string $error = null): bool
    {
        $cuando ??= now();

        if ($nuevo === 'fallido') {
            if (in_array($this->estado, ['entregado', 'leido', 'fallido'], true)) {
                return false;
            }
            $this->fill(['estado' => 'fallido', 'error_codigo' => $codigo, 'error_mensaje' => $error ? mb_substr($error, 0, 255) : null])->save();

            return true;
        }

        if (!isset(self::RANGO[$nuevo]) || $this->estado === 'fallido' && $nuevo === 'enviado'
            || (self::RANGO[$this->estado] ?? -1) >= self::RANGO[$nuevo]) {
            return false;
        }

        $campos = ['estado' => $nuevo];
        if (!$this->enviado_at) {
            $campos['enviado_at'] = $cuando;
        }
        if ($nuevo === 'entregado' || $nuevo === 'leido') {
            $campos['entregado_at'] = $this->entregado_at ?? $cuando;
        }
        if ($nuevo === 'leido') {
            $campos['leido_at'] = $this->leido_at ?? $cuando;
        }
        $this->fill($campos)->save();

        return true;
    }
}
