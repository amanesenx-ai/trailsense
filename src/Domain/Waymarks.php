<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * Trail waymark legend (AC13.6), following the FCMP marking standard used on homologated Portuguese trails:
 * GR white/red, PR yellow/red, PL green/white; marks: right way, turn left, turn right, wrong way.
 */
final class Waymarks
{
    public const SYSTEMS = [
        'GR' => ['name' => 'Grande Rota (GR)', 'top' => '#ffffff', 'bottom' => '#d62828',
            'about' => 'Long-distance route, usually more than 30 km, often in stages.'],
        'PR' => ['name' => 'Pequena Rota (PR)', 'top' => '#ffd400', 'bottom' => '#d62828',
            'about' => 'Short route, done in one day.'],
        'PL' => ['name' => 'Percurso Local (PL)', 'top' => '#2e9e4f', 'bottom' => '#ffffff',
            'about' => 'Local route, mostly in an urban setting.'],
    ];

    public const MARKS = [
        'right' => ['Right way', 'Two parallel stripes: keep going.'],
        'left' => ['Turn left', 'Stripes bend to the left at the junction.'],
        'turn-right' => ['Turn right', 'Stripes bend to the right at the junction.'],
        'wrong' => ['Wrong way', 'A cross: not this way, go back to the last mark.'],
    ];

    /** @return array{system:?string, name:string, top:?string, bottom:?string, about:string, tips:list<string>} */
    public static function forTrail(string $ref, string $name = ''): array
    {
        $code = strtoupper(preg_replace('/[^A-Za-z]/', '', substr(trim($ref), 0, 2)) ?? '');
        if (isset(self::SYSTEMS[$code])) {
            return ['system' => $code] + self::SYSTEMS[$code] + ['tips' => [
                'Marks are painted on rocks, posts, walls and tree trunks, at eye level and in both directions.',
                'At junctions, look for a mark within about 50 m after the turn to confirm you are right.',
                'No mark for a long time? Go back to the last one you saw — do not guess.',
            ]];
        }
        $boardwalk = stripos($name, 'passadiç') !== false;
        return ['system' => null, 'name' => $boardwalk ? 'Managed walkway' : 'Local signage',
            'top' => null, 'bottom' => null,
            'about' => $boardwalk ? 'Wooden walkways and stairs with their own signposts and entry points.' : 'This route uses local signposts.',
            'tips' => $boardwalk
                ? ['Stay on the walkway; side paths are closed.', 'Follow the kilometre posts and the signs to the exits.', 'Walkways can close in bad weather — check the operator\'s notices.']
                : ['Follow the signposts and the route on the map.', 'Keep the map open: TrailSense warns you if you leave the route.']];
    }
}
