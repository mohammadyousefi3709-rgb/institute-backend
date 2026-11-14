<?php

declare(strict_types=1);

namespace Helpers;

final class Schedule
{
    public static function groupByClass(array $rows): array
    {
        $classes = [];

        foreach ($rows as $row) {
            $classId = (int) $row['class_id'];

            if (!isset($classes[$classId])) {
                $class = [
                    'class_id' => $classId,
                    'title' => $row['title'],
                    'class_num' => $row['class_num'],
                    'sessions' => (int) $row['sessions'],
                    's_date' => $row['s_date'],
                    'e_date' => $row['e_date'],
                ];

                if (array_key_exists('teacher_name', $row)) {
                    $class['teacher_name'] = $row['teacher_name'];
                }

                $class['times'] = [];
                $classes[$classId] = $class;
            }

            if ($row['day'] !== null) {
                $classes[$classId]['times'][] = [
                    'day' => (int) $row['day'],
                    's_clock' => $row['s_clock'],
                    'e_clock' => $row['e_clock'],
                ];
            }
        }

        return array_values($classes);
    }
}