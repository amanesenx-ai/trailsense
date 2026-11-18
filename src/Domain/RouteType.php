<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * Route type in plain words for first-timers (AC1.13), with the FCMP classes used in Portugal:
 * GR more than 30 km or more than one day; PR up to 30 km, done in one day; PL at least half in an urban setting.
 */
final class RouteType
{
    /** @return array{code:string, name:string, size:string, plain:string, length:string, time:string} */
    public static function describe(string $ref, string $name, float $km, int $minutes, bool $loop): array
    {
        $code = Waymarks::forTrail($ref, $name)['system'];
        [$typeName, $plain] = match ($code) {
            'GR' => ['Grande Rota (GR) — a long route', 'A long-distance route: more than 30 km or more than one day, usually walked in stages.'],
            'PR' => ['Pequena Rota (PR) — a short route', 'A short route of up to 30 km that you walk in one day. Homologated by the FCMP, so it is marked all the way.'],
            'PL' => ['Percurso Local (PL) — a local route', 'A local route where at least half of the way is in a town or village.'],
            default => ['Not an official PR/GR route', 'Not part of the FCMP network, so it has its own signs. ' . ($km <= 30 ? 'It is a one-day walk.' : 'It is longer than a typical day walk.')],
        };
        $size = match (true) { $km < 5 => 'short', $km <= 10 => 'medium', $km <= 15 => 'long', default => 'very long' };
        $length = match ($size) {
            'short' => 'Short walk (under 5 km)',
            'medium' => 'Medium walk (5–10 km)',
            'long' => 'Long walk (10–15 km)',
            default => 'Very long walk (over 15 km)',
        } . ($loop ? ', a loop: you finish where you started.' : ', one way: plan how to get back from the end.');
        $hours = $minutes / 60;
        $time = match (true) {
            $hours < 2 => 'About ' . self::durationText($minutes) . ' — a morning or an afternoon.',
            $hours < 4 => 'About ' . self::durationText($minutes) . ' — half a day with breaks.',
            default => 'About ' . self::durationText($minutes) . ' — a full day: bring lunch.',
        };
        return ['code' => (string) $code, 'name' => $typeName, 'size' => $size, 'plain' => $plain, 'length' => $length, 'time' => $time];
    }

    public static function durationText(int $minutes): string
    {
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h === 0 ? $m . ' min' : $h . ' h' . ($m ? ' ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) : '');
    }
}
