<?php

namespace App\Services;

use App\Models\Factura;
use App\Models\Medico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Chequeos del servidor para el panel Estado del sistema: respaldos, tareas programadas, cola,
 * integridad de la auditoría, SRI, certificados, disco y configuración pendiente.
 * Cada chequeo devuelve: [titulo, estado ok|aviso|error, detalle, accion?].
 */
class EstadoSistemaService
{
    public const LATIDO_TAREAS = 'sistema.latido_tareas';
    public const LATIDO_COLA = 'sistema.latido_cola';

    public function __construct(private AjustesService $ajustes) {}

    /** @return array<string, array{titulo: string, estado: string, detalle: string, accion?: string}> */
    public function chequeos(): array
    {
        return [
            'respaldo' => $this->respaldo(),
            'tareas' => $this->latido(self::LATIDO_TAREAS, 'Tareas programadas', 3,
                'Recordatorios, respaldos y reintentos del SRI se ejecutan a su hora.',
                'El programador no responde: revise el servicio solarmed-tareas.'),
            'cola' => $this->cola(),
            'auditoria' => $this->auditoria(),
            'sri' => $this->sri(),
            'certificados' => $this->certificados(),
            'inventario' => $this->inventario(),
            'disco' => $this->disco(),
            'configuracion' => $this->configuracion(),
        ];
    }

    private function inventario(): array
    {
        $a = app(InventarioService::class)->alertas();
        $partes = array_filter([
            $a['vencidos'] ? "{$a['vencidos']} " . ($a['vencidos'] === 1 ? 'lote vencido' : 'lotes vencidos') . ' con existencias (dar de baja)' : null,
            $a['por_vencer'] ? "{$a['por_vencer']} " . ($a['por_vencer'] === 1 ? 'lote vence' : 'lotes vencen') . ' en 60 días o menos' : null,
            $a['stock_bajo'] ? "{$a['stock_bajo']} " . ($a['stock_bajo'] === 1 ? 'producto' : 'productos') . ' con stock bajo' : null,
        ]);

        return [
            'titulo' => 'Inventario',
            'estado' => $a['vencidos'] ? 'error' : ($partes ? 'aviso' : 'ok'),
            'detalle' => $partes ? ucfirst(implode('; ', $partes)) . '.' : 'Sin productos vencidos, por vencer ni con stock bajo.',
            'accion' => route('inventario'),
        ];
    }

    /** Peor estado del conjunto, para el aviso del panel de inicio */
    public function resumen(): array
    {
        $chequeos = $this->chequeos();
        $errores = collect($chequeos)->where('estado', 'error')->count();
        $avisos = collect($chequeos)->where('estado', 'aviso')->count();

        return ['errores' => $errores, 'avisos' => $avisos];
    }

    private function respaldo(): array
    {
        $ruta = config('respaldo.ruta');
        $archivos = File::isDirectory($ruta) ? File::glob(rtrim($ruta, '/') . '/solarmed_*.tar.gz*') : [];
        $ultimo = collect($archivos)->sortByDesc(fn ($f) => File::lastModified($f))->first();
        $cifrado = (bool) config('respaldo.password');

        if (!$ultimo) {
            return ['titulo' => 'Respaldos', 'estado' => 'error', 'detalle' => 'No hay ningún respaldo en ' . $ruta . '.'];
        }

        $fecha = Carbon::createFromTimestamp(File::lastModified($ultimo), config('app.timezone'));
        $horas = (int) $fecha->diffInHours(now());
        $detalle = 'Último: ' . $fecha->format('d/m/Y H:i') . ' (' . round(File::size($ultimo) / 1048576, 2) . ' MB, '
            . ($cifrado ? 'cifrado' : 'SIN CIFRAR') . '). ' . count($archivos) . ' respaldos guardados.';

        $estado = $horas > 48 ? 'error' : ($horas > 26 || !$cifrado ? 'aviso' : 'ok');
        if ($horas > 26) {
            $detalle = "El último respaldo tiene {$horas} horas. " . $detalle;
        }

        return ['titulo' => 'Respaldos', 'estado' => $estado, 'detalle' => $detalle];
    }

