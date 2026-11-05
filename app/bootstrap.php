<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {

    $prefix = 'Core\\';
    $baseDir = BASE_PATH . '/core/';

    // بررسی فضای نام کلاس
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    // استخراج نام کلاس
    $className = substr($class, strlen($prefix));

    // تبدیل نام کلاس به نام فایل کوچک
    $file = $baseDir . strtolower($className) . '.php';

    // بارگذاری فایل در صورت وجود
    if (file_exists($file)) {
        require_once $file;
    }
});