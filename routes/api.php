use Core\Router;

Router::get('/api/health', function() {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "success" => true,
        "message" => "سیستم سالم است و روتر به درستی کار می‌کند! ✅"
    ], JSON_UNESCAPED_UNICODE);
});