    private function latido(string $clave, string $titulo, int $minutosMax, string $bien, string $mal): array
    {
        $ultimo = Cache::get($clave);
        if (!$ultimo) {
            return ['titulo' => $titulo, 'estado' => 'aviso', 'detalle' => 'Aún no hay señal. Si el servicio acaba de iniciar, espere unos minutos.'];
        }

        $minutos = (int) Carbon::parse($ultimo)->diffInMinutes(now());

        return $minutos > $minutosMax
            ? ['titulo' => $titulo, 'estado' => 'error', 'detalle' => "{$mal} Última señal hace {$minutos} min."]
            : ['titulo' => $titulo, 'estado' => 'ok', 'detalle' => "{$bien} Última señal hace {$minutos} min."];
    }

    private function cola(): array
    {
        $chequeo = $this->latido(self::LATIDO_COLA, 'Cola de trabajos', 12,
            'Autorizaciones del SRI y correos se procesan en segundo plano.',
            'El trabajador de la cola no procesa: revise el servicio solarmed-cola.');

        try {
            $pendientes = DB::table('jobs')->count();
            $fallidos = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDays(7))->count();
        } catch (\Throwable) {
            return $chequeo;
        }

        if ($fallidos > 0) {
            $chequeo['detalle'] .= " {$fallidos} trabajos fallidos en los últimos 7 días.";
            $chequeo['estado'] = $chequeo['estado'] === 'ok' ? 'aviso' : $chequeo['estado'];
        }
        if ($pendientes > 20) {
            $chequeo['detalle'] .= " {$pendientes} trabajos en espera.";
        }

