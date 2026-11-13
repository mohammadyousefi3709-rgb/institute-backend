<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Helpers\Token;
use Helpers\Validator;

final class AuthController
{
    public static function login(): void
    {
        $request = new Request();
        $body = $request->body();

        $data = Validator::validate($body, [
            'username' => ['required', 'max:50'],
            'password' => ['required', 'max:255'],
        ]);

        $password = (string) $body['password'];

        $statement = Database::connection()->prepare(
            'SELECT user_id, username, password, fullname, role
             FROM users
             WHERE username = :username'
        );
        $statement->execute(['username' => (string) $data['username']]);
        $user = $statement->fetch();

        $hash = $user !== false
            ? (string) $user['password']
            : password_hash('invalid-password', PASSWORD_DEFAULT);

        $passwordMatches = password_verify($password, $hash);

        if ($user === false || !$passwordMatches) {
            throw new HttpException('نام کاربری یا رمز عبور اشتباه است', 401);
        }

        Response::success([
            'token' => Token::generate((int) $user['user_id'], (string) $user['role']),
            'user' => [
                'user_id' => (int) $user['user_id'],
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'role' => $user['role'],
            ],
        ], 'ورود با موفقیت انجام شد');
 
 
    }

            public static function me(Request $request): void
    {
        $statement = Database::connection()->prepare(
            'SELECT user_id, username, national_code, fullname, phone, address, skill, img_url, role
             FROM users
             WHERE user_id = :user_id'
        );
        $statement->execute(['user_id' => (int) $request->user['user_id']]);
        $profile = $statement->fetch();

        if ($profile === false) {
            throw new HttpException('کاربر یافت نشد', 404);
        }

        Response::success($profile, 'اطلاعات کاربر');
    }

    public static function changePassword(Request $request): void
    {
        $body = $request->body();

        Validator::validate($body, [
            'current_password' => ['required', 'max:255'],
            'new_password' => ['required', 'min:8', 'max:255'],
            'confirm_password' => ['required', 'max:255'],
        ]);

        $current = (string) $body['current_password'];
        $new = (string) $body['new_password'];
        $confirm = (string) $body['confirm_password'];

        if ($new !== $confirm) {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, [
                'confirm_password' => 'تکرار رمز با رمز جدید یکسان نیست',
            ]);
        }

        if ($new === $current) {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, [
                'new_password' => 'رمز جدید باید با رمز فعلی متفاوت باشد',
            ]);
        }

        $userId = (int) $request->user['user_id'];
        $pdo = Database::connection();

        $statement = $pdo->prepare('SELECT password FROM users WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $hash = $statement->fetchColumn();

        if (!is_string($hash) || !password_verify($current, $hash)) {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, [
                'current_password' => 'رمز فعلی اشتباه است',
            ]);
        }

        $update = $pdo->prepare('UPDATE users SET password = :password WHERE user_id = :user_id');
        $update->execute([
            'password' => password_hash($new, PASSWORD_DEFAULT),
            'user_id' => $userId,
        ]);

        Response::success(null, 'رمز عبور با موفقیت تغییر کرد');
    }
    
}
