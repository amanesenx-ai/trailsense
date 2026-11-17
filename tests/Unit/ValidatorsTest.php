<?php
declare(strict_types=1);

use TrailSense\Domain\ActivityValidator;
use TrailSense\Domain\PlanValidator;
use TrailSense\Domain\UserValidator;

$today = new DateTimeImmutable('2026-11-17');
$trailExists = fn (int $id) => $id === 3;
$validPlan = ['trail_id' => '3', 'planned_date' => '2026-11-21', 'start_time' => '09:30', 'duration_min' => '180', 'notes' => ''];

return [
    'valid plan passes (AC2.1)' => function () use ($today, $trailExists, $validPlan) {
        [$data, $errors] = PlanValidator::validate($validPlan, $trailExists, false, $today);
        assertSame([], $errors);
        assertSame(3, $data['trail_id']);
        assertSame('09:30', $data['start_time']);
    },
    'plan in the past is rejected (AC2.2)' => function () use ($today, $trailExists, $validPlan) {
        [, $errors] = PlanValidator::validate(['planned_date' => '2026-11-16'] + $validPlan, $trailExists, false, $today);
        assertSame('The date cannot be in the past.', $errors['planned_date']);
    },
    'plan today is accepted' => function () use ($today, $trailExists, $validPlan) {
        [, $errors] = PlanValidator::validate(['planned_date' => '2026-11-17'] + $validPlan, $trailExists, false, $today);
        assertSame([], $errors);
    },
    'impossible date, bad time, unknown trail and short duration are all reported' => function () use ($today, $trailExists) {
        [, $errors] = PlanValidator::validate(
            ['trail_id' => '99', 'planned_date' => '2026-02-30', 'start_time' => '25:00', 'duration_min' => '10'],
            $trailExists, false, $today
        );
        assertSame(['trail_id', 'planned_date', 'start_time', 'duration_min'], array_keys($errors));
    },
    'SQL-looking trail id is rejected, not passed on' => function () use ($today, $trailExists, $validPlan) {
        [, $errors] = PlanValidator::validate(['trail_id' => "3 OR 1=1"] + $validPlan, $trailExists, false, $today);
        assertTrue(isset($errors['trail_id']));
    },
    'group plan needs size, meeting point and meeting time before start (AC9.1)' => function () use ($today, $trailExists, $validPlan) {
        [, $errors] = PlanValidator::validate(
            ['group_size' => '1', 'meeting_point' => '', 'meeting_time' => '10:00'] + $validPlan, $trailExists, true, $today
        );
        assertSame(['group_size', 'meeting_point', 'meeting_time'], array_keys($errors));
    },
    'valid group plan returns group data' => function () use ($today, $trailExists, $validPlan) {
        [$data, $errors] = PlanValidator::validate(
            ['group_size' => '8', 'meeting_point' => 'Valongo station', 'meeting_time' => '09:00'] + $validPlan,
            $trailExists, true, $today
        );
        assertSame([], $errors);
        assertSame(['group_size' => 8, 'meeting_point' => 'Valongo station', 'meeting_time' => '09:00'], $data['group']);
    },
    'activity in the future is rejected (AC7.1)' => function () use ($today) {
        $find = fn (int $id) => ['id' => 1, 'distance_km' => '4.2'];
        [, $errors] = ActivityValidator::validate(['trail_id' => '1', 'hiked_on' => '2026-11-18', 'duration_min' => '60'], $find, $today);
        assertTrue(isset($errors['hiked_on']));
    },
    'activity distance defaults to the trail distance; comma decimals accepted' => function () use ($today) {
        $find = fn (int $id) => ['id' => 1, 'distance_km' => '4.2'];
        [$a, $e1] = ActivityValidator::validate(['trail_id' => '1', 'hiked_on' => '2026-11-10', 'duration_min' => '60', 'distance_km' => ''], $find, $today);
        [$b, $e2] = ActivityValidator::validate(['trail_id' => '1', 'hiked_on' => '2026-11-10', 'duration_min' => '60', 'distance_km' => '5,5'], $find, $today);
        assertSame([], $e1 + $e2);
        assertSame(4.2, $a['distance_km']);
        assertSame(5.5, $b['distance_km']);
    },
    'activity notes over 1000 chars are rejected (AC7.3)' => function () use ($today) {
        $find = fn (int $id) => ['id' => 1, 'distance_km' => '4.2'];
        [, $errors] = ActivityValidator::validate(['trail_id' => '1', 'hiked_on' => '2026-11-10', 'duration_min' => '60', 'notes' => str_repeat('a', 1001)], $find, $today);
        assertTrue(isset($errors['notes']));
    },
    'registration rules (AC8.1)' => function () {
        [, $errors] = UserValidator::validateRegistration(['name' => 'J', 'email' => 'nope', 'password' => 'short', 'role' => 'admin']);
        assertSame(['name', 'email', 'password', 'role'], array_keys($errors));
        [$clean, $ok] = UserValidator::validateRegistration(['name' => 'Joana', 'email' => 'Joana@Mail.PT', 'password' => 'longenough', 'role' => 'hiker']);
        assertSame([], $ok);
        assertSame('joana@mail.pt', $clean['email']);
    },
];
