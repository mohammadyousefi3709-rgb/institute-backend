<?php

declare(strict_types=1);

use Core\Response;
use Core\Router;

Router::get('/api/health', function () {
   Response::success(['status' => 'ok'], 'سرویس فعال است');
});