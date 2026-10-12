<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Registro de auditoría (AM 5216-A Art. 8: trazabilidad del uso de la información).
 * Solo admite inserciones; cada registro guarda el hash del anterior, así cualquier cambio
 * hecho por fuera de la aplicación se detecta con `php artisan auditoria:verificar`.
 */
class RegistroAcceso extends Model
{
    protected $table = 'registro_accesos';

    public const UPDATED_AT = null;

    public const ACCIONES = [
        'ver' => 'Vio',
        'descargar' => 'Descargó',
        'crear' => 'Creó',
        'editar' => 'Editó',
        'eliminar' => 'Eliminó',
        'enviar' => 'Envió',
        'exportar' => 'Exportó',
        'login' => 'Inició sesión',
        'logout' => 'Cerró sesión',
        'login_fallido' => 'Intento fallido',
        'bloqueo' => 'Cuenta bloqueada',
        'cambiar_password' => 'Cambió contraseña',
        'aceptar_confidencialidad' => 'Aceptó confidencialidad',
        'dar_permiso' => 'Dio permiso',
        'quitar_permiso' => 'Quitó permiso',
        'auditar' => 'Auditó',
    ];

    /** Acciones de seguridad de la cuenta (no tocan datos clínicos) */
    public const ACCIONES_SESION = ['login', 'logout', 'login_fallido', 'bloqueo', 'cambiar_password', 'aceptar_confidencialidad'];

    /** Campos que entran al sello, en orden fijo */
    private const CAMPOS_SELLO = ['user_id', 'accion', 'recurso', 'recurso_id', 'paciente_id', 'detalle', 'ip', 'user_agent', 'created_at'];

    protected $fillable = ['user_id', 'accion', 'recurso', 'recurso_id', 'paciente_id', 'detalle', 'ip', 'user_agent', 'created_at', 'hash'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('El registro de auditoría no se puede modificar.'));
        static::deleting(fn () => throw new LogicException('El registro de auditoría no se puede eliminar.'));
    }

    public function user(): BelongsTo     { return $this->belongsTo(User::class); }
    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }

    /** SHA-256 del registro encadenado al hash del registro anterior */
    public static function calcularHash(?string $anterior, array $datos): string
    {
        $campos = [];
        foreach (self::CAMPOS_SELLO as $campo) {
            $valor = $datos[$campo] ?? null;
            if ($valor instanceof \DateTimeInterface) {
                $valor = $valor->format('Y-m-d H:i:s');
            }
            $campos[$campo] = $valor === null ? null : (string) $valor;
        }

        return hash('sha256', ($anterior ?? '') . '|' . json_encode($campos, JSON_UNESCAPED_UNICODE));
    }

    /** Filtros de la pantalla de auditoría, compartidos con la exportación */
    public function scopeFiltrar(Builder $query, array $f): Builder
    {
        if (($f['paciente'] ?? '') !== '') {
            $texto = $f['paciente'];
            $query->whereHas('paciente', fn ($q) => $q
                ->where('cedula', 'like', "%{$texto}%")
                ->orWhere('nombres', 'like', "%{$texto}%")
                ->orWhere('apellidos', 'like', "%{$texto}%"));
        }
        if (($f['usuario'] ?? '') !== '') {
            $query->where('user_id', (int) $f['usuario']);
        }
        if (array_key_exists($f['accion'] ?? '', self::ACCIONES)) {
            $query->where('accion', $f['accion']);
        } elseif (($f['accion'] ?? '') === 'sesiones') {
            $query->whereIn('accion', self::ACCIONES_SESION);
        }
        if (($f['desde'] ?? '') !== '' && strtotime($f['desde'])) {
            $query->whereDate('created_at', '>=', $f['desde']);
        }
        if (($f['hasta'] ?? '') !== '' && strtotime($f['hasta'])) {
            $query->whereDate('created_at', '<=', $f['hasta']);
        }

        return $query;
    }
}
