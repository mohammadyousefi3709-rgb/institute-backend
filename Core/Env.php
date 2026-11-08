<?php

declare(strict_types=1);

namespace Core;

use RuntimeException;

class Env
{
    private static array $vars = [];

    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException("Env file not found: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new RuntimeException("Cannot read env file: {$path}");
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $line, 2), 2, '');

            $key = trim($key);
            $value = trim(trim($value), '"\'');

            if ($key === '') {
                continue;
            }

            self::$vars[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::$vars[$key] ?? $default;
    }
}