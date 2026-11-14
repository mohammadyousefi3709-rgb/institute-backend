<?php

declare(strict_types=1);

namespace Helpers;

use Core\HttpException;
use Models\User;
use PDOException;

final class Account
{
    public static function assertAvailable(string $nationalCode): void
    {
        if (User::findByNationalCode($nationalCode) !== null || User::findByUsername($nationalCode) !== null) {
            throw self::duplicate();
        }
    }

    public static function register(string $role, array $data): int
    {
        $nationalCode = (string) $data['national_code'];

        try {
            return User::create([
                'username' => $nationalCode,
                'national_code' => $nationalCode,
                'password_hash' => self::defaultPasswordHash($nationalCode),
                'fullname' => $data['fullname'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'skill' => $data['skill'] ?? null,
                'img_url' => $data['img_url'] ?? null,
                'role' => $role,
            ]);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw self::duplicate();
            }

            throw $exception;
        }
    }

    public static function resetPassword(array $user, string $role): void
    {
        $nationalCode = (string) ($user['national_code'] ?? '');

        if ($nationalCode === '') {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, [
                'national_code' => 'کدملی این کاربر ثبت نشده است',
            ]);
        }

        User::updatePassword((int) $user['user_id'], $role, self::defaultPasswordHash($nationalCode));
    }

    private static function defaultPasswordHash(string $nationalCode): string
    {
        return password_hash($nationalCode, PASSWORD_DEFAULT);
    }

    private static function duplicate(): HttpException
    {
        return new HttpException('کدملی یا نام کاربری تکراری است', 409, [
            'national_code' => 'این کدملی قبلاً ثبت شده است',
        ]);
    }
}