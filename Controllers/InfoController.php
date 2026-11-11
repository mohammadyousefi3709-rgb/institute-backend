<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\Request;
use Core\Response;
use Helpers\Validator;
use RuntimeException;

final class InfoController
{
    public static function update(Request $request): void
    {
        $data = Validator::validate($request->body(), [
            'about' => ['max:5000'],
            'phone' => ['max:30'],
            'email' => ['max:120', 'email'],
            'address' => ['max:255'],
        ]);

        $pdo = Database::connection();

        $infoId = $pdo->query('SELECT info_id FROM info ORDER BY info_id LIMIT 1')->fetchColumn();

        if ($infoId === false) {
            throw new RuntimeException('The info row is missing. Run: php database/seed.php');
        }

        $statement = $pdo->prepare(
            'UPDATE info
             SET about = :about, phone = :phone, email = :email, address = :address
             WHERE info_id = :info_id'
        );
        $statement->execute([
            'about' => $data['about'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'info_id' => $infoId,
        ]);

        $current = $pdo->prepare('SELECT about, phone, email, address FROM info WHERE info_id = :info_id');
        $current->execute(['info_id' => $infoId]);

        Response::success($current->fetch(), 'اطلاعات سایت ذخیره شد');
    }
}