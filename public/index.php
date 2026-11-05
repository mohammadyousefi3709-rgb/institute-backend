<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/bootstrap.php';


// بارگذاری کلاس‌ها (اگر از Composer استفاده می‌کنید autoload را صدا بزنید، در غیر این صورت دستیrequire کنید)
// require_once __DIR__ . '/../vendor/autoload.php';

use Core\Env;
use Core\Router;

// بارگذاری تنظیمات محیطی
Env::load(__DIR__ . '/../.env');

// بارگذاری لیست مسیرهای API
require_once __DIR__ . '/../routes/api.php';

// گرفتن URI و متد درخواست کاربر
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// اجرای روتر
Router::dispatch($uri, $method);