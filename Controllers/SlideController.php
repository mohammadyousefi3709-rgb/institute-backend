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

final class SlideController
{
    public static function index(): void
    {
        $slides = Database::connection()
            ->query('SELECT slide_id, title, description, img_url FROM slide_shows ORDER BY slide_id')
            ->fetchAll();

        Response::success($slides, 'لیست اسلایدها');
    }

    public static function store(Request $request): void
    {
        $data = Validator::validate($request->body(), [
            'title' => ['required', 'max:150'],
            'description' => ['max:1000'],
        ]);

        $file = $request->file('image');

        if (!Upload::hasFile($file)) {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, [
                'image' => 'تصویر اسلاید الزامی است',
            ]);
        }

        $path = Upload::image($file, 'slides');
        $pdo = Database::connection();

        try {
            $statement = $pdo->prepare(
                'INSERT INTO slide_shows (title, description, img_url)
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

        Response::success(self::find((int) $pdo->lastInsertId()), 'اسلاید با موفقیت اضافه شد', 201);
    }

    public static function update(Request $request, string $id): void
    {
        $slideId = Validator::id($id);
        $slide = self::find($slideId);

        $data = Validator::validate($request->body(), [
            'title' => ['required', 'max:150'],
            'description' => ['max:1000'],
        ]);

        $file = $request->file('image');
        $newPath = Upload::hasFile($file) ? Upload::image($file, 'slides') : null;

        try {
            $statement = Database::connection()->prepare(
                'UPDATE slide_shows
                 SET title = :title, description = :description, img_url = :img_url
                 WHERE slide_id = :slide_id'
            );
            $statement->execute([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'img_url' => $newPath ?? $slide['img_url'],
                'slide_id' => $slideId,
            ]);
        } catch (Throwable $exception) {
            Upload::delete($newPath);

            throw $exception;
        }

        if ($newPath !== null) {
            Upload::delete($slide['img_url']);
        }

        Response::success(self::find($slideId), 'اسلاید با موفقیت ویرایش شد');
    }

    public static function destroy(Request $request, string $id): void
    {
        $slideId = Validator::id($id);
        $slide = self::find($slideId);

        $statement = Database::connection()->prepare('DELETE FROM slide_shows WHERE slide_id = :slide_id');
        $statement->execute(['slide_id' => $slideId]);

        Upload::delete($slide['img_url']);

        Response::success(null, 'اسلاید حذف شد');
    }

    private static function find(int $id): array
    {
        $statement = Database::connection()->prepare(
            'SELECT slide_id, title, description, img_url FROM slide_shows WHERE slide_id = :slide_id'
        );
        $statement->execute(['slide_id' => $id]);
        $slide = $statement->fetch();

        if ($slide === false) {
            throw new HttpException('اسلاید یافت نشد', 404);
        }

        return $slide;
    }
}