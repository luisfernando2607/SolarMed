<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de negocio al registrar un turno. Su mensaje es seguro para mostrar al usuario.
 */
class TurnoException extends RuntimeException
{
}
