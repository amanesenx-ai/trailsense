<?php
declare(strict_types=1);

/** Release 5: reviewer's improvements list (landing, weather on trails, favourites, filters, organizer tools, password). */
$port = 8094;
$server = null;
$base = "http://127.0.0.1:$port";
$login = function (string $email, string $password = 'trailsense123') use ($base): Browser {
    $b = new Browser($base);
    $b->get('r=login')->post('r=login.submit', ['email' => $email, 'password' => $password]);
    return $b;
};

return [
    '__before' => function () use (&$server, $port) {
        resetTestDatabase();
        @array_map('unlink', glob(TS_ROOT . '/var/cache/outlook-*.json') ?: []);
        $cmd = sprintf('TS_ENV=test TS_WEATHER=demo exec php -S 127.0.0.1:%d -t %s', $port, escapeshellarg(TS_ROOT . '/public'));
        $server = proc_open($cmd, [['pipe', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']], $pipes);
        for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) { usleep(100000); }
    },
    '__after' => function () use (&$server) { if ($server) { proc_terminate($server); } },

    'public landing page: headline, 3 pillars, difference, two profiles, sign-up (AC0.1, items 1-4)' => function () use ($base) {
        $b = (new Browser($base))->get('r=home');
        assertSame(200, $b->status);
        foreach (['Hike smarter. Explore safer. Share the experience.', 'Discover and plan', 'Decide with the weather', 'Share and hike together',
            'Why TrailSense and not just another trails app?', 'Run safer group hikes in four steps', 'Create a free account', 'class="disc-card',
            'See it in the app', 'img/shot-risk.jpg', 'img/shot-gps.jpg', 'img/shot-organizer.jpg', 'For club organizers: from invite to briefing',
            'Where the information comes from', 'What TrailSense cannot do', 'not published yet', 'class="lp-facts"', 'chip chip-', 'climbs</span>',
            'data-lp-nav', 'href="#features"', 'id="features"', 'href="#sources"', 'id="sources"', 'class="signin-link"'] as $n) {
            assertContains($n, $b->body);
        }
        // 22 Nov: lighter page — TrailSense column first, no outdated 'no offline maps' claim, search icon for Discover
        assertTrue(strpos($b->body, 'diff-us') < strpos($b->body, 'diff-them'), 'TrailSense column first');
        assertTrue(!str_contains($b->body, 'no offline maps'), 'outdated limit removed');
        foreach ([] as $n) {
            assertContains($n, $b->body);
        }
    },
    'sources page is public and every page links to it in the footer (AC0.2)' => function () use ($base) {
        $b = (new Browser($base))->get('r=sources');
        assertSame(200, $b->status);
        foreach (['Sources and credits', 'Open-Meteo', 'IPMA', 'Rota Vicentina', 'Decreto-Lei n.º 82/2021', 'AllTrails', 'rel="noopener"'] as $n) {
            assertContains($n, $b->body);
        }
        assertContains('class="site-foot"', (new Browser($base))->get('r=home')->body);
        assertContains('r=sources', (new Browser($base))->get('r=login')->body);
    },
    'the app states its limits where users decide (risk check and hike mode)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        assertContains('Guidance, not a guarantee', $b->get('r=plan&id=1')->body);
        assertContains('always confirm with the trail marks', $b->get('r=hike&id=2')->body);
        foreach (['shot-risk.jpg', 'shot-gps.jpg', 'shot-organizer.jpg'] as $img) {
            assertTrue(is_file(TS_ROOT . '/public/assets/img/' . $img), $img . ' exists');
        }
    },
    'demo credentials are not shown publicly (item 35)' => function () use ($base) {
        $b = (new Browser($base))->get('r=login');
        assertTrue(!str_contains($b->body, 'trailsense123'));
        assertTrue(!str_contains((string) file_get_contents(TS_ROOT . '/README.md'), 'trailsense123'));
    },
    'trail page: 7-day weather with rain chance, wind, update time and "plan this day" (AC1.16, items 10-13)' => function () use ($login) {
        $b = $login('joana@trailsense.pt')->get('r=trail&id=2');
        assertContains('Weather at the start, next 7 days', $b->body);
        assertSame(7, substr_count($b->body, '<li class="day'));
        assertMatches('/\d+%<\/span>/', $b->body);
        assertContains('updated ', $b->body);
        assertContains('r=plan.new&amp;trail_id=2&amp;date=', $b->body);
        $date = (new DateTimeImmutable('+3 days'))->format('Y-m-d');
        assertContains('value="' . $date . '"', $b->get('r=plan.new&trail_id=2&date=' . $date)->body, 'date carried into the form');
    },
    'filters: region and duration; invalid values ignored (AC1.17, item 8)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        $all = substr_count($b->get('r=trails')->body, 'class="trail-card"');
        $short = substr_count($b->get('r=trails&max_min=120')->body, 'class="trail-card"');
        assertTrue($short > 0 && $short < $all, 'duration filter narrows the list');
        assertContains('id="region"', $b->body);
        $arouca = $b->get('r=trails&region=Arouca')->body;
        assertContains('Passadiços do Paiva', $arouca);
        assertTrue(!str_contains($arouca, 'Trilho dos Romanos'));
        assertSame($all, substr_count($b->get('r=trails&region=Narnia&max_min=7')->body, 'class="trail-card"'));
    },
    'save a trail: stored in the DB, shown as saved, filter "saved only", on home and profile (AC1.18, items 6, 14, 20)' => function () use ($login) {
        $b = $login('rita@trailsense.pt')->get('r=trail&id=3');
        assertContains('☆ Save trail', $b->body);
        $b->post('r=trail.save&id=3', []);
        assertSame(303, $b->status, 'PRG redirect');
        $b->follow();
        assertContains('★ Saved', $b->body);
        assertContains('Trail saved', $b->body);
        assertSame(1, (int) resetlessCount('trail_favourite WHERE user_id = 3'));
        assertContains('Trilho do Castelo', $b->get('r=trails&saved=1')->body);
        $home = $b->get('r=home')->body;
        assertContains('★ Saved trails', $home);
        assertContains('Recently viewed', $home);
        assertContains('My saved trails', $b->get('r=account')->body);
        $b->get('r=trail&id=3')->post('r=trail.save&id=3', []);       // toggle off
        assertSame(0, (int) resetlessCount('trail_favourite WHERE user_id = 3'));
    },
    'share buttons on trail pages and group hikes (AC1.19, items 14, 17)' => function () use ($login) {
        $b = $login('bruno@trailsense.pt');
        assertContains('data-share', $b->get('r=trail&id=1')->body);
        assertContains('Share invite link', $b->get('r=plan&id=3')->body);
    },
    'organizer dashboard: events with sign-ups, places left, status, edit and quick create (AC9.8, item 7)' => function () use ($login) {
        $b = $login('bruno@trailsense.pt')->get('r=home');
        foreach (['People signed up', 'Places left', 'class="event-row"', 'class="ev-count"', 'r=plan.edit&amp;id=', '+ New group hike'] as $n) {
            assertContains($n, $b->body);
        }
    },
    'organizer edits a group hike; cannot shrink below people already joined (AC9.9, items 15, 16, 32)' => function () use ($login) {
        foreach (['joana@trailsense.pt', 'rita@trailsense.pt', 'miguel@trailsense.pt'] as $who) {      // 3+ people joined
            $login($who)->get('r=plan&id=3')->post('r=group.join&id=3', []);
        }
        assertTrue((int) resetlessCount('hike_participant WHERE plan_id = 3') >= 3);
        $b = $login('bruno@trailsense.pt')->get('r=plan.edit&id=3');
        assertSame(200, $b->status);
        assertContains('Edit group hike', $b->body);
        $date = (new DateTimeImmutable('+6 days'))->format('Y-m-d');
        $fields = ['trail_id' => '3', 'planned_date' => $date, 'start_time' => '10:00', 'duration_min' => '140', 'notes' => '',
            'group_size' => '2', 'meeting_point' => 'Valongo station', 'meeting_time' => '09:30'];
        $b->post('r=plan.update&id=3', $fields);
        assertSame(422, $b->status, 'smaller than the people already joined');
        assertContains('already joined', $b->body);
        $b->post('r=plan.update&id=3', ['group_size' => '9'] + $fields);
        assertSame(303, $b->status);
        $b->follow();
        assertContains('Group hike updated', $b->body);
        assertContains('Valongo station', $b->body);
        assertSame(1, (int) resetlessCount('group_plan WHERE plan_id = 3 AND group_size = 9'), 'saved in the database');
    },
    'nobody else can edit or update someone else\'s plan (AC9.9, item 34)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        assertSame(403, $b->get('r=plan.edit&id=3')->status);
        $b->get('r=account')->post('r=plan.update&id=3', ['trail_id' => '3', 'planned_date' => '2030-01-01', 'start_time' => '09:00', 'duration_min' => '60']);
        assertSame(403, $b->status);
    },
    'organizer cancels a group hike: participants no longer see it (AC9.9, item 16)' => function () use ($login) {
        $b = $login('bruno@trailsense.pt')->get('r=plan&id=4');
        assertContains('Cancel group hike', $b->body);
        $b->post('r=plan.delete&id=4', ['kind' => 'group']);
        $b->follow();
        assertContains('Group hike cancelled', $b->body);
        assertSame(0, (int) resetlessCount('hike_plan WHERE id = 4'));
        assertSame(0, (int) resetlessCount('hike_participant WHERE plan_id = 4'));
    },
    'change password: wrong current password is refused; then the new one works (AC8.5, item 30)' => function () use ($login, $base) {
        $b = $login('miguel@trailsense.pt')->get('r=account');
        $b->post('r=password.update', ['current_password' => 'nope', 'new_password' => 'NewSecret99', 'repeat_password' => 'NewSecret99']);
        $b->follow();
        assertContains('current password is not correct', $b->body);
        $b->post('r=password.update', ['current_password' => 'trailsense123', 'new_password' => 'NewSecret99', 'repeat_password' => 'NewSecret99']);
        $b->follow();
        assertContains('Password changed', $b->body);
        $again = $login('miguel@trailsense.pt', 'NewSecret99');
        assertSame(200, $again->get('r=plans')->status);
    },
    'offline trail GPS: service worker, download button, offline badge, pending hike (AC13.12–13.15, ADR-020)' => function () use ($login, $base) {
        $b = $login('joana@trailsense.pt')->get('r=hike&id=2');
        assertContains('Download for offline', $b->body);
        assertContains('data-el="offline-badge"', $b->body);
        $sw = (string) file_get_contents($base . '/sw.js');
        foreach (['ts-pages', 'ts-tiles', 'tile.openstreetmap.org', 'clear-pages'] as $n) {
            assertContains($n, $sw);
        }
        $js = (string) file_get_contents(TS_ROOT . '/public/assets/hike.js');
        $off = (string) file_get_contents(TS_ROOT . '/public/assets/hike-offline.js');
        assertContains('tilesForRoute', $off);
        assertContains('window.HikeOffline(', $js);
        assertContains('ts-pending-hike', $js);
        assertContains('hike-offline.js', $b->body ?? (string) file_get_contents(TS_ROOT . '/src/Controllers/HikeController.php'));
        assertContains("serviceWorker.register('sw.js')", (string) file_get_contents(TS_ROOT . '/public/assets/app.js'));
    },
    'cleaner screens: trail page sections and hike tools in dropdowns; account dropdown menu (release 6)' => function () use ($login) {
        $b = $login('joana@trailsense.pt');
        $trail = $b->get('r=trail&id=2')->body;
        assertTrue(substr_count($trail, '<details class="drop"') >= 4, 'weather, fit, marks, reports as dropdowns');
        assertContains('class="drop-preview"', $trail);
        $hike = $b->get('r=hike&id=2')->body;
        assertTrue(substr_count($hike, '<details class="drop"') === 3, 'offline, marks, how directions work');
        assertContains('class="user-menu" data-menu', $hike . $trail);
        foreach (['My profile and stats', '★ Saved trails', 'Publish a hike', 'Sign out'] as $item) {
            assertContains($item, $trail);
        }
    },
    'forms show a busy state while submitting (AC8.6, item 28)' => function () {
        assertContains("aria-busy", (string) file_get_contents(TS_ROOT . '/public/assets/app.js'));
    },
];
