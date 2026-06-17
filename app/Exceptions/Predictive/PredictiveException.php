<?php

namespace App\Exceptions\Predictive;

use Exception;

class PredictiveException extends Exception
{
    public function __construct(string $message = "Predictive operation failed", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}