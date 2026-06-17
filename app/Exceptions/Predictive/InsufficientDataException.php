<?php

namespace App\Exceptions\Predictive;

use Exception;

class InsufficientDataException extends Exception
{
    public function __construct(string $dataType, int $required, int $available)
    {
        parent::__construct(
            "Datos insuficientes para {$dataType}. Requeridos: {$required} meses, Disponibles: {$available} meses"
        );
    }
}