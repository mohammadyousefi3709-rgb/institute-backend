<?php

declare(strict_types=1);

// اتولودر یکپارچه و استاندارد برای بارگذاری خودکار کلاس‌ها بر اساس Namespace
spl_autoload_register(function (string $class) {
    // تبدیل Namespace (مثل Core\Env) به مسیر فایل (با حفظ دقیق بزرگی و کوچکی حروف)
    $file = BASE_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});