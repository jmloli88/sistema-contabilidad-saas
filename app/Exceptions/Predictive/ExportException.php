<?php

namespace App\Exceptions\Predictive;

class ExportException extends PredictiveException
{
    public function __construct(string $message = "Export operation failed", int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}