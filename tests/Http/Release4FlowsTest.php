<?php
declare(strict_types=1);

/** Release 4 flows: route fit + elevation, map images on cards, plans agenda, richer home (AC1.13–1.15, AC2.5, AC8.3). */
$port = 8095;
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

    'trail page: "Is this route for me?" with route type, time, climb rating and elevation profile (AC1.13, AC1.14)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=trail&id=6');
        assertSame(200, $b->status);
        assertContains('Is this route for me?', $b->body);
        assertContains('Pequena Rota (PR) — a short route', $b->body);
        assertContains('up to 30 km', $b->body);
        assertContains('Very demanding', $b->body, 'Caminho do Carteiro: ~94 m/km with a very steep pitch');
        assertContains('class="elev"', $b->body);
        assertContains('Longest climb:', $b->body);
        assertContains('Steepest part:', $b->body);
        assertContains('Open-Meteo Elevation API', $b->body);
    },
    'flat coastal trail is rated gentle' => function () use ($login) {
        assertContains('Gentle · ↑ 60 m', $login('joana@trailsense.pt')->get('r=trail&id=1')->body);
    },
    'real map image on trail, plan, group and feed cards (AC1.15)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        foreach (['r=trails', 'r=plans', 'r=groups', 'r=feed', 'r=home'] as $page) {
            $html = $b->get($page)->body;
            assertContains('class="smap"', $html, $page);
            assertContains('https://tile.openstreetmap.org/', $html, $page);
        }
        assertContains('cm-lay-split', $b->get('r=trails')->body, 'trail cards: photo + map');
    },
    'plans page is an agenda with tabs Upcoming / Joined / Past (AC2.5)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=plans');
        assertContains('class="agenda-row', $b->body);
        assertContains('class="ag-date"', $b->body);
        assertContains('r=plans&amp;tab=joined', $b->body);
        assertContains('aria-current="page">My upcoming', $b->body);
        $past = $b->get('r=plans&tab=past');
        assertContains('agenda-past', $past->body);
        assertContains('days ago', $past->body);
        assertContains('aria-current="page">Past', $past->body);
        assertContains('aria-current="page">My upcoming', $b->get('r=plans&tab=bogus')->body, 'unknown tab falls back');
    },
    'home: photo hero with the next hike, countdown, verdict, start hike; stats; discover trails with photos; community (AC8.3)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=home');
        assertContains('class="hero"', $b->body);
        assertContains('--hero: url(', $b->body);
        assertContains('Your next hike:', $b->body);
        assertMatches('/in \d+ days|tomorrow|today/', $b->body);
        assertContains('r=hike&amp;id=', $b->body);
        assertContains('m climbed', $b->body);
        assertContains('class="discover"', $b->body);
        assertContains('class="post-card"', $b->body);
    },
];
