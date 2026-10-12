<?php

namespace App\Livewire;

use App\Services\AjustesService;
use App\Services\AuditoriaService;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\WithFileUploads;

class ConfiguracionClinica extends Component
{
    use WithFileUploads;

    // Clínica (marca)
    public $marca_nombre, $marca_subtitulo, $marca_direccion, $marca_telefono, $marca_email, $marca_color;
    public $logo;

    // Catálogo de exámenes
    public string $examenesLaboratorio = '';
    public string $examenesImagen = '';

    // Agenda
    public string $tiposTexto = '';
    public string $duracionesTexto = '';
    public $duracion_defecto;
    public $hora_inicio;
    public $hora_fin;
    public array $dias = [];

    // Alertas prenatales
    public $pa_sistolica, $pa_diastolica, $pa_severa_sistolica, $pa_severa_diastolica;
    public $fcf_min, $fcf_max, $au_tolerancia_cm, $movimientos_desde_semana, $dias_sin_control, $aviso_fpp_dias;

    // Tareas
    public $hora_recordatorios;
    public $hora_respaldo;

    public const DIAS = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 0 => 'Dom'];
    private const PRENATAL = ['pa_sistolica', 'pa_diastolica', 'pa_severa_sistolica', 'pa_severa_diastolica', 'fcf_min', 'fcf_max',
        'au_tolerancia_cm', 'movimientos_desde_semana', 'dias_sin_control', 'aviso_fpp_dias'];

    public function mount(): void
    {
        $a = app(AjustesService::class);
        foreach (['nombre', 'subtitulo', 'direccion', 'telefono', 'email', 'color'] as $campo) {
            $this->{"marca_{$campo}"} = $a->get("marca.{$campo}");
        }
        $this->examenesLaboratorio = implode("\n", $a->get('examenes.laboratorio'));
        $this->examenesImagen = implode("\n", $a->get('examenes.imagen'));
        $this->tiposTexto = implode("\n", $a->get('agenda.tipos'));
        $this->duracionesTexto = implode(', ', $a->get('agenda.duraciones'));
        $this->duracion_defecto = $a->get('agenda.duracion_defecto');
        $this->hora_inicio = $a->get('agenda.hora_inicio');
        $this->hora_fin = $a->get('agenda.hora_fin');
        $this->dias = array_map('strval', $a->get('agenda.dias_laborables'));
        foreach (self::PRENATAL as $campo) {
            $this->{$campo} = $a->get("prenatal.{$campo}");
        }
        $this->hora_recordatorios = $a->get('tareas.hora_recordatorios');
        $this->hora_respaldo = $a->get('tareas.hora_respaldo');
    }

    public function restaurarPrenatal(): void
    {
        foreach (self::PRENATAL as $campo) {
            $this->{$campo} = AjustesService::DEFECTOS["prenatal.{$campo}"];
        }
    }

    public function guardar(): void
    {
        $this->authorize('configuracion.editar');

        $tipos = collect(preg_split('/\R/', $this->tiposTexto))->map(fn ($t) => mb_substr(trim($t), 0, 60))->filter()->unique()->values()->all();
        $duraciones = collect(preg_split('/[\s,;]+/', $this->duracionesTexto))->filter(fn ($d) => ctype_digit($d))->map(fn ($d) => (int) $d)
            ->filter(fn ($d) => $d >= 5 && $d <= 480)->unique()->sort()->values()->all();

        $this->validate([
            'examenesLaboratorio' => 'required|string|max:5000',
            'examenesImagen' => 'required|string|max:5000',
            'tiposTexto' => 'required|string|max:2000',
            'duracionesTexto' => 'required|string|max:200',
            'duracion_defecto' => 'required|integer',
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'dias' => 'required|array|min:1',
            'dias.*' => 'in:0,1,2,3,4,5,6',
            'pa_sistolica' => 'required|integer|between:100,200',
            'pa_diastolica' => 'required|integer|between:60,130',
            'pa_severa_sistolica' => 'required|integer|between:120,250|gt:pa_sistolica',
            'pa_severa_diastolica' => 'required|integer|between:80,150|gt:pa_diastolica',
            'fcf_min' => 'required|integer|between:60,140',
            'fcf_max' => 'required|integer|between:140,220|gt:fcf_min',
            'au_tolerancia_cm' => 'required|numeric|between:1,10',
            'movimientos_desde_semana' => 'required|integer|between:16,40',
            'dias_sin_control' => 'required|integer|between:7,90',
            'aviso_fpp_dias' => 'required|integer|between:1,60',
            'marca_nombre' => 'required|string|max:80',
            'marca_subtitulo' => 'nullable|string|max:120',
            'marca_direccion' => 'nullable|string|max:200',
            'marca_telefono' => 'nullable|string|max:40',
            'marca_email' => 'nullable|email|max:120',
            'marca_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:1024|dimensions:min_width=64,min_height=64,max_width=3000,max_height=3000',
            'hora_recordatorios' => ['required', 'date_format:H:i'],
            'hora_respaldo' => ['required', 'date_format:H:i'],
        ], [
            'hora_fin.after' => 'La hora de cierre debe ser posterior a la de inicio.',
            'dias.required' => 'Seleccione al menos un día de atención.',
            'pa_severa_sistolica.gt' => 'Debe ser mayor que el umbral de hipertensión.',
            'pa_severa_diastolica.gt' => 'Debe ser mayor que el umbral de hipertensión.',
            'fcf_max.gt' => 'Debe ser mayor que el mínimo.',
            'logo.image' => 'El logo debe ser una imagen PNG o JPG.',
            'logo.max' => 'El logo no debe superar 1 MB.',
            'logo.dimensions' => 'El logo debe medir entre 64 y 3000 píxeles por lado.',
        ]);

        if (!$tipos) {
            $this->addError('tiposTexto', 'Agregue al menos un tipo de cita.');
            return;
        }
        if (!$duraciones) {
            $this->addError('duracionesTexto', 'Ingrese duraciones en minutos separadas por coma, por ejemplo: 15, 20, 30.');
            return;
        }
        if (!in_array((int) $this->duracion_defecto, $duraciones, true)) {
            $this->addError('duracion_defecto', 'La duración por defecto debe estar en la lista de duraciones.');
            return;
        }

        $lineas = fn (string $t) => collect(preg_split('/\R/', $t))->map(fn ($x) => mb_substr(trim($x), 0, 80))->filter()->unique()->values()->all();
        $valores = [
            'examenes.laboratorio' => $lineas($this->examenesLaboratorio),
            'examenes.imagen' => $lineas($this->examenesImagen),
            'marca.nombre' => trim($this->marca_nombre),
            'marca.subtitulo' => trim((string) $this->marca_subtitulo),
            'marca.direccion' => trim((string) $this->marca_direccion),
            'marca.telefono' => trim((string) $this->marca_telefono),
            'marca.email' => trim((string) $this->marca_email),
            'marca.color' => $this->marca_color,
            'agenda.tipos' => $tipos,
            'agenda.duraciones' => $duraciones,
            'agenda.duracion_defecto' => (int) $this->duracion_defecto,
            'agenda.hora_inicio' => $this->hora_inicio,
            'agenda.hora_fin' => $this->hora_fin,
            'agenda.dias_laborables' => array_values(array_map('intval', $this->dias)),
            'tareas.hora_recordatorios' => $this->hora_recordatorios,
            'tareas.hora_respaldo' => $this->hora_respaldo,
        ];
        foreach (self::PRENATAL as $campo) {
            $valores["prenatal.{$campo}"] = $campo === 'au_tolerancia_cm' ? (float) $this->{$campo} : (int) $this->{$campo};
        }

        if ($this->logo) {
            $valores += $this->guardarLogo();
            $this->reset('logo');
        }

        app(AjustesService::class)->guardar($valores);
        app(AuditoriaService::class)->registrar('editar', 'configuracion', null, null, 'Configuración de agenda, alertas y tareas');

        $this->tiposTexto = implode("\n", $tipos);
        $this->duracionesTexto = implode(', ', $duraciones);
        $this->dispatch('notify', message: 'Configuración guardada.', type: 'success');
    }

    /** Valida el logo apenas se selecciona, para mostrar el error antes de guardar */
    public function updatedLogo(): void
    {
        $this->validateOnly('logo', [
            'logo' => 'nullable|image|mimes:png,jpg,jpeg|max:1024|dimensions:min_width=64,min_height=64,max_width=3000,max_height=3000',
        ], [
            'logo.image' => 'El logo debe ser una imagen PNG o JPG.',
            'logo.mimes' => 'El logo debe ser una imagen PNG o JPG.',
            'logo.max' => 'El logo no debe superar 1 MB.',
            'logo.dimensions' => 'El logo debe medir entre 64 y 3000 píxeles por lado.',
        ]);
    }

    public function quitarLogo(): void
    {
        $this->authorize('configuracion.editar');
        $this->borrarLogosPropios();
        app(AjustesService::class)->guardar(['marca.logo' => null, 'marca.logo_bn' => null]);
        $this->dispatch('notify', message: 'Se restauró el logo original.', type: 'success');
    }

    /** Guarda el logo en public/marca y genera una versión en blanco y negro para los tickets térmicos */
    private function guardarLogo(): array
    {
        $this->borrarLogosPropios();
        $dir = public_path('marca');
        File::ensureDirectoryExists($dir);

        $ext = $this->logo->getMimeType() === 'image/png' ? 'png' : 'jpg';
        $marca = now()->format('YmdHis');
        $nombre = "logo-{$marca}.{$ext}";
        File::copy($this->logo->getRealPath(), "{$dir}/{$nombre}");

        $bn = "logo-bn-{$marca}.png";
        $img = @imagecreatefromstring(File::get("{$dir}/{$nombre}"));
        if ($img) {
            // Fondo blanco para transparencias y escala de grises
            $lienzo = imagecreatetruecolor(imagesx($img), imagesy($img));
            imagefill($lienzo, 0, 0, imagecolorallocate($lienzo, 255, 255, 255));
            imagecopy($lienzo, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
            imagefilter($lienzo, IMG_FILTER_GRAYSCALE);
            imagepng($lienzo, "{$dir}/{$bn}");
            imagedestroy($img);
            imagedestroy($lienzo);
        } else {
            File::copy("{$dir}/{$nombre}", "{$dir}/{$bn}");
        }

        return ['marca.logo' => "marca/{$nombre}", 'marca.logo_bn' => "marca/{$bn}"];
    }

    private function borrarLogosPropios(): void
    {
        foreach (['marca.logo', 'marca.logo_bn'] as $clave) {
            $ruta = app(AjustesService::class)->get($clave);
            if ($ruta && str_starts_with($ruta, 'marca/')) {
                File::delete(public_path($ruta));
            }
        }
    }

    /** Contraste del texto blanco sobre el color elegido (botones), para avisar si es muy claro */
    public function getContrasteColorProperty(): ?float
    {
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $this->marca_color)) {
            return null;
        }
        $lin = function (int $c) { $c /= 255; return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4; };
        [$r, $g, $b] = sscanf($this->marca_color, '#%02x%02x%02x');
        return round(1.05 / (0.2126 * $lin($r) + 0.7152 * $lin($g) + 0.0722 * $lin($b) + 0.05), 1);
    }

    public function render()
    {
        return view('livewire.configuracion-clinica')->layout('layouts.app');
    }
}
