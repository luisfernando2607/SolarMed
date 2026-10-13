<?php

namespace App\Models;

use App\Support\NumeroALetras;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Medicamento prescrito por DCI (ACESS-2023-0030 Art. 5 c) */
class RecetaItem extends Model
{
    protected $table = 'receta_items';

    protected $fillable = [
        'receta_id', 'orden', 'dci', 'forma_farmaceutica', 'concentracion', 'via',
        'cantidad', 'dosis', 'frecuencia', 'duracion', 'indicacion', 'antimicrobiano',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'antimicrobiano' => 'boolean',
    ];

    public function receta(): BelongsTo { return $this->belongsTo(Receta::class); }

    /** "Amoxicilina 500 mg · cápsula" */
    public function descripcion(): string
    {
        return trim("{$this->dci} {$this->concentracion}") . " · {$this->forma_farmaceutica}";
    }

    public function cantidadEnLetras(): string
    {
        return NumeroALetras::conNumero((int) $this->cantidad);
    }
}
