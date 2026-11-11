<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Helpers\Upload;
use Helpers\Validator;
use Throwable;

final class ContentController
{
    public static function index(): void
    {
        $contents = Database::connection()
            ->query(
                'SELECT content_id, title, description, img_url, created_at
                 FROM new_content
                 ORDER BY created_at DESC, content_id DESC'
            )
            ->fetchAll();

        Response::success($contents, 'لیست مطالب');
    }

    public static function store(Request $request): void
    {
        $data = Validator::validate($request->body(), [
            'title' => ['required', 'max:150'],
            'description' => ['max:5000'],
        ]);

        $file = $request->file('image');
        $path = Upload::hasFile($file) ? Upload::image($file, 'contents') : null;
        $pdo = Database::connection();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO new_content (title, description, img_url)
                 VALUES (:title, :description, :img_url)'
            );
            $statement->execute([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'img_url' => $path,
            ]);
        } catch (Throwable $exception) {
            Upload::delete($path);

            throw $exception;
        }

        Response::success(self::find((int) $pdo->lastInsertId()), 'مطلب با موفقیت اضافه شد', 201);
    }

    public static function update(Request $request, string $id): void
    {
        $contentId = Validator::id($id);
        $content = self::find($contentId);

        $data = Validator::validate($request->body(), [
            'title' => ['required', 'max:150'],
            'description' => ['max:5000'],
        ]);

        $file = $request->file('image');
        $newPath = Upload::hasFile($file) ? Upload::image($file, 'contents') : null;

        try {
            $statement = Database::connection()->prepare(
                'UPDATE new_content
                 SET title = :title, description = :description, img_url = :img_url
                 WHERE content_id = :content_id'
            );
            $statement->execute([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'img_url' => $newPath ?? $content['img_url'],
                'content_id' => $contentId,
            ]);
        } catch (Throwable $exception) {
            Upload::delete($newPath);

            throw $exception;
        }

        if ($newPath !== null) {
            Upload::delete($content['img_url']);
        }

        Response::success(self::find($contentId), 'مطلب با موفقیت ویرایش شد');
    }

    public static function destroy(Request $request, string $id): void
    {
        $contentId = Validator::id($id);
        $content = self::find($contentId);

        $statement = Database::connection()->prepare('DELETE FROM new_content WHERE content_id = :content_id');
        $statement->execute(['content_id' => $contentId]);

        Upload::delete($content['img_url']);

        Response::success(null, 'مطلب حذف شد');
    }

    private static function find(int $id): array
    {
        $statement = Database::connection()->prepare(
            'SELECT content_id, title, description, img_url, created_at
             FROM new_content
             WHERE content_id = :content_id'
        );
        $statement->execute(['content_id' => $id]);
        $content = $statement->fetch();

        if ($content === false) {
            throw new HttpException('مطلب یافت نشد', 404);
        }

        return $content;
    }
}