<?php

declare(strict_types=1);

namespace Middlewares;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Helpers\Token;

final class AuthMiddleware
{
    public static function handle(Request $request): void
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new HttpException('احراز هویت لازم است', 401);
        }

        $payload = Token::verify($token);

        if ($payload === null || !isset($payload['user_id'])) {
            throw new HttpException('توکن نامعتبر یا منقضی شده است', 401);
        }

        $statement = Database::connection()->prepare(
            'SELECT user_id, username, fullname, role
             FROM users
             WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => (int) $payload['user_id']]);
        $user = $statement->fetch();

        if ($user === false) {
            throw new HttpException('کاربر یافت نشد', 401);
        }

        $request->user = $user;
    }
}