<?php
declare(strict_types=1);

namespace TrailSense\Domain;

use DateTimeImmutable;

/** Server-side validation for F2 (plan) and F9 (group details). Returns [clean data, errors]. */
final class PlanValidator
{
    /**
     * @param array<string,string> $in     raw form fields
     * @param callable(int):bool   $trailExists
     * @return array{0: array<string,mixed>, 1: array<string,string>}
     */
    public static function validate(array $in, callable $trailExists, bool $isGroup, DateTimeImmutable $today): array
    {
        $errors = [];
        $data = [];

        $trailId = $in['trail_id'] ?? '';
        if (!ctype_digit($trailId) || !$trailExists((int) $trailId)) {
            $errors['trail_id'] = 'Choose a trail from the list.';
        } else {
            $data['trail_id'] = (int) $trailId;
        }

        $date = self::date($in['planned_date'] ?? '');
        if ($date === null) {
            $errors['planned_date'] = 'Enter a valid date.';
        } elseif ($date < $today->setTime(0, 0)) {
            $errors['planned_date'] = 'The date cannot be in the past.';
        } else {
            $data['planned_date'] = $date->format('Y-m-d');
        }

        $start = self::time($in['start_time'] ?? '');
        if ($start === null) {
            $errors['start_time'] = 'Enter a start time (HH:MM).';
        } else {
            $data['start_time'] = $start;
        }

        $duration = $in['duration_min'] ?? '';
        if (!ctype_digit($duration) || (int) $duration < 30 || (int) $duration > 720) {
            $errors['duration_min'] = 'Duration must be between 30 and 720 minutes.';
        } else {
            $data['duration_min'] = (int) $duration;
        }

        $notes = $in['notes'] ?? '';
        if (mb_strlen($notes) > 500) {
            $errors['notes'] = 'Notes can have at most 500 characters.';
        } else {
            $data['notes'] = $notes;
        }

        if ($isGroup) {
            [$group, $groupErrors] = self::validateGroup($in, $data['start_time'] ?? null);
            $data['group'] = $group;
            $errors += $groupErrors;
        }

        return [$data, $errors];
    }

    /** @return array{0: array<string,mixed>, 1: array<string,string>} */
    private static function validateGroup(array $in, ?string $startTime): array
    {
        $errors = [];
        $group = [];

        $size = $in['group_size'] ?? '';
        if (!ctype_digit($size) || (int) $size < 2 || (int) $size > 40) {
            $errors['group_size'] = 'Group size must be between 2 and 40 people.';
        } else {
            $group['group_size'] = (int) $size;
        }

        $point = $in['meeting_point'] ?? '';
        if (mb_strlen($point) < 3 || mb_strlen($point) > 120) {
            $errors['meeting_point'] = 'Enter a meeting point (3–120 characters).';
        } else {
            $group['meeting_point'] = $point;
        }

        $meet = self::time($in['meeting_time'] ?? '');
        if ($meet === null) {
            $errors['meeting_time'] = 'Enter a meeting time (HH:MM).';
        } elseif ($startTime !== null && $meet > $startTime) {
            $errors['meeting_time'] = 'The group must meet before or at the start time.';
        } else {
            $group['meeting_time'] = $meet;
        }

        return [$group, $errors];
    }

    public static function date(string $value): ?DateTimeImmutable
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return ($d !== false && $d->format('Y-m-d') === $value) ? $d : null;
    }

    public static function time(string $value): ?string
    {
        if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(:00)?$/', $value, $m)) {
            return null;
        }
        return $m[1] . ':' . $m[2];
    }
}
