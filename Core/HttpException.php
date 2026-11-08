<?php

declare(strict_types=1);

namespace Core;

use Exception;

final class HttpException extends Exception
{
    public function __construct(
        string $message = '',
        private readonly int $statusCode = 400,
        private readonly array $errors = []
    ) {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}