<?php
declare(strict_types=1);

namespace TrailSense\Core;

/** Small inline SVG icons (stroke = currentColor) for risk factors and weather (AC4.13, AC4.14). Decorative: aria-hidden. */
final class Icons
{
    private const PATHS = [
        'rain' => '<path d="M7 15a4 4 0 0 1 .5-8A5.5 5.5 0 0 1 18 8.5a3.5 3.5 0 0 1-.5 6.5z"/><path d="m8 18-1 3m5-3-1 3m5-3-1 3"/>',
        'heavy' => '<path d="M7 13a4 4 0 0 1 .5-8A5.5 5.5 0 0 1 18 6.5a3.5 3.5 0 0 1-.5 6.5z"/><path d="m7 16-1.5 5m5-5L9 21m5-5-1.5 5m5-5L16 21"/>',
        'drizzle' => '<path d="M7 15a4 4 0 0 1 .5-8A5.5 5.5 0 0 1 18 8.5a3.5 3.5 0 0 1-.5 6.5z"/><path d="M8 19v.5m4-.5v.5m4-.5v.5"/>',
        'wind' => '<path d="M3 9h11a3 3 0 1 0-3-3M3 15h15a3 3 0 1 1-3 3M3 12h8"/>',
        'heat' => '<path d="M12 4v10m0 0a3.5 3.5 0 1 0 .01 0z"/><path d="M12 4a2 2 0 0 1 2 2v8.5M12 4a2 2 0 0 0-2 2v8.5"/><path d="M17 6h3m-3 4h3"/>',
        'cold' => '<path d="M12 2v20M4 7l16 10M4 17 20 7"/><path d="m9 4 3 3 3-3m-6 16 3-3 3 3"/>',
        'daylight' => '<path d="M3 18h18M6 18a6 6 0 0 1 12 0"/><path d="M12 6V3m-6.4 5.6L3.5 6.5m14.9 2.1 2.1-2.1"/>',
        'official' => '<path d="M12 3 2 20h20z"/><path d="M12 10v4m0 3v.5"/>',
        'fire' => '<path d="M12 21c-4 0-6.5-2.7-6.5-6.2 0-3.3 2.4-5.4 3.6-8.3.6 1.9 1.6 2.9 2.6 3.4C12 7 13 4.6 15 3c.2 3.4 3.5 6 3.5 11 0 4-2.7 7-6.5 7z"/>',
        'trail' => '<path d="M4 21c3-4 1-7 5-9s3-6 7-8"/><circle cx="18" cy="4" r="1.5"/><path d="M14 21h6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'partly' => '<path d="M9 5.5a4 4 0 0 1 6.4 3"/><path d="M5.5 5.5 4 4M9 2v1.5M2.5 10H4"/><path d="M8 20a4 4 0 0 1 .5-8A5 5 0 0 1 18 13a3.5 3.5 0 0 1-.5 7z"/>',
        'cloud' => '<path d="M7 19a4.5 4.5 0 0 1 .5-9A6 6 0 0 1 19 11a4 4 0 0 1-.5 8z"/>',
        'fog' => '<path d="M7 12a4 4 0 0 1 .5-8A5.5 5.5 0 0 1 18 5.5a3.5 3.5 0 0 1 0 6.5"/><path d="M4 16h16M6 20h12"/>',
        'snow' => '<path d="M7 14a4 4 0 0 1 .5-8A5.5 5.5 0 0 1 18 7.5a3.5 3.5 0 0 1-.5 6.5z"/><path d="M8 18h.01M12 20h.01M16 18h.01M10 22h.01M14 22h.01"/>',
        'storm' => '<path d="M7 14a4 4 0 0 1 .5-8A5.5 5.5 0 0 1 18 7.5a3.5 3.5 0 0 1-.5 6.5"/><path d="m13 11-3 5h4l-3 5"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
        'group' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.3 2.7-5.5 6-5.5s6 2.2 6 5.5"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.6c2.9 0 5 1.9 5 4.9"/>',
        'alert' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5m0 3v.5"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        'external' => '<path d="M14 4h6v6m0-6-9 9"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/>',
    ];

    public static function svg(string $name, string $class = 'icon'): string
    {
        $paths = self::PATHS[$name] ?? self::PATHS['cloud'];
        return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
    }
}
