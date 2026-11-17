<?php
declare(strict_types=1);

namespace TrailSense\Alerts;

/** Official warnings + rural fire risk for one hike (F4.2). null = not published for that date yet. */
final class OfficialAlerts
{
    public const LEVELS = ['green' => 0, 'yellow' => 1, 'orange' => 2, 'red' => 3];
    public const FIRE_NAMES = [1 => 'Reduced', 2 => 'Moderate', 3 => 'Elevated', 4 => 'Very high', 5 => 'Maximum'];

    /** @param list<array{type:string,level:string,start:string,end:string,text:string}> $warnings */
    public function __construct(
        public readonly string $source,              // 'ipma' | 'demo' | 'none'
        public readonly ?int $warningLevel,          // 0..3, null = not published yet / unavailable
        public readonly array $warnings = [],
        public readonly ?int $fireRisk = null,       // 1..5, null = not published yet / unavailable
        public readonly string $message = '',
    ) {
    }

    public static function unavailable(string $message): self
    {
        return new self('none', null, [], null, $message);
    }
}
