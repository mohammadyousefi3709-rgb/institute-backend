<?php

declare(strict_types=1);

use Core\Response;
use Core\Router;
use Core\Database;
use Controllers\AuthController;
use Middlewares\AuthMiddleware;

Router::get('/api/health', function () {
   $result = Database::connection()->query('SELECT 1')->fetchColumn();

   Response::success([
      'status' => 'ok',
      'database' => (int) $result === 1 ? 'connected' : 'unknown',
   ], 'سرویس فعال است');
});

Router::post('/api/auth/login', [AuthController::class, 'login']);

Router::get('/api/auth/me', [AuthController::class, 'me'], [[AuthMiddleware::class, 'handle']]);
Router::post('/api/auth/change-password', [AuthController::class, 'changePassword'], [[AuthMiddleware::class, 'handle']]);



