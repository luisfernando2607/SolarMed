<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ecografia extends Model {
    protected $fillable = [
        'paciente_id','medico_id','consulta_id','fecha','indicacion',
        'semanas_gestacion','fum','fpp','presentacion','lcf','placenta',
        'liquido_amniotico','dbp','cc','ca','lf','peso_fetal_estimado',
        'conclusion','imagen_path','pdf_path',
    ];
    protected $casts = ['fecha' => 'date', 'fum' => 'date', 'fpp' => 'date'];

    public function paciente(): BelongsTo { return $this->belongsTo(Paciente::class); }
    public function medico(): BelongsTo   { return $this->belongsTo(Medico::class); }
}
