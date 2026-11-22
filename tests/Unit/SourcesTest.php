<?php
declare(strict_types=1);

use TrailSense\Domain\Sources;

/* AC0.2: every source has a name, a description and an https link; the four groups exist. */
return [
    'sources are grouped and every item has name, description and an https link' => function () {
        $all = Sources::all();
        assertSame(['data', 'rules', 'evidence', 'compare'], array_keys($all));
        $n = 0;
        foreach ($all as $group) {
            foreach ($group['items'] as $s) {
                assertTrue($s['name'] !== '' && $s['what'] !== '', 'name and description');
                assertTrue(str_starts_with($s['url'], 'https://'), $s['url'] . ' is https');
                $n++;
            }
        }
        assertTrue($n >= 20, 'at least 20 sources, got ' . $n);
    },
    'the data the app really calls is credited (Open-Meteo, IPMA, OpenStreetMap)' => function () {
        $urls = implode(' ', array_column(Sources::all()['data']['items'], 'url'));
        foreach (['open-meteo.com', 'ipma.pt', 'openstreetmap.org'] as $host) {
            assertTrue(str_contains($urls, $host), $host);
        }
    },
];
