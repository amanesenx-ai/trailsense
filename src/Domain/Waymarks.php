<?php
declare(strict_types=1);

namespace TrailSense\Domain;

/**
 * Trail waymark legend (AC13.6, revised in release 4) following the FCMP marks as shown in the Rota Vicentina manual
 * "Marcação, Sinalética e Manutenção" (2021), p. 13: GR white over red, PR yellow over red, PL white over green.
 * Shapes: right way = two horizontal stripes; wrong way = the two colours crossed; turn = a corner whose
 * horizontal part points the way to turn (the leg goes down on the opposite side).
 */
final class Waymarks
{
    public const SYSTEMS = [
        'GR' => ['name' => 'Grande Rota (GR)', 'top' => '#ffffff', 'bottom' => '#d0202e',
            'about' => 'White over red. Long-distance route: more than 30 km or more than one day.'],
        'PR' => ['name' => 'Pequena Rota (PR)', 'top' => '#ffd400', 'bottom' => '#d0202e',
            'about' => 'Yellow over red. Short route: up to 30 km, walked in one day.'],
        'PL' => ['name' => 'Percurso Local (PL)', 'top' => '#ffffff', 'bottom' => '#7ab51d',
            'about' => 'White over green. Local route, at least half of it in a town or village.'],
    ];

    /** key => [label, what it looks like, what to do] */
    public const MARKS = [
        'right' => ['Right way', 'Two horizontal stripes.', 'Keep going on this path.'],
        'wrong' => ['Wrong way', 'The two colours crossed (an X).', 'Not this way — go back to the last mark.'],
        'left' => ['Turn left', 'The stripes make a corner whose horizontal part points left.', 'Turn left at the next junction.'],
        'turn-right' => ['Turn right', 'The stripes make a corner whose horizontal part points right.', 'Turn right at the next junction.'],
    ];

    /** How the marks are placed on the ground (manual, pp. 26–27 and 32). */
    public const PLACEMENT = [
        'You should see a mark at least every 250 m, painted on rocks, walls, posts or trees, 0.8–2 m above the ground.',
        'Turn marks come up to 30 m before the junction; a "right way" mark within 50 m after it confirms you turned correctly.',
        'A "wrong way" cross is painted up to 30 m into the path you should not take.',
        'No mark for more than about 250 m? Go back to the last one you saw — do not guess.',
    ];

    /** @return array{system:?string, name:string, top:?string, bottom:?string, about:string, tips:list<string>} */
    public static function forTrail(string $ref, string $name = ''): array
    {
        $code = strtoupper(preg_replace('/[^A-Za-z]/', '', substr(trim($ref), 0, 2)) ?? '');
        if (isset(self::SYSTEMS[$code])) {
            return ['system' => $code] + self::SYSTEMS[$code] + ['tips' => self::PLACEMENT];
        }
        $boardwalk = stripos($name, 'passadiç') !== false;
        return ['system' => null, 'name' => $boardwalk ? 'Managed walkway' : 'Local signage',
            'top' => null, 'bottom' => null,
            'about' => $boardwalk ? 'Wooden walkways and stairs with their own signposts, kilometre posts and entry points — no painted FCMP marks.' : 'This route uses local signposts, not FCMP painted marks.',
            'tips' => $boardwalk
                ? ['Stay on the walkway; side paths are closed.', 'Follow the kilometre posts and the signs to the exits.', 'Walkways can close in bad weather — check the operator\'s notices.']
                : ['Follow the signposts and the route on the map.', 'Keep the map open: TrailSense warns you if you leave the route.']];
    }

    /**
     * SVG drawing of one mark (60×40), on a grey stone. Geometry traced from the manual's figures.
     * Stripes are 7 units thick with a 3-unit gap; turn = upper bar straight, lower bar + a leg going down.
     */
    public static function svg(string $mark, string $top, string $bottom, string $label): string
    {
        $shapes = [
            'right' => '<rect x="12" y="11" width="36" height="7" fill="' . $top . '"/><rect x="12" y="21" width="36" height="7" fill="' . $bottom . '"/>',
            'wrong' => '<path d="M15 8 45 32" stroke="' . $top . '" stroke-width="7"/><path d="M45 8 15 32" stroke="' . $bottom . '" stroke-width="7"/>',
            // the corner's leg goes down on the far side; the horizontal part points the way to turn (owner check vs manual p. 13)
            'left' => '<rect x="12" y="7" width="36" height="7" fill="' . $top . '"/><path d="M12 17h36v17h-8V24H12z" fill="' . $bottom . '"/>',
            'turn-right' => '<rect x="12" y="7" width="36" height="7" fill="' . $top . '"/><path d="M48 17H12v17h8V24h28z" fill="' . $bottom . '"/>',
        ];
        return '<svg viewBox="0 0 60 40" role="img" aria-label="' . htmlspecialchars($label, ENT_QUOTES) . '">'
            . '<rect x="1" y="1" width="58" height="38" rx="6" fill="#7d8580"/>' . ($shapes[$mark] ?? '') . '</svg>';
    }
}
