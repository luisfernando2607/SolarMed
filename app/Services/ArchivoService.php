<?php
namespace App\Services;

use App\Models\PacienteArchivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ArchivoService {

    const CATEGORIAS = [
        'laboratorio' => 'Laboratorio',
        'ecografia' => 'Ecografía / imagen',
        'referencia' => 'Referencia / interconsulta',
        'foto' => 'Fotografía clínica',
        'otro' => 'Otro',
    ];

    /** Tipos permitidos según el contenido real del archivo (no la extensión que envía el navegador) */
    const MIME_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /** Tamaño máximo en KB (ARCHIVO_MAX_KB en .env) */
    public static function maxKb(): int
    {
        return (int) config('clinica.max_archivo_kb', 5120);
    }

    public function subir(
        UploadedFile $archivo,
        int $pacienteId,
        ?int $medicoId,
        ?int $userId,
        string $categoria,
        ?int $consultaId = null,
        ?int $ecografiaId = null,
        ?string $descripcion = null
    ): PacienteArchivo {
        $mime = $archivo->getMimeType();
        if (!isset(self::MIME_PERMITIDOS[$mime])) {
            throw ValidationException::withMessages(['archivo' => 'Formato no permitido. Solo PDF, JPG y PNG.']);
        }
        if (ceil($archivo->getSize() / 1024) > self::maxKb()) {
            throw ValidationException::withMessages(['archivo' => 'El archivo supera el límite de ' . round(self::maxKb() / 1024, 1) . ' MB.']);
        }
        if (!array_key_exists($categoria, self::CATEGORIAS)) {
            throw ValidationException::withMessages(['categoria' => 'Categoría no válida.']);
        }

        $nombre  = Str::uuid() . '.' . self::MIME_PERMITIDOS[$mime];
        $carpeta = "pacientes/{$pacienteId}/{$categoria}";

        Storage::disk('private')->putFileAs($carpeta, $archivo, $nombre);

        return PacienteArchivo::create([
            'paciente_id'       => $pacienteId,
            'medico_id'         => $medicoId,
            'user_id'           => $userId,
            'consulta_id'       => $consultaId,
            'ecografia_id'      => $ecografiaId,
            'categoria'         => $categoria,
            'nombre_original'   => mb_substr($archivo->getClientOriginalName(), 0, 255),
            'nombre_almacenado' => $nombre,
            'ruta'              => "{$carpeta}/{$nombre}",
            'mime_type'         => $mime,
            'tamanio_kb'        => (int) ceil($archivo->getSize() / 1024),
            'descripcion'       => $descripcion,
        ]);
    }

    public function eliminar(PacienteArchivo $archivo): void
    {
        Storage::disk('private')->delete($archivo->ruta);
        $archivo->delete();
    }
}
