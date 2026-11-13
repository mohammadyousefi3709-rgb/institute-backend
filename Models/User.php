<?php

declare(strict_types=1);

namespace Models;

use Core\Database;
use PDO;

final class User
{
    private const PUBLIC_COLUMNS = 'user_id, username, national_code, fullname, phone, address, skill, img_url, role, created_at, updated_at';

    private const EDITABLE_COLUMNS = ['fullname', 'phone', 'address', 'skill', 'img_url'];

    public static function find(int $id, string $role): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE user_id = :user_id AND role = :role'
        );
        $statement->execute(['user_id' => $id, 'role' => $role]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    public static function findByUsername(string $username): ?array
    {
        return self::findOneBy('username', $username);
    }

    public static function findByNationalCode(string $nationalCode): ?array
    {
        return self::findOneBy('national_code', $nationalCode);
    }

    public static function paginate(string $role, string $search, int $limit, int $offset): array
    {
        $pdo = Database::connection();

        $where = 'role = :role';
        $params = [':role' => $role];

        if ($search !== '') {
            $like = '%' . addcslashes($search, '\\%_') . '%';

            $where .= ' AND (fullname LIKE :search_name OR national_code LIKE :search_code OR skill LIKE :search_skill)';
            $params[':search_name'] = $like;
            $params[':search_code'] = $like;
            $params[':search_skill'] = $like;
        }

        $count = $pdo->prepare('SELECT COUNT(*) FROM users WHERE ' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $statement = $pdo->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE ' . $where
            . ' ORDER BY user_id DESC LIMIT :limit OFFSET :offset'
        );

        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value);
        }

        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return ['items' => $statement->fetchAll(), 'total' => $total];
    }

    public static function create(array $data): int
    {
        $pdo = Database::connection();

        $statement = $pdo->prepare(
            'INSERT INTO users (username, national_code, password, fullname, phone, address, skill, img_url, role)
             VALUES (:username, :national_code, :password, :fullname, :phone, :address, :skill, :img_url, :role)'
        );
        $statement->execute([
            'username' => $data['username'],
            'national_code' => $data['national_code'] ?? null,
            'password' => $data['password_hash'],
            'fullname' => $data['fullname'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'skill' => $data['skill'] ?? null,
            'img_url' => $data['img_url'] ?? null,
            'role' => $data['role'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, string $role, array $fields): void
    {
        $sets = [];
        $params = ['user_id' => $id, 'role' => $role];

        foreach (self::EDITABLE_COLUMNS as $column) {
            if (array_key_exists($column, $fields)) {
                $sets[] = $column . ' = :' . $column;
                $params[$column] = $fields[$column];
            }
        }

        if ($sets === []) {
            return;
        }

        $statement = Database::connection()->prepare(
            'UPDATE users SET ' . implode(', ', $sets) . ' WHERE user_id = :user_id AND role = :role'
        );
        $statement->execute($params);
    }

    public static function updatePassword(int $id, string $role, string $passwordHash): void
    {
        $statement = Database::connection()->prepare(
            'UPDATE users SET password = :password WHERE user_id = :user_id AND role = :role'
        );
        $statement->execute(['password' => $passwordHash, 'user_id' => $id, 'role' => $role]);
    }

    public static function delete(int $id, string $role): void
    {
        $statement = Database::connection()->prepare(
            'DELETE FROM users WHERE user_id = :user_id AND role = :role'
        );
        $statement->execute(['user_id' => $id, 'role' => $role]);
    }

    private static function findOneBy(string $column, string $value): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE ' . $column . ' = :value'
        );
        $statement->execute(['value' => $value]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }
}