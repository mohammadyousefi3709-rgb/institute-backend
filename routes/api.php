<?php

declare(strict_types=1);

use Core\Response;
use Core\Router;
use Core\Database;
use Controllers\AuthController;
use Middlewares\AuthMiddleware;
use Controllers\LandingController;
use Controllers\InfoController;
use Middlewares\RoleMiddleware;
use Controllers\SlideController;
use Controllers\ContentController;



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

// Public landing page
Router::get('/api/public/landing', [LandingController::class, 'show']);

$admin = [[AuthMiddleware::class, 'handle'], RoleMiddleware::allow('admin')];

// Landing page management (admin only)
Router::put('/api/admin/info', [InfoController::class, 'update'], $admin);
Router::get('/api/admin/slides', [SlideController::class, 'index'], $admin);
Router::post('/api/admin/slides', [SlideController::class, 'store'], $admin);
Router::post('/api/admin/slides/{id}', [SlideController::class, 'update'], $admin);
Router::delete('/api/admin/slides/{id}', [SlideController::class, 'destroy'], $admin);
Router::get('/api/admin/contents', [ContentController::class, 'index'], $admin);
Router::post('/api/admin/contents', [ContentController::class, 'store'], $admin);
Router::post('/api/admin/contents/{id}', [ContentController::class, 'update'], $admin);
Router::delete('/api/admin/contents/{id}', [ContentController::class, 'destroy'], $admin);


