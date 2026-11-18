<?php
declare(strict_types=1);

/** Release 3 flows through real HTTP: hike mode, waymarks, map layers, visual risk check (F13, AC1.12, F4.3). */
$port = 8096;
$server = null;
$base = "http://127.0.0.1:$port";
$login = function (string $email) use ($base): Browser {
    $b = new Browser($base);
    $b->get('r=login')->post('r=login.submit', ['email' => $email, 'password' => 'trailsense123']);
    return $b;
};

return [
    '__before' => function () use (&$server, $port) {
        resetTestDatabase();
        $cmd = sprintf('TS_ENV=test TS_WEATHER=demo exec php -S 127.0.0.1:%d -t %s', $port, escapeshellarg(TS_ROOT . '/public'));
        $server = proc_open($cmd, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) { usleep(100000); }
    },
    '__after' => function () use (&$server) { if ($server) { proc_terminate($server); } },

    'trail page offers "Start hike" and shows the trail marks legend (AC13.1, AC13.6)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=trail&id=2');
        assertSame(200, $b->status);
        assertContains('r=hike&amp;id=2', $b->body);
        assertContains('How this trail is marked', $b->body);
        assertContains('Pequena Rota (PR)', $b->body);
        assertContains('fill="#ffd400"', $b->body, 'PR marks are yellow over red');
        assertContains('Wrong way', $b->body);
    },
    'hike mode: full-screen map, compass, GPS banner, progress, share, voice, finish, legend; no 112 (AC13.1–13.11)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=hike&id=2');
        assertSame(200, $b->status);
        foreach (['signed-in hike-mode"', 'data-hike=', 'data-el="compass"', 'data-el="offroute"', 'data-el="nav"', 'data-el="voice"', 'data-el="alt"', '&quot;profile&quot;:[[0',
            'Share my position', 'data-el="finish"', 'Trail marks for this route', 'hike-core.js', 'hike.js', 'leaflet@1.9.4'] as $needle) {
            assertContains($needle, $b->body);
        }
        assertContains('from=hike', $b->body, 'Finish goes to Publish a hike, pre-filled');
        assertTrue(!str_contains($b->body, 'tel:112'), '112 button removed by the owner (AC13.7 revised)');
    },
    'hike mode on a boardwalk route explains its own signage; unknown trail is 404' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=hike&id=4');
        assertContains('Managed walkway', $b->body);
        assertSame(404, $b->get('r=hike&id=999')->status);
    },
    'hike mode needs a signed-in user' => function () use ($base) {
        $b = (new Browser($base))->get('r=hike&id=2');
        assertSame(302, $b->status);
    },
    'finishing a hike pre-fills Publish with trail, today, distance and duration; bad values are ignored (AC13.8)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=publish&from=hike&trail_id=2&distance_km=6.43&duration_min=131');
        assertSame(200, $b->status);
        assertContains('value="6.4"', $b->body);
        assertContains('value="131"', $b->body);
        assertContains('value="' . date('Y-m-d') . '"', $b->body);
        $bad = $b->get('r=publish&from=hike&trail_id=2&distance_km=-3&duration_min=abc');
        assertTrue(!str_contains($bad->body, 'value="-3"') && !str_contains($bad->body, 'value="abc"'));
    },
    'visual risk check: verdict, hour by hour, checklist, factor tiles, direct weather links (AC4.11–4.15)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=plan&id=1');
        $b->post('r=plan.check&id=1', []);
        $b->follow();
        assertSame(200, $b->status);
        assertMatches('/class="verdict verdict-(ok|caution|warning|pending)"/', $b->body);
        assertMatches('/<p class="verdict-title">(Good to go|Go prepared|Rethink this plan|Looks fine so far)/', $b->body);
        assertContains('class="wx-chart"', $b->body);
        assertContains('class="hour hour-in"', $b->body);
        assertContains('data-checklist="plan-1"', $b->body);
        assertContains('class="tile tile-', $b->body);
        assertContains('https://api.open-meteo.com/v1/forecast?latitude=', $b->body);
        assertContains('weather_code', $b->body);
        assertContains('https://www.ipma.pt/pt/otempo/prev-sam/?p=PTO', $b->body);
        assertContains('See every rule that was checked', $b->body);
    },
    'map layers are standard, topographic and waymarked trails, with referrer policy set (AC1.12)' => function () use ($base) {
        $js = (string) file_get_contents(TS_ROOT . '/public/assets/app.js');
        foreach (['tile.openstreetmap.org', 'tile.opentopomap.org', 'tile.waymarkedtrails.org/hiking', "referrerPolicy: 'strict-origin-when-cross-origin'", 'L.control.layers'] as $n) {
            assertContains($n, $js);
        }
    },
];
