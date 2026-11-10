<?php

declare(strict_types=1);

namespace Helpers;

use Core\Env;
use DomainException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

final class Token
{
    private const ALGORITHM = 'HS256';

    public static function generate(int $userId, string $role): string
    {
        $issuedAt = time();

        $payload = [
            'user_id' => $userId,
            'role' => $role,
            'iat' => $issuedAt,
            'exp' => $issuedAt + (int) Env::get('JWT_TTL', '28800'),
        ];

        return JWT::encode($payload, self::secret(), self::ALGORITHM);
    }

    public static function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key(self::secret(), self::ALGORITHM));
        } catch (UnexpectedValueException | DomainException | InvalidArgumentException) {
            return null;
        }

        return (array) $decoded;
    }

    private static function secret(): string
    {
        $secret = Env::get('JWT_SECRET', '');

        if (strlen($secret) < 32) {
            throw new RuntimeException('JWT_SECRET is missing or too short in .env');
        }

        return $secret;
    }
}