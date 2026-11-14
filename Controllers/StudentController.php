<?php

declare(strict_types=1);

namespace Controllers;

use Core\Database;
use Core\HttpException;
use Core\Request;
use Core\Response;
use Helpers\Account;
use Helpers\Pagination;
use Helpers\Schedule;
use Helpers\Validator;
use Models\User;

final class StudentController
{
    private const ROLE = 'student';

    private const SEARCH_COLUMNS = ['fullname', 'national_code', 'phone'];

    public static function index(Request $request): void
    {
        $page = Pagination::fromRequest($request);

        $search = $request->query('search');
        $search = is_string($search) ? mb_substr(trim($search), 0, 100, 'UTF-8') : '';

        $result = User::paginate(self::ROLE, $search, self::SEARCH_COLUMNS, $page['per_page'], $page['offset']);

        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $page['page'],
            'per_page' => $page['per_page'],
        ], 'لیست زبان‌آموزها');
    }

        public static function store(Request $request): void
    {
        $data = Validator::validate($request->body(), [
            'fullname' => ['required', 'max:100'],
            'national_code' => ['required', 'national_code'],
            'phone' => ['required', 'mobile'],
            'address' => ['max:255'],
        ]);

        $nationalCode = (string) $data['national_code'];

        Account::assertAvailable($nationalCode);

        $id = Account::register(self::ROLE, [
            'national_code' => $nationalCode,
            'fullname' => $data['fullname'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
        ]);

        Response::success(User::find($id, self::ROLE), 'زبان‌آموز با موفقیت ثبت شد', 201);
    }

        public static function update(Request $request, string $id): void
    {
        $studentId = Validator::id($id);
        self::findOrFail($studentId);

        $data = Validator::validate($request->body(), [
            'fullname' => ['required', 'max:100'],
            'phone' => ['required', 'mobile'],
            'address' => ['max:255'],
        ]);

        User::update($studentId, self::ROLE, [
            'fullname' => $data['fullname'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
        ]);

        Response::success(self::findOrFail($studentId), 'اطلاعات زبان‌آموز ویرایش شد');
    }

    public static function destroy(Request $request, string $id): void
    {
        $studentId = Validator::id($id);
        self::findOrFail($studentId);

        User::delete($studentId, self::ROLE);

        Response::success(null, 'زبان‌آموز حذف شد');
    }

    private static function findOrFail(int $id): array
    {
        return User::find($id, self::ROLE) ?? throw new HttpException('زبان‌آموز یافت نشد', 404);
    }

        public static function resetPassword(Request $request, string $id): void
    {
        $studentId = Validator::id($id);
        $student = self::findOrFail($studentId);

        Account::resetPassword($student, self::ROLE);

        Response::success(null, 'رمز زبان‌آموز به کدملی او بازنشانی شد');
    }

    public static function schedule(Request $request, string $id): void
    {
        $studentId = Validator::id($id);
        $student = self::findOrFail($studentId);

        $statement = Database::connection()->prepare(
            "SELECT c.class_id, c.title, c.class_num, c.sessions, c.s_date, c.e_date,
                    u.fullname AS teacher_name,
                    t.day,
                    TIME_FORMAT(t.s_clock, '%H:%i') AS s_clock,
                    TIME_FORMAT(t.e_clock, '%H:%i') AS e_clock
             FROM class_student cs
             JOIN classes c ON c.class_id = cs.class_id
             JOIN users u ON u.user_id = c.user_id
             LEFT JOIN class_time t ON t.class_id = c.class_id
             WHERE cs.user_id = :user_id
             ORDER BY c.class_id, t.day, t.s_clock"
        );
        $statement->execute(['user_id' => $studentId]);

        Response::success([
            'student' => $student,
            'classes' => Schedule::groupByClass($statement->fetchAll()),
        ], 'برنامه‌ی زبان‌آموز');
    }
}