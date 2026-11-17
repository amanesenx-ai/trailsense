<?php
declare(strict_types=1);

namespace TrailSense\Alerts;

use DateTimeImmutable;

/** Offline stand-in for IPMA (local development/rehearsal only, labelled "demo" in the UI). */
final class DemoAlertsProvider implements OfficialAlertsProvider
{
    public function alerts(string $area, bool $coastal, float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): OfficialAlerts
    {
        $days = (int) (new DateTimeImmutable('today'))->diff($start->setTime(0, 0))->format('%r%a');
        if ($days < 0) {
            return OfficialAlerts::unavailable('This hike date has passed.');
        }
        $seed = crc32($area . $start->format('Y-m-d'));
        $level = $days <= IpmaProvider::WARNING_HORIZON_DAYS ? [0, 0, 0, 1][$seed % 4] : null;
        $list = $level ? [['type' => 'Precipitação', 'level' => 'yellow', 'start' => $start->format('D 06:00'),
            'end' => $start->format('D 21:00'), 'text' => 'Demo warning']] : [];
        $fire = $days <= IpmaProvider::FIRE_HORIZON_DAYS ? 1 + ($seed >> 4) % 2 : null;
        return new OfficialAlerts('demo', $level, $list, $fire, '');
    }
}
