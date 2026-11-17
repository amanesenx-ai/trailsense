<?php
declare(strict_types=1);

namespace TrailSense\Domain;

use DateTimeImmutable;

/** Server-side validation for F7 (publish a hike). Photo checks live in PhotoValidator. */
final class ActivityValidator
{
    /**
     * @param callable(int):?array $findTrail returns the trail row or null
     * @return array{0: array<string,mixed>, 1: array<string,string>}
     */
    public static function validate(array $in, callable $findTrail, DateTimeImmutable $today): array
    {
        $errors = [];
        $data = [];

        $trail = null;
        $trailId = $in['trail_id'] ?? '';
        if (ctype_digit($trailId)) {
            $trail = $findTrail((int) $trailId);
        }
        if ($trail === null) {
            $errors['trail_id'] = 'Choose the trail you hiked.';
        } else {
            $data['trail_id'] = (int) $trail['id'];
        }

        $date = PlanValidator::date($in['hiked_on'] ?? '');
        if ($date === null) {
            $errors['hiked_on'] = 'Enter a valid date.';
        } elseif ($date > $today->setTime(0, 0)) {
            $errors['hiked_on'] = 'You can only publish hikes that already happened.';
        } else {
            $data['hiked_on'] = $date->format('Y-m-d');
        }

        $distance = str_replace(',', '.', $in['distance_km'] ?? '');
        if ($distance === '' && $trail !== null) {
            $data['distance_km'] = (float) $trail['distance_km'];   // AC7.1 default
        } elseif (!is_numeric($distance) || (float) $distance <= 0 || (float) $distance > 200) {
            $errors['distance_km'] = 'Distance must be a number between 0.1 and 200 km.';
        } else {
            $data['distance_km'] = round((float) $distance, 1);
        }

        $duration = $in['duration_min'] ?? '';
        if (!ctype_digit($duration) || (int) $duration < 10 || (int) $duration > 1440) {
            $errors['duration_min'] = 'Duration must be between 10 and 1440 minutes.';
        } else {
            $data['duration_min'] = (int) $duration;
        }

        $notes = $in['notes'] ?? '';
        if (mb_strlen($notes) > 1000) {
            $errors['notes'] = 'Notes can have at most 1000 characters.';
        } else {
            $data['notes'] = $notes;
        }

        return [$data, $errors];
    }
}
