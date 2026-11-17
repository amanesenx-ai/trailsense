<?php
declare(strict_types=1);

namespace TrailSense\Domain;

use DateTimeImmutable;

/** Sunset and daylight margin, computed locally so it is always available (AC4.5). */
final class Daylight
{
    public const SAFETY_BUFFER_MIN = 30;

    public static function sunset(DateTimeImmutable $date, float $lat, float $lon): DateTimeImmutable
    {
        $noon = $date->setTime(12, 0);
        $info = date_sun_info($noon->getTimestamp(), $lat, $lon);
        return (new DateTimeImmutable('@' . $info['sunset']))->setTimezone($date->getTimezone());
    }

    /** Minutes from planned end to sunset; negative means finishing after sunset. */
    public static function marginMinutes(DateTimeImmutable $end, DateTimeImmutable $sunset): int
    {
        return intdiv($sunset->getTimestamp() - $end->getTimestamp(), 60);
    }

    /** Latest start that still finishes SAFETY_BUFFER_MIN before sunset (AC5.2). Rounded down to 5 min. */
    public static function latestStart(DateTimeImmutable $sunset, int $durationMin): DateTimeImmutable
    {
        $latest = $sunset->modify('-' . ($durationMin + self::SAFETY_BUFFER_MIN) . ' minutes');
        $minute = (int) $latest->format('i');
        return $latest->setTime((int) $latest->format('H'), $minute - $minute % 5);
    }
}
