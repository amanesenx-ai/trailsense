<?php
declare(strict_types=1);

namespace TrailSense\Alerts;

use DateTimeImmutable;

interface OfficialAlertsProvider
{
    public function alerts(string $area, bool $coastal, float $lat, float $lon, DateTimeImmutable $start, DateTimeImmutable $end): OfficialAlerts;
}
