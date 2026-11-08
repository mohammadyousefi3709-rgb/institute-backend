<?php

declare(strict_types=1);

use Core\Database;
use Core\Env;

if (PHP_SAPI !== 'cli') {
    exit('This script can only be run from the command line.');
}

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';

Env::load(BASE_PATH . '/.env');

$username = Env::get('SEED_ADMIN_USERNAME', 'admin');
$password = Env::get('SEED_ADMIN_PASSWORD', '');
$fullname = Env::get('SEED_ADMIN_FULLNAME', 'مدیر سیستم');

if ($password === '') {
    echo 'SEED_ADMIN_PASSWORD is empty in .env' . PHP_EOL;
    exit(1);
}

$pdo = Database::connection();

$check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
$check->execute(['username' => $username]);

if ((int) $check->fetchColumn() > 0) {
    echo 'Admin already exists, skipped.' . PHP_EOL;
} else {
    $insert = $pdo->prepare(
        'INSERT INTO users (username, password, fullname, role)
         VALUES (:username, :password, :fullname, :role)'
    );
    $insert->execute([
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'fullname' => $fullname,
        'role' => 'admin',
    ]);

    echo 'Admin created.' . PHP_EOL;
}

if ((int) $pdo->query('SELECT COUNT(*) FROM info')->fetchColumn() === 0) {
    $pdo->exec('INSERT INTO info () VALUES ()');

    echo 'Info row created.' . PHP_EOL;
} else {
    echo 'Info row already exists, skipped.' . PHP_EOL;
}

echo 'Seed finished.' . PHP_EOL;لهف