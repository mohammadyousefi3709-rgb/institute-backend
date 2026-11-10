<?php

declare(strict_types=1);

namespace Middlewares;

use Closure;
use Core\HttpException;
use Core\Request;

final class RoleMiddleware
{
    public static function allow(string ...$roles): Closure
    {
        return static function (Request $request) use ($roles): void {
            if ($request->user === null) {
                throw new HttpException('احراز هویت لازم است', 401);
            }

            if (!in_array($request->user['role'], $roles, true)) {
                throw new HttpException('شما اجازه‌ی دسترسی به این بخش را ندارید', 403);
            }
        };
    }
}