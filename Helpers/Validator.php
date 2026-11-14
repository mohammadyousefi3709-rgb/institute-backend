<?php

declare(strict_types=1);

namespace Helpers;

use Core\Database;
use Core\HttpException;
use InvalidArgumentException;

final class Validator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value !== null && !is_scalar($value)) {
                $errors[$field] = 'مقدار نامعتبر است';
                continue;
            }

            if (self::isEmpty($value) && !in_array('required', $fieldRules, true)) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                $message = self::check($rule, $value);

                if ($message !== null) {
                    $errors[$field] = $message;
                    continue 2;
                }
            }

            $validated[$field] = $value;
        }

        if ($errors !== []) {
            throw new HttpException('اطلاعات ارسالی نامعتبر است', 422, $errors);
        }

        return $validated;
    }

        public static function id(string $value): int
    {
        if (preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            throw new HttpException('مورد درخواستی یافت نشد', 404);
        }

        return (int) $value;
    }
    private static function check(string $rule, mixed $value): ?string
    {
        [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

        return match ($name) {
            'required' => self::isEmpty($value) ? 'این فیلد الزامی است' : null,
            'min' => self::length($value) < (int) $param ? "حداقل {$param} کاراکتر باشد" : null,
            'max' => self::length($value) > (int) $param ? "حداکثر {$param} کاراکتر باشد" : null,
            'regex' => preg_match((string) $param, (string) $value) !== 1 ? 'فرمت نامعتبر است' : null,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false ? 'ایمیل نامعتبر است' : null,
            'unique' => self::exists((string) $param, $value) ? 'این مقدار قبلاً ثبت شده است' : null,
            'national_code' => self::isNationalCode((string) $value) ? null : 'کدملی نامعتبر است',
            'mobile' => preg_match('/^09\d{9}$/', (string) $value) === 1 ? null : 'شماره موبایل نامعتبر است',
            default => throw new InvalidArgumentException("Unknown validation rule: {$name}"),
        };
    }

    private static function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '';
    }

        private static function isNationalCode(string $code): bool
    {
        if (preg_match('/^\d{10}$/', $code) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{9}$/', $code) === 1) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $code[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $control = (int) $code[9];

        return $remainder < 2 ? $control === $remainder : $control === 11 - $remainder;
    }

    private static function length(mixed $value): int
    {
        return mb_strlen((string) $value, 'UTF-8');
    }

    private static function exists(string $param, mixed $value): bool
    {
        [$table, $column] = array_pad(explode(',', $param, 2), 2, null);

        if (
            $table === null
            || $column === null
            || preg_match('/^[a-z_]+$/', $table) !== 1
            || preg_match('/^[a-z_]+$/', $column) !== 1
        ) {
            throw new InvalidArgumentException('Invalid unique rule: ' . $param);
        }

        $statement = Database::connection()->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :value"
        );
        $statement->execute(['value' => (string) $value]);

        return (int) $statement->fetchColumn() > 0;
    }
}