<?php

namespace App\Policies;

use App\Models\ExpedienteConsulta;
use App\Models\User;

class ExpedienteConsultaPolicy
{
    /**
     * Una consulta clínica solo la puede corregir el médico que la registró.
     * Cada corrección queda guardada en el historial de versiones.
     */
    public function update(User $user, ExpedienteConsulta $consulta): bool
    {
        return $user->can('expediente.crear')
            && $consulta->medico?->user_id === $user->id;
    }
}
