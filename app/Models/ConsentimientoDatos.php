<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Constancia de que la persona aceptó el aviso de privacidad al usar un formulario público */
class ConsentimientoDatos extends Model
{
    /** Cambie la versión cada vez que se modifique el texto de resources/views/privacidad.blade.php */
    public const VERSION_AVISO = '2026-11.2';

    protected $table = 'consentimientos_datos';

    public const UPDATED_AT = null;

    protected $fillable = ['paciente_id', 'cedula', 'origen', 'version_aviso', 'ip', 'user_agent'];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }

    public static function registrar(string $origen, string $cedula, ?int $pacienteId): self
    {
        return self::create([
            'paciente_id' => $pacienteId,
            'cedula' => mb_substr($cedula, 0, 13),
            'origen' => $origen,
            'version_aviso' => self::VERSION_AVISO,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
        ]);
    }
}
