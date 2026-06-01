<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cie10Code extends Model
{
    protected $table = 'cie10_codes';

    protected $fillable = [
        'codigo', 'descripcion', 'categoria',
        'categoria_descripcion', 'capitulo', 'capitulo_descripcion',
    ];
}
