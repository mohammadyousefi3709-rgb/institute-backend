<?php

declare(strict_types=1);

namespace Helpers;

use Core\Request;

final class Pagination
{
    private const DEFAULT_PER_PAGE = 10;
    private const MAX_PER_PAGE = 100;
    private const MAX_PAGE = 1000000;

    public static function fromRequest(Request $request): array
    {
        $page = self::toInt($request->query('page'), 1, self::MAX_PAGE);
        $perPage = self::toInt($request->query('per_page'), self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE);

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    private static function toInt(mixed $value, int $default, int $max): int
    {
        if (!is_string($value) || preg_match('/^[1-9][0-9]{0,8}$/', $value) !== 1) {
            return $default;
        }

        return min((int) $value, $max);
    }
}