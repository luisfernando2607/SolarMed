<?php

namespace App\Services;

use App\Models\ControlPrenatal;
use Carbon\Carbon;

/**
 * Cálculos y alertas del control prenatal. Las alertas son apoyo a la decisión clínica, no diagnósticos.
 */
class PrenatalService
{
    /** Fecha probable de parto por regla de Naegele (FUM + 280 días) */
    public function fpp(Carbon $fum): Carbon
    {
        return $fum->copy()->addDays(280);
    }

    /** @return array{0: ?int, 1: ?int} [sistólica, diastólica] */
    public function presion(?string $pa): array
    {
        if ($pa && preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $pa, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [null, null];
    }

    /**
     * @return array<int, array{nivel: 'alta'|'media', mensaje: string}>
     */
    public function alertas(ControlPrenatal $control): array
    {
        $alertas = [];
        $semanas = $control->semanas_gestacion;
        $a = app(AjustesService::class);
        [$paS, $paD] = [(int) $a->get('prenatal.pa_sistolica'), (int) $a->get('prenatal.pa_diastolica')];
        [$sevS, $sevD] = [(int) $a->get('prenatal.pa_severa_sistolica'), (int) $a->get('prenatal.pa_severa_diastolica')];
        [$fcfMin, $fcfMax] = [(int) $a->get('prenatal.fcf_min'), (int) $a->get('prenatal.fcf_max')];
        $tolAu = (float) $a->get('prenatal.au_tolerancia_cm');
        $semanaMov = (int) $a->get('prenatal.movimientos_desde_semana');
        [$sistolica, $diastolica] = $this->presion($control->presion_arterial);

        if ($sistolica !== null) {
            if ($sistolica >= $sevS || $diastolica >= $sevD) {
                $alertas[] = ['nivel' => 'alta', 'mensaje' => "PA {$control->presion_arterial}: hipertensión severa"];
            } elseif ($sistolica >= $paS || $diastolica >= $paD) {
                $alertas[] = ['nivel' => 'alta', 'mensaje' => "PA {$control->presion_arterial}: hipertensión, descartar preeclampsia"];
            }
        }

        if ($control->fcf !== null && ($control->fcf < $fcfMin || $control->fcf > $fcfMax)) {
            $alertas[] = ['nivel' => 'alta', 'mensaje' => "FCF {$control->fcf} lpm fuera de {$fcfMin}–{$fcfMax}"];
        }

        // Entre las 20 y 36 semanas la altura uterina (cm) ≈ semanas de gestación ± tolerancia
        if ($control->altura_uterina !== null && $semanas !== null && $semanas >= 20 && $semanas <= 36
            && abs((float) $control->altura_uterina - $semanas) > $tolAu) {
            $alertas[] = ['nivel' => 'media', 'mensaje' => "Altura uterina {$control->altura_uterina} cm no acorde a {$semanas} semanas"];
        }

        if ($control->movimientos_fetales === false && $semanas !== null && $semanas >= $semanaMov) {
            $alertas[] = ['nivel' => 'alta', 'mensaje' => "Sin movimientos fetales después de las {$semanaMov} semanas"];
        }

        if ($control->edemas && $control->edemas !== 'Ausentes' && $sistolica !== null && ($sistolica >= $paS || $diastolica >= $paD)) {
            $alertas[] = ['nivel' => 'alta', 'mensaje' => 'Edemas con hipertensión'];
        }

        return $alertas;
    }
}
