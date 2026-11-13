<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Helpers\Pagination;
use Helpers\Upload;
use Helpers\Validator;
use Models\User;
use PDOException;
use Throwable;

final class TeacherController
{
    private const ROLE = 'teacher';

    public static function index(Request $request): void
    {
        $page = Pagination::fromRequest($request);

        $search = $request->query('search');
        $search = is_string($search) ? mb_substr(trim($search), 0, 100, 'UTF-8') : '';

        $result = User::paginate(self::ROLE, $search, $page['per_page'], $page['offset']);

        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page['page'],
            'per_page' => $page['per_page'],
        ], 'لیست مدرس‌ها');
    }

        public static function store(Request $request): void
    {
        $data = Validator::validate($request->body(), [
            'fullname' => ['required', 'max:100'],
            'national_code' => ['required', 'national_code'],
            'skill' => ['required', 'max:100'],
        ]);

        $nationalCode = (string) $data['national_code'];

        if (User::findByNationalCode($nationalCode) !== null || User::findByUsername($nationalCode) !== null) {
            throw self::duplicate();
        }

        $file = $request->file('image');
        $path = Upload::hasFile($file) ? Upload::image($file, 'users') : null;

        try {
            $id = User::create([
                'username' => $nationalCode,
                'national_code' => $nationalCode,
                'password_hash' => password_hash($nationalCode, PASSWORD_DEFAULT),
                'fullname' => $data['fullname'],
                'skill' => $data['skill'],
                'img_url' => $path,
                'role' => self::ROLE,
            ]);
        } catch (Throwable $exception) {
            Upload::delete($path);

            if ($exception instanceof PDOException && (int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw self::duplicate();
            }

            throw $exception;
        }

        Response::success(User::find($id, self::ROLE), 'مدرس با موفقیت ثبت شد', 201);
    }

    private static function duplicate(): HttpException
    {
        return new HttpException('کدملی یا نام کاربری تکراری است', 409, [
            'national_code' => 'این کدملی قبلاً ثبت شده است',
        ]);
    }

        public static function update(Request $request, string $id): void
    {
        $teacherId = Validator::id($id);
        $teacher = self::findOrFail($teacherId);

        $data = Validator::validate($request->body(), [
            'fullname' => ['required', 'max:100'],
            'skill' => ['required', 'max:100'],
        ]);

        $file = $request->file('image');
        $newPath = Upload::hasFile($file) ? Upload::image($file, 'users') : null;

        try {
            User::update($teacherId, self::ROLE, [
                'fullname' => $data['fullname'],
                'skill' => $data['skill'],
                'img_url' => $newPath ?? $teacher['img_url'],
            ]);
        } catch (Throwable $exception) {
            Upload::delete($newPath);

            throw $exception;
        }

        if ($newPath !== null) {
            Upload::delete($teacher['img_url']);
        }

        Response::success(self::findOrFail($teacherId), 'اطلاعات مدرس ویرایش شد');
    }

    public static function destroy(Request $request, string $id): void
    {
        $teacherId = Validator::id($id);
        $teacher = self::findOrFail($teacherId);

        if (self::hasClasses($teacherId)) {
            throw self::hasClassesError();
        }

        try {
            User::delete($teacherId, self::ROLE);
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1451) {
                throw self::hasClassesError();
            }

            throw $exception;
        }

        Upload::delete($teacher['img_url']);

        Response::success(null, 'مدرس حذف شد');
    }

    private static function findOrFail(int $id): array
    {
        return User::find($id, self::ROLE) ?? throw new HttpException('مدرس یافت نشد', 404);
    }

    private static function hasClasses(int $teacherId): bool
    {
        $statement = Database::connection()->prepare('SELECT COUNT(*) FROM classes WHERE user_id = :user_id');
        $statement->execute(['user_id' => $teacherId]);

        return (int) $statement->fetchColumn() > 0;
    }

    private static function hasClassesError(): HttpException
    {
        return new HttpException('این مدرس کلاس فعال دارد', 409);
    }

        public static function resetPassword(Request $request, string $id): void
    {
        $teacherId = Validator::id($id);
        $teacher = self::findOrFail($teacherId);

        $nationalCode = (string) ($teacher['national_code'] ?? '');

        if ($nationalCode === '') {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, [
                'national_code' => 'کدملی این مدرس ثبت نشده است',
            ]);
        }

        User::updatePassword($teacherId, self::ROLE, password_hash($nationalCode, PASSWORD_DEFAULT));

        Response::success(null, 'رمز مدرس به کدملی او بازنشانی شد');
    }

        public static function schedule(Request $request, string $id): void
    {
        $teacherId = Validator::id($id);
        $teacher = self::findOrFail($teacherId);

        $statement = Database::connection()->prepare(
            "SELECT c.class_id, c.title, c.class_num, c.sessions, c.s_date, c.e_date,
                    t.day,
                    TIME_FORMAT(t.s_clock, '%H:%i') AS s_clock,
                    TIME_FORMAT(t.e_clock, '%H:%i') AS e_clock
             FROM classes c
             LEFT JOIN class_time t ON t.class_id = c.class_id
             WHERE c.user_id = :user_id
             ORDER BY c.class_id, t.day, t.s_clock"
        );
        $statement->execute(['user_id' => $teacherId]);

        $classes = [];

        foreach ($statement->fetchAll() as $row) {
            $classId = (int) $row['class_id'];

            if (!isset($classes[$classId])) {
                $classes[$classId] = [
                    'class_id' => $classId,
                    'title' => $row['title'],
                    'class_num' => $row['class_num'],
                    'sessions' => (int) $row['sessions'],
                    's_date' => $row['s_date'],
                    'e_date' => $row['e_date'],
                    'times' => [],
                ];
            }

            if ($row['day'] !== null) {
                $classes[$classId]['times'][] = [
                    'day' => (int) $row['day'],
                    's_clock' => $row['s_clock'],
                    'e_clock' => $row['e_clock'],
                ];
            }
        }

        Response::success([
            'teacher' => $teacher,
            'classes' => array_values($classes),
        ], 'برنامه‌ی مدرس');
    }
}