<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/** One-line verdict for the risk check (AC4.11) and the "what to bring / do" list (AC4.12). Pure. */
final class Verdict
{
    /**
     * @param array $factors presented factors (status, rule, precaution, label)
     * @return array{key:string, title:string, reason:string}
     */
    public static function from(string $overall, bool $complete, array $factors): array
    {
        $names = fn (string $level) => array_values(array_map(fn ($f) => $f['rule']['name'],
            array_filter($factors, fn ($f) => $f['rule'] !== null && $f['status'] === $level)));
        $warn = $names('warning');
        $caution = $names('caution');
        if ($overall === 'warning' || $warn !== []) {
            return ['key' => 'warning', 'title' => 'Rethink this plan', 'reason' => self::join($warn ?: $caution) . '.'];
        }
        if ($overall === 'caution' || $caution !== []) {
            return ['key' => 'caution', 'title' => 'Go prepared', 'reason' => self::join($caution) . '.'];
        }
        if (!$complete) {
            $pending = array_values(array_map(fn ($f) => mb_strtolower($f['label']),
                array_filter($factors, fn ($f) => in_array($f['status'], ['pending', 'unknown'], true))));
            return ['key' => 'pending', 'title' => 'Looks fine so far — check again closer to the date',
                'reason' => 'Not published yet: ' . ($pending ? implode(', ', $pending) : 'part of the forecast') . '.'];
        }
        return ['key' => 'ok', 'title' => 'Good to go', 'reason' => 'Nothing in the forecast, daylight or official warnings needs extra care.'];
    }

    /** @return list<array{text:string, level:string}> triggered precautions first (warning, caution), then the basics */
    public static function checklist(array $factors): array
    {
        $items = [];
        foreach (['warning', 'caution'] as $level) {
            foreach ($factors as $f) {
                if ($f['rule'] !== null && $f['status'] === $level && ($f['precaution'] ?? '') !== '') {
                    $items[] = ['text' => $f['precaution'], 'level' => $level];
                }
            }
        }
        foreach (['Water and a snack', 'Phone charged, with this plan offline', 'Tell someone your route and when you expect to be back'] as $basic) {
            $items[] = ['text' => $basic, 'level' => 'basic'];
        }
        return $items;
    }

    private static function join(array $names): string
    {
        if (count($names) <= 1) {
            return $names[0] ?? 'See the factors below';
        }
        $lower = fn (string $n) => mb_strtolower(mb_substr($n, 0, 1)) . mb_substr($n, 1);
        $rest = array_map($lower, array_slice($names, 1, -1));
        return implode(', ', [$names[0], ...$rest]) . ' and ' . $lower((string) end($names));
    }
}
