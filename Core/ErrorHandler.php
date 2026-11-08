<?php

declare(strict_types=1);

namespace Core;

use Throwable;

final class ErrorHandler
{
    public static function handleException(Throwable $exception): void
    {
        if ($exception instanceof HttpException) {
            Response::error(
                $exception->getMessage(),
                $exception->getStatusCode(),
                $exception->getErrors()
            );
        }

        error_log(sprintf(
            '%s: %s in %s:%d',
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine()
        ));

        $debug = filter_var(Env::get('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);

        Response::error(
            $debug ? $exception->getMessage() : 'خطای داخلی سرور',
            500
        );
    }

    public static function handleError(
    int $severity,
    string $message,
    string $file,
    int $line
): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new \ErrorException(
        $message,
        0,
        $severity,
        $file,
        $line
    );
}
}