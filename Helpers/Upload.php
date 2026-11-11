<?php

declare(strict_types=1);

namespace Helpers;

use Core\HttpException;
use finfo;
use InvalidArgumentException;
use RuntimeException;

final class Upload
{
    private const MAX_SIZE = 2 * 1024 * 1024;

    private const FOLDERS = ['slides', 'contents', 'users'];

    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const ERROR_MESSAGES = [
        UPLOAD_ERR_INI_SIZE => 'حجم فایل بیشتر از حد مجاز سرور است',
        UPLOAD_ERR_FORM_SIZE => 'حجم فایل بیشتر از حد مجاز است',
        UPLOAD_ERR_PARTIAL => 'فایل به‌صورت ناقص آپلود شد',
        UPLOAD_ERR_NO_FILE => 'فایلی ارسال نشده است',
        UPLOAD_ERR_NO_TMP_DIR => 'پوشه‌ی موقت آپلود روی سرور وجود ندارد',
        UPLOAD_ERR_CANT_WRITE => 'ذخیره‌ی فایل روی سرور ممکن نشد',
        UPLOAD_ERR_EXTENSION => 'آپلود توسط یک افزونه‌ی PHP متوقف شد',
    ];

    public static function hasFile(?array $file): bool
    {
        return $file !== null
            && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    public static function image(array $file, string $folder): string
    {
        if (!in_array($folder, self::FOLDERS, true)) {
            throw new InvalidArgumentException('Unknown upload folder: ' . $folder);
        }

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            $message = is_int($error)
                ? (self::ERROR_MESSAGES[$error] ?? 'آپلود فایل با خطا مواجه شد')
                : 'آپلود فایل با خطا مواجه شد';

            throw self::invalid($message);
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw self::invalid('فایل ارسال‌شده معتبر نیست');
        }

        if ((int) filesize($tmpName) > self::MAX_SIZE) {
            throw self::invalid('حجم تصویر نباید بیشتر از ۲ مگابایت باشد');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpName);

        if ($mime === false || !isset(self::ALLOWED_TYPES[$mime])) {
            throw self::invalid('فقط تصویر jpg، png یا webp مجاز است');
        }

        $directory = self::root() . '/' . $folder;

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Cannot create upload directory: ' . $directory);
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::ALLOWED_TYPES[$mime];

        if (!move_uploaded_file($tmpName, $directory . '/' . $name)) {
            throw new RuntimeException('Cannot move uploaded file to: ' . $directory);
        }

        return 'uploads/' . $folder . '/' . $name;
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '') {
            return;
        }

        $root = realpath(self::root());
        $file = realpath(BASE_PATH . '/public/' . $path);

        if ($root === false || $file === false) {
            return;
        }

        if (!str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
            return;
        }

        if (is_file($file) && !unlink($file)) {
    throw new RuntimeException('حذف فایل ناموفق بود');
}
    }

    private static function root(): string
    {
        return BASE_PATH . '/public/uploads';
    }

    private static function invalid(string $message): HttpException
    {
        return new HttpException('اطلاعات ارسالی نامعتبر است', 422, ['image' => $message]);
    }
}