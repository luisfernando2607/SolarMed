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
        [$sistolica, $diastolica] = $this->presion($control->presion_arterial);

        if ($sistolica !== null) {
            if ($sistolica >= 160 || $diastolica >= 110) {
                $alertas[] = ['nivel' => 'alta', 'mensaje' => "PA {$control->presion_arterial}: hipertensión severa"];
            } elseif ($sistolica >= 140 || $diastolica >= 90) {
                $alertas[] = ['nivel' => 'alta', 'mensaje' => "PA {$control->presion_arterial}: hipertensión, descartar preeclampsia"];
            }
        }

        if ($control->fcf !== null && ($control->fcf < 110 || $control->fcf > 160)) {
            $alertas[] = ['nivel' => 'alta', 'mensaje' => "FCF {$control->fcf} lpm fuera de 110–160"];
        }

        // Entre las 20 y 36 semanas la altura uterina (cm) ≈ semanas de gestación ± 3
        if ($control->altura_uterina !== null && $semanas !== null && $semanas >= 20 && $semanas <= 36
            && abs((float) $control->altura_uterina - $semanas) > 3) {
            $alertas[] = ['nivel' => 'media', 'mensaje' => "Altura uterina {$control->altura_uterina} cm no acorde a {$semanas} semanas"];
        }

        if ($control->movimientos_fetales === false && $semanas !== null && $semanas >= 28) {
            $alertas[] = ['nivel' => 'alta', 'mensaje' => 'Sin movimientos fetales después de las 28 semanas'];
        }

        if ($control->edemas && $control->edemas !== 'Ausentes' && $sistolica !== null && ($sistolica >= 140 || $diastolica >= 90)) {
            $alertas[] = ['nivel' => 'alta', 'mensaje' => 'Edemas con hipertensión'];
        }

        return $alertas;
    }
}
