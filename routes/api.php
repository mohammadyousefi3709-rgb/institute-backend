<?php

declare(strict_types=1);

use Core\Response;
use Core\Router;
use Core\Database;

Router::get('/api/health', function () {
   $result = Database::connection()->query('SELECT 1')->fetchColumn();

   Response::success([
      'status' => 'ok',
      'database' => (int) $result === 1 ? 'connected' : 'unknown',
   ], 'سرویس فعال است');
});