        return $chequeo;
    }

    private function auditoria(): array
    {
        $r = Cache::get(AuditoriaService::CLAVE_INTEGRIDAD);
        if (!$r) {
            return ['titulo' => 'Registro de auditoría', 'estado' => 'aviso', 'detalle' => 'Aún no se ha verificado la integridad.', 'accion' => route('auditoria')];
        }
        $cuando = Carbon::parse($r['verificado_at']);

        if (!$r['integro']) {
            return ['titulo' => 'Registro de auditoría', 'estado' => 'error', 'accion' => route('auditoria'),
                'detalle' => "Alterado a partir del registro #{$r['primer_error']} (verificado el {$cuando->format('d/m/Y H:i')})."];
        }

        return ['titulo' => 'Registro de auditoría', 'estado' => $cuando->lt(now()->subDays(2)) ? 'aviso' : 'ok', 'accion' => route('auditoria'),
            'detalle' => number_format($r['total']) . " registros íntegros. Verificado el {$cuando->format('d/m/Y H:i')}."];
    }

    private function sri(): array
    {
        $vigentes = Factura::where('estado', '!=', 'anulada');
        $sinEmitir = (clone $vigentes)->where(fn ($q) => $q->whereNull('estado_sri')->orWhereIn('estado_sri', ['pendiente', 'xml_generado', 'firmado']))->count();
        $problemas = (clone $vigentes)->whereIn('estado_sri', ['rechazado', 'no_autorizado'])->count();
        $atascadas = (clone $vigentes)->whereIn('estado_sri', ['enviado', 'error_envio', 'error_autorizacion'])
            ->where('updated_at', '<', now()->subDay())->count();

        $partes = [];
        if ($sinEmitir) $partes[] = "{$sinEmitir} sin emitir";
        if ($problemas) $partes[] = "{$problemas} devueltas o no autorizadas";
        if ($atascadas) $partes[] = "{$atascadas} en proceso hace más de un día";

        return [
            'titulo' => 'Facturación electrónica (SRI)',
            'estado' => $problemas || $atascadas ? 'error' : ($sinEmitir ? 'aviso' : 'ok'),
            'detalle' => $partes ? ucfirst(implode(', ', $partes)) . '.' : 'Todas las facturas vigentes están autorizadas.',
            'accion' => route('facturas'),
        ];
    }

    private function certificados(): array
    {
        $medicos = Medico::where('activo', true)->get();
        $sri = app(SriSignService::class);
        $faltan = [];
        $porVencer = [];
        $vencidos = [];

        foreach ($medicos as $m) {
            if (!$m->p12_path) {
                $faltan[] = $m->nombre_completo;
                continue;
            }
            // Leer el .p12 cuesta: se recuerda un día por médico y archivo
            $vence = Cache::remember("sistema.p12.{$m->id}." . md5($m->p12_path), now()->addDay(),
                fn () => $sri->vencimientoCertificado($m)?->toIso8601String());
            if (!$vence) {
                $porVencer[] = "{$m->nombre_completo} (no se pudo leer)";
                continue;
            }
            $fecha = Carbon::parse($vence);
            if ($fecha->isPast()) {
                $vencidos[] = "{$m->nombre_completo} (venció el {$fecha->format('d/m/Y')})";
            } elseif ($fecha->lt(now()->addDays(30))) {
                $porVencer[] = "{$m->nombre_completo} (vence el {$fecha->format('d/m/Y')})";
            }
        }

        $partes = array_filter([
            $vencidos ? 'Vencidos: ' . implode(', ', $vencidos) : null,
            $porVencer ? 'Por vencer: ' . implode(', ', $porVencer) : null,
            $faltan ? 'Sin certificado .p12: ' . implode(', ', $faltan) : null,
        ]);

        return [
            'titulo' => 'Firma electrónica de los médicos',
            'estado' => $vencidos ? 'error' : ($porVencer || $faltan ? 'aviso' : 'ok'),
            'detalle' => $partes ? implode('. ', $partes) . '.' : 'Todos los médicos activos tienen su certificado vigente.',
            'accion' => route('usuarios'),
        ];
    }

    private function disco(): array
    {
        $libre = @disk_free_space(storage_path());
        $total = @disk_total_space(storage_path());
        if (!$libre || !$total) {
            return ['titulo' => 'Espacio en disco', 'estado' => 'aviso', 'detalle' => 'No se pudo consultar el espacio libre.'];
        }
        $pct = round($libre * 100 / $total);
        $gb = round($libre / 1073741824, 1);

        return [
            'titulo' => 'Espacio en disco',
            'estado' => $gb < 2 ? 'error' : ($pct < 10 ? 'aviso' : 'ok'),
            'detalle' => "{$gb} GB libres ({$pct} %).",
        ];
    }

    private function configuracion(): array
    {
        $pendientes = [];
        if (config('app.debug')) $pendientes[] = 'APP_DEBUG está activo';
        if (!str_starts_with((string) config('app.url'), 'https://')) $pendientes[] = 'la dirección no usa HTTPS (el QR de los certificados no se verifica fuera de la clínica)';
        if (in_array(config('mail.default'), ['log', 'array'], true)) $pendientes[] = 'el correo no está configurado (los recordatorios y facturas no se envían)';
        if (!config('respaldo.password')) $pendientes[] = 'los respaldos no están cifrados (BACKUP_PASSWORD)';
        if (!$this->ajustes->get('establecimiento.unicodigo')) $pendientes[] = 'falta el unicódigo del establecimiento';
        $sinCedula = Medico::where('activo', true)->whereHas('user', fn ($q) => $q->whereNull('cedula')->orWhere('cedula', ''))->count();
        if ($sinCedula) $pendientes[] = "{$sinCedula} " . ($sinCedula === 1 ? 'médico sin cédula' : 'médicos sin cédula');
        $sinRegistro = Medico::where('activo', true)->where(fn ($q) => $q->whereNull('colegiatura')->orWhere('colegiatura', ''))->count();
        if ($sinRegistro) $pendientes[] = "{$sinRegistro} " . ($sinRegistro === 1 ? 'médico sin registro profesional' : 'médicos sin registro profesional');
        $clavesTemporales = User::where('debe_cambiar_password', true)->count();
        if ($clavesTemporales) $pendientes[] = "{$clavesTemporales} " . ($clavesTemporales === 1 ? 'cuenta aún usa' : 'cuentas aún usan') . ' la clave asignada';

        return [
            'titulo' => 'Configuración',
            'estado' => $pendientes ? 'aviso' : 'ok',
            'detalle' => $pendientes ? 'Pendiente: ' . implode('; ', $pendientes) . '.' : 'Todo configurado.',
            'accion' => route('configuracion'),
        ];
    }
}
