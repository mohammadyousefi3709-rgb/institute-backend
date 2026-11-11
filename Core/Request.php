<?php

declare(strict_types=1);

namespace Core;

use JsonException;

final class Request
{
    public ?array $user = null;

    private ?array $body = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        return is_string($path) ? $path : '/';
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return $_SERVER[$key] ?? null;
    }

    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization')
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }

        public function body(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }

        if ($this->isFormRequest()) {
            if ($_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
                throw new HttpException('حجم درخواست بیشتر از حد مجاز سرور است', 413);
            }

            return $this->body = $_POST;
        }

        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return $this->body = [];
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException('بدنه‌ی درخواست JSON معتبر نیست', 400);
        }

        return $this->body = is_array($decoded) ? $decoded : [];
    }
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $_GET[$key] ?? $default;
    }

        public function file(string $key): ?array
    {
        $file = $_FILES[$key] ?? null;

        return is_array($file) ? $file : null;
    }

    private function isFormRequest(): bool
    {
        $type = strtolower($_SERVER['CONTENT_TYPE'] ?? '');

        return str_starts_with($type, 'multipart/form-data')
            || str_starts_with($type, 'application/x-www-form-urlencoded');
    }
}