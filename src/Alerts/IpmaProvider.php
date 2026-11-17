<?php
declare(strict_types=1);

namespace TrailSense\Alerts;

use DateTimeImmutable;
use TrailSense\Weather\OpenMeteoProvider;

/**
 * IPMA open data (ADR-012). Warnings: https://api.ipma.pt/open-data/forecast/warnings/warnings_www.json
 * Fire risk (RCM 1–5 per municipality): https://api.ipma.pt/open-data/forecast/meteorology/rcm/rcm-d{0,1}.json
 */
final class IpmaProvider implements OfficialAlertsProvider
{
    public const WARNINGS_URL = 'https://api.ipma.pt/open-data/forecast/warnings/warnings_www.json';
    public const FIRE_URL = 'https://api.ipma.pt/open-data/forecast/meteorology/rcm/rcm-d%d.json';
    public const WARNING_HORIZON_DAYS = 2;   // today + 2 (IPMA publishes about 3 days)
    public const FIRE_HORIZON_DAYS = 1;      // rcm-d0 (today) and rcm-d1 (tomorrow)
    private const COASTAL_ONLY = ['Agitação Marítima'];

    /** @var callable(string):?string */
    private $fetch;
    /** @var array<string,?array> per-request cache */
    private array $cache = [];

    public function __construct(?callable $fetch = null, private readonly ?DateTimeImmutable $today = null)
    {
        $this->fetch = $fetch ?? [OpenMeteoProvider::class, 'httpGet'];
    }

    public function alerts(string $area, bool $coastal, float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): OfficialAlerts
    {
        $today = ($this->today ?? new DateTimeImmutable('today'))->setTime(0, 0);
        $days = (int) $today->diff($start->setTime(0, 0))->format('%r%a');
        if ($days < 0) {
            return OfficialAlerts::unavailable('This hike date has passed.');
        }

        $level = null;
        $list = [];
        $messages = [];
        if ($days <= self::WARNING_HORIZON_DAYS) {
            $warnings = $this->json(self::WARNINGS_URL);
            if ($warnings === null) {
                $messages[] = 'IPMA warnings could not be loaded.';
            } else {
                [$level, $list] = self::matchWarnings($warnings, $area, $coastal, $start, $end);
            }
        } else {
            $messages[] = 'IPMA publishes warnings about 3 days ahead.';
        }

        $fire = null;
        if ($days <= self::FIRE_HORIZON_DAYS) {
            $rcm = $this->json(sprintf(self::FIRE_URL, $days));
            $fire = $rcm === null ? null : self::nearestFireRisk($rcm, $lat, $lon);
            if ($fire === null) {
                $messages[] = 'IPMA fire risk could not be loaded.';
            }
        } else {
            $messages[] = 'Fire risk is published for today and tomorrow.';
        }

        return new OfficialAlerts('ipma', $level, $list, $fire, implode(' ', $messages));
    }

    /** @return array{0:int, 1:list<array<string,string>>} highest level (0 = none) and the matching warnings */
    public static function matchWarnings(array $warnings, string $area, bool $coastal, DateTimeImmutable $start, DateTimeImmutable $end): array
    {
        $level = 0;
        $list = [];
        foreach ($warnings as $w) {
            $lvl = OfficialAlerts::LEVELS[$w['awarenessLevelID'] ?? 'green'] ?? 0;
            if (($w['idAreaAviso'] ?? '') !== $area || $lvl === 0) {
                continue;
            }
            if (!$coastal && in_array($w['awarenessTypeName'] ?? '', self::COASTAL_ONLY, true)) {
                continue;
            }
            $ws = new DateTimeImmutable((string) $w['startTime']);
            $we = new DateTimeImmutable((string) $w['endTime']);
            if ($ws >= $end || $we <= $start) {
                continue;                                     // does not overlap the hike window
            }
            $level = max($level, $lvl);
            $list[] = ['type' => (string) $w['awarenessTypeName'], 'level' => (string) $w['awarenessLevelID'],
                'start' => $ws->format('D H:i'), 'end' => $we->format('D H:i'), 'text' => (string) ($w['text'] ?? '')];
        }
        return [$level, $list];
    }

    /** RCM of the municipality whose reference point is nearest to the trail start. */
    public static function nearestFireRisk(array $rcm, float $lat, float $lon): ?int
    {
        $best = null;
        $bestD = INF;
        foreach ($rcm['local'] ?? [] as $loc) {
            if (!isset($loc['latitude'], $loc['longitude'], $loc['data']['rcm'])) {
                continue;
            }
            $d = ($loc['latitude'] - $lat) ** 2 + (($loc['longitude'] - $lon) * cos(deg2rad($lat))) ** 2;
            if ($d < $bestD) {
                $bestD = $d;
                $best = (int) $loc['data']['rcm'];
            }
        }
        return ($best !== null && $best >= 1 && $best <= 5) ? $best : null;
    }

    private function json(string $url): ?array
    {
        if (!array_key_exists($url, $this->cache)) {
            $body = ($this->fetch)($url);
            $data = $body === null ? null : json_decode($body, true);
            $this->cache[$url] = is_array($data) ? $data : null;
        }
        return $this->cache[$url];
    }
}
