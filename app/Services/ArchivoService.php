<?php
namespace App\Services;

use App\Models\PacienteArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArchivoService {

    const CATEGORIAS = ['laboratorio','referencia','ecografia','foto','otro'];
    const MIME_PERMITIDOS = ['application/pdf','image/jpeg','image/jpg','image/png'];
    const MAX_KB = 5120; // 5 MB

    public function subir(
        UploadedFile $archivo,
        int $pacienteId,
        int $medicoId,
        string $categoria,
        ?int $consultaId  = null,
        ?int $ecografiaId = null,
        ?string $descripcion = null
    ): PacienteArchivo {
        $this->validar($archivo);

        $uuid    = Str::uuid();
        $ext     = $archivo->getClientOriginalExtension();
        $nombre  = "{$uuid}.{$ext}";
        $carpeta = "pacientes/{$pacienteId}/{$categoria}";
        $ruta    = "{$carpeta}/{$nombre}";

        Storage::disk('private')->putFileAs($carpeta, $archivo, $nombre);

        return PacienteArchivo::create([
            'paciente_id'       => $pacienteId,
            'medico_id'         => $medicoId,
            'consulta_id'       => $consultaId,
            'ecografia_id'      => $ecografiaId,
            'categoria'         => $categoria,
            'nombre_original'   => $archivo->getClientOriginalName(),
            'nombre_almacenado' => $nombre,
            'ruta'              => $ruta,
            'mime_type'         => $archivo->getMimeType(),
            'tamanio_kb'        => (int) ceil($archivo->getSize() / 1024),
            'descripcion'       => $descripcion,
        ]);
    }

    private function validar(UploadedFile $archivo): void {
        if (!in_array($archivo->getMimeType(), self::MIME_PERMITIDOS)) {
            throw new \Exception('Formato no permitido. Solo PDF, JPG y PNG.');
        }
        if (ceil($archivo->getSize() / 1024) > self::MAX_KB) {
            throw new \Exception('El archivo supera el límite de 5 MB.');
        }
    }
}
