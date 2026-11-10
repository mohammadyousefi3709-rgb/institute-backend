<?php

declare(strict_types=1);

namespace Core;

class Router {
    private static array $routes = [];

    public static function get(string $path, $handler, array $middlewares = []): void {
        self::add('GET', $path, $handler, $middlewares);
    }

    public static function post(string $path, $handler, array $middlewares = []): void {
        self::add('POST', $path, $handler, $middlewares);
    }

    public static function put(string $path, $handler, array $middlewares = []): void {
        self::add('PUT', $path, $handler, $middlewares);
    }

    public static function delete(string $path, $handler, array $middlewares = []): void {
        self::add('DELETE', $path, $handler, $middlewares);
    }

    private static function add(string $method, string $path, $handler, array $middlewares): void {
        // استانداردسازی مسیر (حذف اسلش اضافه در انتهای مسیر)
        $path = rtrim($path, '/');
        self::$routes[] = [
            'method' => $method,
            'path' => $path === '' ? '/' : $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public static function dispatch(string $uri, string $method): void {
        // حذف base path از URI (اگر پروژه در زیرپوشه اجرا شود)
        $basePath = Env::get('APP_BASE_PATH', '/');
        if ($basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }
        
        // پاکسازی URI از Query String (مثل ?id=1)
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if ($uri === '') $uri = '/';

        $routeFound = false;
        $methodMatched = false;

        foreach (self::$routes as $route) {
            // تبدیل الگوهای مثل {id} به Regular Expression برای گرفتن پارامترها
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $uri, $matches)) {
                $routeFound = true;
                if ($route['method'] === $method) {
                    $methodMatched = true;
                    
                                       // حذف کل تطابق از ابتدای آرایه و نگهداری متغیرها
                    array_shift($matches);

                    $request = new Request();

                    // اجرای میدلورها به ترتیب؛ هر کدام در صورت رد شدن استثنا پرتاب می‌کند
                    foreach ($route['middlewares'] as $middleware) {
                        call_user_func($middleware, $request);
                    }

                    // اجرای تابع یا کنترلر مربوطه؛ Request همیشه اولین آرگومان است
                    if (is_callable($route['handler'])) {
                        call_user_func_array($route['handler'], [$request, ...$matches]);
                    }
                    return;
                }
            }
        }

                // اگر مسیر پیدا شد اما متد اشتباه بود (خطای 405)
        if ($routeFound && !$methodMatched) {
            throw new HttpException('متد درخواست برای این مسیر مجاز نیست (Method Not Allowed)', 405);
        }

               // اگر کلاً مسیر پیدا نشد (خطای 404)
        throw new HttpException('مسیر مورد نظر یافت نشد (Not Found)', 404);
        
    }

}